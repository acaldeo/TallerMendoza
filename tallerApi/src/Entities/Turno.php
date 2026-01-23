<?php
namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use DateTime;

#[ORM\Entity]
#[ORM\Table(name: 'turnos')]
#[ORM\Index(name: 'idx_taller_estado', columns: ['taller_id', 'estado'])]
#[ORM\Index(name: 'idx_taller_numero', columns: ['taller_id', 'numeroTurno'])]
#[ORM\UniqueConstraint(name: 'unique_taller_numero', columns: ['taller_id', 'numeroTurno'])]
/**
 * Entidad que representa un turno (cita) en un taller.
 * Gestiona los detalles del turno incluyendo información del cliente, vehículo, descripción del problema y estado.
 *
 * Propósito general:
 * - Representar una cita de servicio en un taller mecánico.
 * - Almacenar datos del cliente, vehículo y problema reportado.
 * - Gestionar el estado del turno a lo largo de su ciclo de vida (espera, en taller, finalizado).
 * - Mantener un registro temporal de creación, inicio y finalización.
 *
 * Dependencias:
 * - Depende de la entidad Taller (relación muchos-a-uno).
 * - Es utilizada por TurnoService para operaciones de negocio.
 * - Los controladores (TallerController, AdminController) interactúan con esta entidad a través de servicios.
 * - El estado se valida y cambia según reglas de negocio definidas en los servicios.
 *
 * Interacciones con otras capas:
 * - La capa de servicios (TurnoService) maneja la lógica de creación, actualización y consulta de turnos.
 * - Los validadores (TurnoValidator) verifican los datos antes de asignarlos a esta entidad.
 * - El EntityManager (de Doctrine) persiste y recupera instancias de esta entidad desde la base de datos.
 */
class Turno
{
    /**
     * Constantes que definen los posibles estados de un turno.
     */
    public const ESTADO_EN_TALLER = 'EN_TALLER';
    public const ESTADO_EN_ESPERA = 'EN_ESPERA';
    public const ESTADO_FINALIZADO = 'FINALIZADO';

    /**
     * El identificador único del turno.
     * Clave primaria generada automáticamente.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    /**
     * El taller asociado con este turno.
     * Relación muchos-a-uno con la entidad Taller, no puede ser nula.
     */
    #[ORM\ManyToOne(targetEntity: Taller::class, inversedBy: 'turnos')]
    #[ORM\JoinColumn(nullable: false)]
    private Taller $taller;

    /**
     * El número de turno dentro del taller.
     * Único por taller.
     */
    #[ORM\Column(type: 'integer')]
    private int $numeroTurno;

    /**
     * El nombre del cliente para este turno.
     * Almacenado como cadena con longitud máxima de 255.
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $nombreCliente;

    /**
     * El número de teléfono del cliente.
     * Almacenado como cadena con longitud máxima de 20.
     */
    #[ORM\Column(type: 'string', length: 20)]
    private string $telefono;

    /**
     * El modelo del vehículo para este turno.
     * Almacenado como cadena con longitud máxima de 255.
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $modeloVehiculo;

    /**
     * La patente (placa) del vehículo para este turno.
     * Almacenada como cadena con longitud máxima de 10, puede ser nula.
     */
    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private string $patente;

    /**
     * Descripción del problema con el vehículo.
     * Almacenada como texto, permitiendo descripciones más largas.
     */
    #[ORM\Column(type: 'text')]
    private string $descripcionProblema;

    /**
     * El estado actual del turno.
     * Utiliza constantes: EN_ESPERA, EN_TALLER, FINALIZADO.
     */
    #[ORM\Column(type: 'string', length: 20)]
    private string $estado;

    /**
     * La fecha y hora cuando el turno fue creado.
     * Se establece automáticamente en el constructor.
     */
    #[ORM\Column(type: 'datetime')]
    private DateTime $fechaCreacion;

    /**
     * La fecha y hora cuando el trabajo en el turno comenzó.
     * Se establece automáticamente cuando el estado cambia a EN_TALLER.
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $fechaInicio = null;

    /**
     * La fecha y hora cuando el turno fue completado.
     * Se establece automáticamente cuando el estado cambia a FINALIZADO.
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $fechaFinalizacion = null;

    /**
     * Constructor de la entidad Turno.
     * Inicializa la fecha de creación a la hora actual y el estado a EN_ESPERA.
     */
    public function __construct()
    {
        $this->fechaCreacion = new DateTime();
        $this->estado = self::ESTADO_EN_ESPERA;
    }

