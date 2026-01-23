<?php
namespace App\Services;

use App\Entities\Taller;
use App\Entities\Turno;
use App\Services\EmailService;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\LockMode;
use Exception;

/**
 * Clase de servicio para gestionar citas de taller (turnos).
 *
 * Esta clase maneja la creación, finalización y consulta de citas en un sistema de talleres.
 * Gestiona los estados de las citas, la administración de colas y asegura operaciones thread-safe usando bloqueos de base de datos.
 * Las citas pueden estar en estados como esperando, en taller o finalizado, con promoción automática basada en la capacidad del taller.
 *
 * Propósito general:
 * - Centralizar la lógica de negocio para operaciones con turnos.
 * - Garantizar integridad de datos mediante transacciones y bloqueos.
 * - Gestionar la cola de turnos y promociones automáticas.
 * - Integrar notificaciones por email al crear turnos.
 *
 * Dependencias:
 * - Utiliza EntityManager de Doctrine para operaciones de BD.
 * - Depende de entidades Taller y Turno.
 * - Usa EmailService para enviar notificaciones.
 * - Emplea bloqueos pesimistas para concurrencia.
 *
 * Interacciones con otras capas:
 * - Es llamada por controladores (TallerController, AdminController) para lógica de turnos.
 * - Accede directamente a la base de datos a través del EntityManager.
 * - Envía emails a través de EmailService sin fallar la operación principal.
 * - Maneja excepciones que pueden ser lanzadas a los controladores.
 */
class TurnoService
{
    /** @var EntityManager EntityManager de Doctrine para operaciones de base de datos */
    private EntityManager $em;
    /** @var EmailService Servicio para enviar notificaciones por email */
    private EmailService $emailService;

