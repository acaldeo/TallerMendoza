<?php
namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'localidad')]
/**
 * Entidad que representa una localidad/ciudad.
 */
class Localidad
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $id;

    #[ORM\Column(type: 'string', length: 50)]
    private string $nombre;

    #[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
    private int $codigopostal;

    #[ORM\ManyToOne(targetEntity: Provincia::class, inversedBy: 'localidades')]
    #[ORM\JoinColumn(name: 'provincia_id', referencedColumnName: 'id', nullable: false)]
    private Provincia $provincia;

    #[ORM\OneToMany(mappedBy: 'localidad', targetEntity: Taller::class)]
    private Collection $talleres;

    public function __construct()
    {
        $this->talleres = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getCodigopostal(): int
    {
        return $this->codigopostal;
    }

    public function setCodigopostal(int $codigopostal): self
    {
        $this->codigopostal = $codigopostal;
        return $this;
    }

    public function getProvincia(): Provincia
    {
        return $this->provincia;
    }

    public function setProvincia(Provincia $provincia): self
    {
        $this->provincia = $provincia;
        return $this;
    }

    public function getTalleres(): Collection
    {
        return $this->talleres;
    }
}