    /**
     * Obtiene el identificador único del turno.
     *
     * @return int El ID del turno.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Obtiene el taller asociado con este turno.
     *
     * @return Taller La entidad Taller.
     */
    public function getTaller(): Taller
    {
        return $this->taller;
    }

    /**
     * Establece el taller para este turno.
     *
     * @param Taller $taller La entidad Taller.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setTaller(Taller $taller): self
    {
        $this->taller = $taller;
        return $this;
    }

    /**
     * Obtiene el número de turno.
     *
     * @return int El número de turno.
     */
    public function getNumeroTurno(): int
    {
        return $this->numeroTurno;
    }

    /**
     * Establece el número de turno.
     *
     * @param int $numeroTurno El número de turno.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setNumeroTurno(int $numeroTurno): self
    {
        $this->numeroTurno = $numeroTurno;
        return $this;
    }

    /**
     * Obtiene el nombre del cliente.
     *
     * @return string El nombre del cliente.
     */
    public function getNombreCliente(): string
    {
        return $this->nombreCliente;
    }

    /**
     * Establece el nombre del cliente.
     *
     * @param string $nombreCliente El nombre del cliente.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setNombreCliente(string $nombreCliente): self
    {
        $this->nombreCliente = $nombreCliente;
        return $this;
    }

    /**
     * Obtiene el número de teléfono del cliente.
     *
     * @return string El número de teléfono.
     */
    public function getTelefono(): string
    {
        return $this->telefono;
    }

    /**
     * Establece el número de teléfono del cliente.
     *
     * @param string $telefono El número de teléfono.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setTelefono(string $telefono): self
    {
        $this->telefono = $telefono;
        return $this;
    }

    /**
     * Obtiene el modelo del vehículo.
     *
     * @return string El modelo del vehículo.
     */
    public function getModeloVehiculo(): string
    {
        return $this->modeloVehiculo;
    }

    /**
     * Establece el modelo del vehículo.
     *
     * @param string $modeloVehiculo El modelo del vehículo.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setModeloVehiculo(string $modeloVehiculo): self
    {
        $this->modeloVehiculo = $modeloVehiculo;
        return $this;
    }

    /**
     * Obtiene la patente (placa) del vehículo.
     *
     * @return string|null La patente.
     */
    public function getPatente(): ?string
    {
        return $this->patente;
    }

    /**
     * Establece la patente (placa) del vehículo.
     *
     * @param string|null $patente La patente.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setPatente(?string $patente): self
    {
        $this->patente = $patente;
        return $this;
    }

    /**
     * Obtiene la descripción del problema.
     *
     * @return string La descripción del problema.
     */
    public function getDescripcionProblema(): string
    {
        return $this->descripcionProblema;
    }

    /**
     * Establece la descripción del problema.
     *
     * @param string $descripcionProblema La descripción del problema.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setDescripcionProblema(string $descripcionProblema): self
    {
        $this->descripcionProblema = $descripcionProblema;
        return $this;
    }

    /**
     * Obtiene el estado actual del turno.
     *
     * @return string El estado (uno de las constantes ESTADO_*).
     */
    public function getEstado(): string
    {
        return $this->estado;
    }

    /**
     * Establece el estado del turno.
     * Actualiza automáticamente fechaInicio al transitar a EN_TALLER,
     * y fechaFinalizacion al transitar a FINALIZADO.
     *
     * @param string $estado El nuevo estado.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setEstado(string $estado): self
    {
        $this->estado = $estado;
        if ($estado === self::ESTADO_EN_TALLER && !$this->fechaInicio) {
            $this->fechaInicio = new DateTime();
        } elseif ($estado === self::ESTADO_FINALIZADO && !$this->fechaFinalizacion) {
            $this->fechaFinalizacion = new DateTime();
        }
        return $this;
    }

    /**
     * Obtiene la fecha y hora de creación del turno.
     *
     * @return DateTime La marca de tiempo de creación.
     */
    public function getFechaCreacion(): DateTime
    {
        return $this->fechaCreacion;
    }

    /**
     * Obtiene la fecha y hora de inicio del turno.
     *
     * @return DateTime|null La marca de tiempo de inicio, o null si no ha empezado.
     */
    public function getFechaInicio(): ?DateTime
    {
        return $this->fechaInicio;
    }

    /**
     * Obtiene la fecha y hora de finalización del turno.
     *
     * @return DateTime|null La marca de tiempo de finalización, o null si no ha finalizado.
     */
    public function getFechaFinalizacion(): ?DateTime
    {
        return $this->fechaFinalizacion;
    }
}