    /**
     * Constructor para inyectar la dependencia del EntityManager de Doctrine.
     *
     * @param EntityManager $em La instancia del EntityManager para interacciones con la base de datos.
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->emailService = new EmailService($em);
    }

    /**
     * Crea una nueva cita (turno) para un taller.
     *
     * Este método maneja la creación de una nueva cita con numeración automática,
     * asignación de estado basada en la capacidad del taller, y asegura operaciones thread-safe
     * usando bloqueo pesimista en la entidad del taller.
     *
     * @param int $tallerId El ID del taller donde se está creando la cita.
     * @param array $datos Array que contiene datos de la cita: nombreCliente, telefono, modeloVehiculo, descripcionProblema, patente.
     * @return Turno La entidad de cita recién creada.
     * @throws Exception Si el taller no se encuentra o fallan las operaciones de base de datos.
     */
    public function crearTurno(int $tallerId, array $datos): Turno
    {
        // Iniciar transacción de base de datos para atomicidad
        $this->em->beginTransaction();

        try {
            // Bloquear la entidad del taller para prevenir modificaciones concurrentes
            $taller = $this->em->find(Taller::class, $tallerId, LockMode::PESSIMISTIC_WRITE);
            if (!$taller) {
                throw new Exception('Taller no encontrado');
            }

            // Verificar si hay un turno activo para esta patente
            $existingTurno = $this->em->createQuery(
                'SELECT t FROM App\Entities\Turno t WHERE t.taller = :taller AND t.patente = :patente AND t.estado != :finalizado'
            )->setParameters([
                'taller' => $taller,
                'patente' => $datos['patente'],
                'finalizado' => Turno::ESTADO_FINALIZADO
            ])->getOneOrNullResult();

            if ($existingTurno) {
                throw new Exception('Ya tienes un turno asignado. Tu número de turno es ' . $existingTurno->getNumeroTurno());
            }

            // Calcular el siguiente número de turno para este taller
            $ultimoNumero = $this->em->createQuery(
                'SELECT MAX(t.numeroTurno) FROM App\Entities\Turno t WHERE t.taller = :taller'
            )->setParameter('taller', $taller)->getSingleScalarResult() ?? 0;

            // Crear nueva entidad de turno con los datos proporcionados
            $turno = new Turno();
            $turno->setTaller($taller)
                  ->setNumeroTurno($ultimoNumero + 1)
                  ->setNombreCliente($datos['nombreCliente'])
                  ->setTelefono($datos['telefono'])
                  ->setModeloVehiculo($datos['modeloVehiculo'])
                  ->setPatente($datos['patente'])
                  ->setDescripcionProblema($datos['descripcionProblema']);

            // Verificar si el turno puede ir directamente al taller basado en la capacidad
            $turnosEnTaller = $this->em->createQuery(
                'SELECT COUNT(t) FROM App\Entities\Turno t WHERE t.taller = :taller AND t.estado = :estado'
            )->setParameters([
                'taller' => $taller,
                'estado' => Turno::ESTADO_EN_TALLER
            ])->getSingleScalarResult();

            if ($turnosEnTaller < $taller->getCapacidad()) {
                $turno->setEstado(Turno::ESTADO_EN_TALLER);
            }

            // Persistir y hacer flush del nuevo turno
            $this->em->persist($turno);
            $this->em->flush();
            $this->em->commit();

            // Enviar notificación por email para nuevo turno
            try {
                $this->emailService->enviarNotificacionNuevoTurno($turno);
            } catch (Exception $emailError) {
                // Registrar error de email pero no fallar la creación del turno
                error_log("Notificación de email falló: " . $emailError->getMessage());
            }

            return $turno;
        } catch (Exception $e) {
            // Rollback de transacción en error
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * Finaliza una cita que está actualmente en el taller.
     *
     * Marca la cita como finalizada y automáticamente promociona la siguiente cita en espera
     * al taller si la capacidad lo permite. Usa bloqueo pesimista para asegurar thread safety.
     *
     * @param int $turnoId El ID de la cita a finalizar.
     * @throws Exception Si la cita no se encuentra o no está en el estado correcto.
     */
    public function finalizarTurno(int $turnoId): void
    {
        // Iniciar transacción para operaciones atómicas
        $this->em->beginTransaction();

        try {
            // Bloquear la entidad de la cita para modificación segura
            $turno = $this->em->find(Turno::class, $turnoId, LockMode::PESSIMISTIC_WRITE);
            if (!$turno) {
                throw new Exception('Cita no encontrada');
            }

            // Validar que la cita esté en el taller
            if ($turno->getEstado() !== Turno::ESTADO_EN_TALLER) {
                throw new Exception('Solo las citas en taller pueden ser finalizadas');
            }

            // Cambiar estado de la cita a finalizado
            $turno->setEstado(Turno::ESTADO_FINALIZADO);

            // Promocionar la siguiente cita en espera al taller
            $siguienteTurno = $this->em->createQuery(
                'SELECT t FROM App\Entities\Turno t
                 WHERE t.taller = :taller AND t.estado = :estado
                 ORDER BY t.fechaCreacion ASC'
            )->setParameters([
                'taller' => $turno->getTaller(),
                'estado' => Turno::ESTADO_EN_ESPERA
            ])->setMaxResults(1)->getOneOrNullResult();

            if ($siguienteTurno) {
                // Transición de estado: esperando a en taller
                $siguienteTurno->setEstado(Turno::ESTADO_EN_TALLER);
            }

            // Confirmar todos los cambios
            $this->em->flush();
            $this->em->commit();
        } catch (Exception $e) {
            // Rollback en error
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * Obtiene el estado actual de un taller, incluyendo citas activas.
     *
     * Retorna información sobre la capacidad del taller y listas de citas que están
     * en el taller o esperando, excluyendo las finalizadas.
     *
     * @param int $tallerId El ID del taller a consultar.
     * @return array Array que contiene nombre del taller, capacidad, y listas de citas en taller y esperando.
     * @throws Exception Si el taller no se encuentra.
     */
    public function obtenerEstadoTaller(int $tallerId): array
    {
        // Encontrar la entidad del taller
        $taller = $this->em->find(Taller::class, $tallerId);
        if (!$taller) {
            throw new Exception('Taller no encontrado');
        }

        // Consultar citas activas (no finalizadas) para este taller
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

        // Categorizar citas por estado
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
     * Lista citas para un taller específico con filtrado opcional.
     *
     * Recupera y formatea citas asociadas con el taller dado,
     * con opciones para filtrar por estado o patente.
     *
     * @param int $tallerId El ID del taller cuyas citas listar.
     * @param array $filtros Filtros opcionales: 'estado' (por defecto excluye FINALIZADO), 'patente'.
     * @return array Array de arrays de datos de citas, cada uno conteniendo detalles completos de la cita.
     * @throws Exception Si el taller no se encuentra.
     */
    public function listarTurnosTaller(int $tallerId, array $filtros = []): array
    {
        // Encontrar la entidad del taller
        $taller = $this->em->find(Taller::class, $tallerId);
        if (!$taller) {
            throw new Exception('Taller no encontrado');
        }

        // Construir consulta con filtros
        $qb = $this->em->createQueryBuilder();
        $qb->select('t')
           ->from('App\Entities\Turno', 't')
           ->where('t.taller = :taller')
           ->setParameter('taller', $taller)
           ->orderBy('t.numeroTurno', 'ASC');

        // Si se proporciona patente, mostrar todos los estados, sino excluir FINALIZADO
        if (!isset($filtros['patente']) || empty($filtros['patente'])) {
            $qb->andWhere('t.estado != :finalizado')
               ->setParameter('finalizado', Turno::ESTADO_FINALIZADO);
        }

        // Filtrar por patente si se proporciona
        if (isset($filtros['patente']) && !empty($filtros['patente'])) {
            $qb->andWhere('t.patente LIKE :patente')
               ->setParameter('patente', '%' . $filtros['patente'] . '%');
        }

        $turnos = $qb->getQuery()->getResult();

        // Formatear datos de citas para respuesta
        return array_map(function(Turno $turno) {
            return [
                'id' => $turno->getId(),
                'numeroTurno' => $turno->getNumeroTurno(),
                'nombreCliente' => $turno->getNombreCliente(),
                'telefono' => $turno->getTelefono(),
                'modeloVehiculo' => $turno->getModeloVehiculo(),
                'patente' => $turno->getPatente(),
                'descripcionProblema' => $turno->getDescripcionProblema(),
                'estado' => $turno->getEstado(),
                'fechaCreacion' => $turno->getFechaCreacion()->format('Y-m-d H:i:s'),
                'fechaInicio' => $turno->getFechaInicio()?->format('Y-m-d H:i:s'),
                'fechaFinalizacion' => $turno->getFechaFinalizacion()?->format('Y-m-d H:i:s')
            ];
        }, $turnos);
    }
}