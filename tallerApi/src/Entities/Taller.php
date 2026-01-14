<?php
namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'talleres')]
/**
 * Entity representing a workshop (Taller) in the system.
 * Manages workshop information such as name, capacity, and relationships to turns and users.
 */
class Taller
{
    /**
     * The unique identifier for the workshop.
     * Auto-generated primary key.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    /**
     * The name of the workshop.
     * Stored as a string with maximum length of 255 characters.
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $nombre;

    /**
     * The maximum capacity of the workshop.
     * Defaults to 3 if not specified.
     */
    #[ORM\Column(type: 'integer')]
    private int $capacidad = 3;

    /**
     * Collection of turns (Turno) associated with this workshop.
     * One-to-many relationship, mapped by 'taller' in Turno entity.
     */
    #[ORM\OneToMany(mappedBy: 'taller', targetEntity: Turno::class)]
    private Collection $turnos;

    /**
     * Collection of users (Usuario) associated with this workshop.
     * One-to-many relationship, mapped by 'taller' in Usuario entity.
     */
    #[ORM\OneToMany(mappedBy: 'taller', targetEntity: Usuario::class)]
    private Collection $usuarios;

    /**
     * Constructor for the Taller entity.
     * Initializes the collections for turnos and usuarios.
     */
    public function __construct()
    {
        $this->turnos = new ArrayCollection();
        $this->usuarios = new ArrayCollection();
    }

    /**
     * Gets the unique identifier of the workshop.
     *
     * @return int The workshop ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets the name of the workshop.
     *
     * @return string The workshop name.
     */
    public function getNombre(): string
    {
        return $this->nombre;
    }

    /**
     * Sets the name of the workshop.
     *
     * @param string $nombre The new name for the workshop.
     * @return self Returns the instance for method chaining.
     */
    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;
        return $this;
    }

    /**
     * Gets the capacity of the workshop.
     *
     * @return int The workshop capacity.
     */
    public function getCapacidad(): int
    {
        return $this->capacidad;
    }

    /**
     * Sets the capacity of the workshop.
     *
     * @param int $capacidad The new capacity for the workshop.
     * @return self Returns the instance for method chaining.
     */
    public function setCapacidad(int $capacidad): self
    {
        $this->capacidad = $capacidad;
        return $this;
    }

    /**
     * Gets the collection of turns associated with this workshop.
     *
     * @return Collection The collection of Turno entities.
     */
    public function getTurnos(): Collection
    {
        return $this->turnos;
    }

    /**
     * Gets the collection of users associated with this workshop.
     *
     * @return Collection The collection of Usuario entities.
     */
    public function getUsuarios(): Collection
    {
        return $this->usuarios;
    }
}