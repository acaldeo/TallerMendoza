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
     * The client's phone number.
     * Stored as a string with max length 20.
     */
    #[ORM\Column(type: 'string', length: 20)]
    private string $telefono;

    /**
     * The model of the vehicle for this turn.
      * Stored as a string with max length 255.
      */
     #[ORM\Column(type: 'string', length: 255)]
     private string $modeloVehiculo;

     /**
      * The license plate of the vehicle for this turn.
      * Stored as a string with max length 10.
      */
     #[ORM\Column(type: 'string', length: 10, nullable: true)]
     private string $patente;
     
    /**
     * Description of the problem with the vehicle.
     * Stored as text, allowing longer descriptions.
     */
    #[ORM\Column(type: 'text')]
    private string $descripcionProblema;

    /**
     * The current state of the turn.
     * Uses constants: EN_ESPERA, EN_TALLER, FINALIZADO.
     */
    #[ORM\Column(type: 'string', length: 20)]
    private string $estado;

    /**
     * The date and time when the turn was created.
     * Automatically set in constructor.
     */
    #[ORM\Column(type: 'datetime')]
    private DateTime $fechaCreacion;

    /**
     * The date and time when work on the turn started.
     * Set automatically when state changes to EN_TALLER.
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $fechaInicio = null;

    /**
     * The date and time when the turn was completed.
     * Set automatically when state changes to FINALIZADO.
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $fechaFinalizacion = null;

    /**
     * Constructor for the Turno entity.
     * Initializes creation date to current time and state to EN_ESPERA.
     */
    public function __construct()
    {
        $this->fechaCreacion = new DateTime();
        $this->estado = self::ESTADO_EN_ESPERA;
    }

    /**
     * Gets the unique identifier of the turn.
     *
     * @return int The turn ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets the workshop associated with this turn.
     *
     * @return Taller The Taller entity.
     */
    public function getTaller(): Taller
    {
        return $this->taller;
    }

    /**
     * Sets the workshop for this turn.
     *
     * @param Taller $taller The Taller entity.
     * @return self Returns the instance for method chaining.
     */
    public function setTaller(Taller $taller): self
    {
        $this->taller = $taller;
        return $this;
    }

    /**
     * Gets the turn number.
     *
     * @return int The turn number.
     */
    public function getNumeroTurno(): int
    {
        return $this->numeroTurno;
    }

    /**
     * Sets the turn number.
     *
     * @param int $numeroTurno The turn number.
     * @return self Returns the instance for method chaining.
     */
    public function setNumeroTurno(int $numeroTurno): self
    {
        $this->numeroTurno = $numeroTurno;
        return $this;
    }

    /**
     * Gets the client's name.
     *
     * @return string The client's name.
     */
    public function getNombreCliente(): string
    {
        return $this->nombreCliente;
    }

    /**
     * Sets the client's name.
     *
     * @param string $nombreCliente The client's name.
     * @return self Returns the instance for method chaining.
     */
    public function setNombreCliente(string $nombreCliente): self
    {
        $this->nombreCliente = $nombreCliente;
        return $this;
    }

    /**
     * Gets the client's phone number.
     *
     * @return string The phone number.
     */
    public function getTelefono(): string
    {
        return $this->telefono;
    }

    /**
     * Sets the client's phone number.
     *
     * @param string $telefono The phone number.
     * @return self Returns the instance for method chaining.
     */
    public function setTelefono(string $telefono): self
    {
        $this->telefono = $telefono;
        return $this;
    }

    /**
     * Gets the vehicle model.
     *
     * @return string The vehicle model.
     */
    public function getModeloVehiculo(): string
    {
        return $this->modeloVehiculo;
    }

    /**
     * Sets the vehicle model.
     *
     * @param string $modeloVehiculo The vehicle model.
     * @return self Returns the instance for method chaining.
     */
    public function setModeloVehiculo(string $modeloVehiculo): self
    {
        $this->modeloVehiculo = $modeloVehiculo;
        return $this;
    }

    /**
     * Gets the vehicle license plate.
     *
     * @return string|null The license plate.
     */
    public function getPatente(): ?string
    {
        return $this->patente;
    }

    /**
     * Sets the vehicle license plate.
     *
     * @param string|null $patente The license plate.
     * @return self Returns the instance for method chaining.
     */
    public function setPatente(?string $patente): self
    {
        $this->patente = $patente;
        return $this;
    }

    /**
     * Gets the problem description.
     *
     * @return string The problem description.
     */
    public function getDescripcionProblema(): string
    {
        return $this->descripcionProblema;
    }

    /**
     * Sets the problem description.
     *
     * @param string $descripcionProblema The problem description.
     * @return self Returns the instance for method chaining.
     */
    public function setDescripcionProblema(string $descripcionProblema): self
    {
        $this->descripcionProblema = $descripcionProblema;
        return $this;
    }

    /**
     * Gets the current state of the turn.
     *
     * @return string The state (one of the ESTADO_* constants).
     */
    public function getEstado(): string
    {
        return $this->estado;
    }

    /**
     * Sets the state of the turn.
     * Automatically updates fechaInicio when transitioning to EN_TALLER,
     * and fechaFinalizacion when transitioning to FINALIZADO.
     *
     * @param string $estado The new state.
     * @return self Returns the instance for method chaining.
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
     * Gets the creation date and time of the turn.
     *
     * @return DateTime The creation timestamp.
     */
    public function getFechaCreacion(): DateTime
    {
        return $this->fechaCreacion;
    }

    /**
     * Gets the start date and time of the turn.
     *
     * @return DateTime|null The start timestamp, or null if not started.
     */
    public function getFechaInicio(): ?DateTime
    {
        return $this->fechaInicio;
    }

    /**
     * Gets the completion date and time of the turn.
     *
     * @return DateTime|null The completion timestamp, or null if not completed.
     */
    public function getFechaFinalizacion(): ?DateTime
    {
        return $this->fechaFinalizacion;
    }
}