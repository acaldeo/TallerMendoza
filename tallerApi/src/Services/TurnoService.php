<?php
namespace App\Services;

use App\Entities\Taller;
use App\Entities\Turno;
use App\Services\EmailService;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\LockMode;
use Exception;

/**
 * Service class for managing workshop appointments (turnos).
 *
 * This class handles the creation, finalization, and querying of appointments in a workshop system.
 * It manages appointment states, queue management, and ensures thread-safe operations using database locks.
 * Appointments can be in states like waiting, in workshop, or finalized, with automatic promotion based on workshop capacity.
 */
class TurnoService
{
    /** @var EntityManager Doctrine EntityManager for database operations */
    private EntityManager $em;
    /** @var EmailService Service for sending email notifications */
    private EmailService $emailService;

    /**
     * Constructor to inject the Doctrine EntityManager dependency.
     *
     * @param EntityManager $em The EntityManager instance for database interactions.
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->emailService = new EmailService($em);
    }

    /**
     * Creates a new appointment (turno) for a workshop.
     *
     * This method handles the creation of a new appointment with automatic numbering,
     * state assignment based on workshop capacity, and ensures thread-safe operations
     * using pessimistic locking on the workshop entity.
     *
     * @param int $tallerId The ID of the workshop where the appointment is being created.
     * @param array $datos Array containing appointment data: nombreCliente, telefono, modeloVehiculo, descripcionProblema.
     * @return Turno The newly created appointment entity.
     * @throws Exception If the workshop is not found or database operations fail.
     */
    public function crearTurno(int $tallerId, array $datos): Turno
    {
        // Begin database transaction for atomicity
        $this->em->beginTransaction();

        try {
            // Lock the workshop entity to prevent concurrent modifications
            $taller = $this->em->find(Taller::class, $tallerId, LockMode::PESSIMISTIC_WRITE);
            if (!$taller) {
                throw new Exception('Workshop not found');
            }

            // Calculate the next appointment number for this workshop
            $ultimoNumero = $this->em->createQuery(
                'SELECT MAX(t.numeroTurno) FROM App\Entities\Turno t WHERE t.taller = :taller'
            )->setParameter('taller', $taller)->getSingleScalarResult() ?? 0;

            // Create new appointment entity with provided data
            $turno = new Turno();
            $turno->setTaller($taller)
                  ->setNumeroTurno($ultimoNumero + 1)
                  ->setNombreCliente($datos['nombreCliente'])
                  ->setTelefono($datos['telefono'])
                  ->setModeloVehiculo($datos['modeloVehiculo'])
                  ->setDescripcionProblema($datos['descripcionProblema']);

            // Check if appointment can go directly to workshop based on capacity
            $turnosEnTaller = $this->em->createQuery(
                'SELECT COUNT(t) FROM App\Entities\Turno t WHERE t.taller = :taller AND t.estado = :estado'
            )->setParameters([
                'taller' => $taller,
                'estado' => Turno::ESTADO_EN_TALLER
            ])->getSingleScalarResult();

            if ($turnosEnTaller < $taller->getCapacidad()) {
                $turno->setEstado(Turno::ESTADO_EN_TALLER);
            }

            // Persist and flush the new appointment
            $this->em->persist($turno);
            $this->em->flush();
            $this->em->commit();

            // Send email notification for new appointment
            try {
                $this->emailService->enviarNotificacionNuevoTurno($turno);
            } catch (Exception $emailError) {
                // Log email error but don't fail the appointment creation
                error_log("Email notification failed: " . $emailError->getMessage());
            }

            return $turno;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * Finalizes an appointment that is currently in the workshop.
     *
     * Marks the appointment as finalized and automatically promotes the next waiting appointment
     * to the workshop if capacity allows. Uses pessimistic locking to ensure thread safety.
     *
     * @param int $turnoId The ID of the appointment to finalize.
     * @throws Exception If the appointment is not found or not in the correct state.
     */
    public function finalizarTurno(int $turnoId): void
    {
        // Begin transaction for atomic operations
        $this->em->beginTransaction();

        try {
            // Lock the appointment entity for safe modification
            $turno = $this->em->find(Turno::class, $turnoId, LockMode::PESSIMISTIC_WRITE);
            if (!$turno) {
                throw new Exception('Appointment not found');
            }

            // Validate that the appointment is in the workshop
            if ($turno->getEstado() !== Turno::ESTADO_EN_TALLER) {
                throw new Exception('Only appointments in workshop can be finalized');
            }

            // Change appointment state to finalized
            $turno->setEstado(Turno::ESTADO_FINALIZADO);

            // Promote the next waiting appointment to workshop
            $siguienteTurno = $this->em->createQuery(
                'SELECT t FROM App\Entities\Turno t
                 WHERE t.taller = :taller AND t.estado = :estado
                 ORDER BY t.fechaCreacion ASC'
            )->setParameters([
                'taller' => $turno->getTaller(),
                'estado' => Turno::ESTADO_EN_ESPERA
            ])->setMaxResults(1)->getOneOrNullResult();

            if ($siguienteTurno) {
                // State transition: waiting to in workshop
                $siguienteTurno->setEstado(Turno::ESTADO_EN_TALLER);
            }

            // Commit all changes
            $this->em->flush();
            $this->em->commit();
        } catch (Exception $e) {
            // Rollback on error
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * Retrieves the current state of a workshop, including active appointments.
     *
     * Returns information about the workshop's capacity and lists appointments that are
     * either in the workshop or waiting, excluding finalized ones.
     *
     * @param int $tallerId The ID of the workshop to query.
     * @return array Array containing workshop name, capacity, and lists of appointments in workshop and waiting.
     * @throws Exception If the workshop is not found.
     */
    public function obtenerEstadoTaller(int $tallerId): array
    {
        // Find the workshop entity
        $taller = $this->em->find(Taller::class, $tallerId);
        if (!$taller) {
            throw new Exception('Workshop not found');
        }

        // Query active appointments (not finalized) for this workshop
        $turnos = $this->em->createQuery(
            'SELECT t FROM App\Entities\Turno t
             WHERE t.taller = :taller AND t.estado != :finalizado
             ORDER BY t.numeroTurno ASC'
        )->setParameters([
            'taller' => $taller,
            'finalizado' => Turno::ESTADO_FINALIZADO
        ])->getResult();

        $enTaller = [];
        $enEspera = [];

        // Categorize appointments by state
        foreach ($turnos as $turno) {
            $data = [
                'numeroTurno' => $turno->getNumeroTurno(),
                'estado' => $turno->getEstado()
            ];

            if ($turno->getEstado() === Turno::ESTADO_EN_TALLER) {
                $enTaller[] = $data;
            } else {
                $enEspera[] = $data;
            }
        }

        return [
            'taller' => $taller->getNombre(),
            'capacidad' => $taller->getCapacidad(),
            'enTaller' => $enTaller,
            'enEspera' => $enEspera
        ];
    }

    /**
     * Lists all appointments for a specific workshop.
     *
     * Retrieves and formats all appointments associated with the given workshop,
     * including detailed information and formatted dates.
     *
     * @param int $tallerId The ID of the workshop whose appointments to list.
     * @return array Array of appointment data arrays, each containing full appointment details.
     * @throws Exception If the workshop is not found.
     */
    public function listarTurnosTaller(int $tallerId): array
    {
        // Find the workshop entity
        $taller = $this->em->find(Taller::class, $tallerId);
        if (!$taller) {
            throw new Exception('Workshop not found');
        }

        // Query all appointments for this workshop
        $turnos = $this->em->createQuery(
            'SELECT t FROM App\Entities\Turno t
             WHERE t.taller = :taller
             ORDER BY t.numeroTurno ASC'
        )->setParameter('taller', $taller)->getResult();

        // Format appointment data for response
        return array_map(function(Turno $turno) {
            return [
                'id' => $turno->getId(),
                'numeroTurno' => $turno->getNumeroTurno(),
                'nombreCliente' => $turno->getNombreCliente(),
                'telefono' => $turno->getTelefono(),
                'modeloVehiculo' => $turno->getModeloVehiculo(),
                'descripcionProblema' => $turno->getDescripcionProblema(),
                'estado' => $turno->getEstado(),
                'fechaCreacion' => $turno->getFechaCreacion()->format('Y-m-d H:i:s'),
                'fechaInicio' => $turno->getFechaInicio()?->format('Y-m-d H:i:s'),
                'fechaFinalizacion' => $turno->getFechaFinalizacion()?->format('Y-m-d H:i:s')
            ];
        }, $turnos);
    }
}