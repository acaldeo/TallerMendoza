<?php
namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'talleres')]
/**
 * Entidad que representa un taller (workshop) en el sistema.
 * Gestiona información del taller como nombre, capacidad, y relaciones con turnos y usuarios.
 *
 * Propósito general:
 * - Representar un taller mecánico en el sistema de gestión de turnos.
 * - Definir la capacidad máxima de turnos simultáneos.
 * - Mantener colecciones de turnos y usuarios asociados.
 *
 * Dependencias:
 * - Tiene una relación uno-a-muchos con Turno (un taller puede tener muchos turnos).
 * - Tiene una relación uno-a-muchos con Usuario (un taller puede tener muchos usuarios administradores).
 * - Es referenciada por ConfiguracionEmail (uno a uno, pero no mapeada aquí).
 *
 * Interacciones con otras capas:
 * - Los servicios como TurnoService y UsuarioService operan sobre las colecciones de turnos y usuarios.
 * - Los controladores (AdminController, TallerController) usan esta entidad para gestionar talleres.
 * - El EntityManager persiste y recupera instancias desde la base de datos.
 */
class Taller
{
    /**
     * El identificador único del taller.
     * Clave primaria generada automáticamente.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    /**
     * El nombre del taller.
     * Almacenado como cadena con longitud máxima de 255 caracteres.
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $nombre;

    /**
     * La ciudad donde se encuentra el taller.
     * Permite distinguir talleres con el mismo nombre en diferentes ubicaciones.
     */
    #[ORM\Column(type: 'string', length: 100)]
    private string $ciudad;

    /**
     * La capacidad máxima del taller.
     * Por defecto 3 si no se especifica.
     */
    #[ORM\Column(type: 'integer')]
    private int $capacidad = 3;

    /**
     * Ruta o nombre del archivo de logo del taller.
     * Almacena el nombre del archivo de imagen del logo.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $logo = null;

    /**
     * Colección de turnos (Turno) asociados con este taller.
     * Relación uno-a-muchos, mapeada por 'taller' en la entidad Turno.
     */
    #[ORM\OneToMany(mappedBy: 'taller', targetEntity: Turno::class)]
    private Collection $turnos;

    /**
     * Colección de usuarios (Usuario) asociados con este taller.
     * Relación uno-a-muchos, mapeada por 'taller' en la entidad Usuario.
     */
    #[ORM\OneToMany(mappedBy: 'taller', targetEntity: Usuario::class)]
    private Collection $usuarios;

    /**
     * Constructor de la entidad Taller.
     * Inicializa las colecciones para turnos y usuarios.
     */
    public function __construct()
    {
        $this->turnos = new ArrayCollection();
        $this->usuarios = new ArrayCollection();
    }

    /**
     * Obtiene el identificador único del taller.
     *
     * @return int El ID del taller.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Obtiene el nombre del taller.
     *
     * @return string El nombre del taller.
     */
    public function getNombre(): string
    {
        return $this->nombre;
    }

    /**
     * Establece el nombre del taller.
     *
     * @param string $nombre El nuevo nombre para el taller.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;
        return $this;
    }

    /**
     * Obtiene la ciudad del taller.
     *
     * @return string La ciudad del taller.
     */
    public function getCiudad(): string
    {
        return $this->ciudad;
    }

    /**
     * Establece la ciudad del taller.
     *
     * @param string $ciudad La ciudad del taller.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setCiudad(string $ciudad): self
    {
        $this->ciudad = $ciudad;
        return $this;
    }

    /**
     * Obtiene la capacidad del taller.
     *
     * @return int La capacidad del taller.
     */
    public function getCapacidad(): int
    {
        return $this->capacidad;
    }

    /**
     * Establece la capacidad del taller.
     *
     * @param int $capacidad La nueva capacidad para el taller.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setCapacidad(int $capacidad): self
    {
        $this->capacidad = $capacidad;
        return $this;
    }

    /**
     * Obtiene la ruta del logo del taller.
     *
     * @return string|null La ruta del logo o null si no tiene.
     */
    public function getLogo(): ?string
    {
        return $this->logo;
    }

    /**
     * Establece la ruta del logo del taller.
     *
     * @param string|null $logo La ruta del logo.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;
        return $this;
    }

    /**
     * Obtiene la colección de turnos asociados con este taller.
     *
     * @return Collection La colección de entidades Turno.
     */
    public function getTurnos(): Collection
    {
        return $this->turnos;
    }

    /**
     * Obtiene la colección de usuarios asociados con este taller.
     *
     * @return Collection La colección de entidades Usuario.
     */
    public function getUsuarios(): Collection
    {
        return $this->usuarios;
    }
}