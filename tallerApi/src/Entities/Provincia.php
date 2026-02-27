<?php
namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'provincia')]
/**
 * Entidad que representa una provincia.
 */
class Provincia
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
    private int $id;

    #[ORM\Column(type: 'string', length: 50)]
    private string $nombre;

    #[ORM\Column(type: 'string', length: 4, unique: true)]
    private string $codigo31662;

    #[ORM\OneToMany(mappedBy: 'provincia', targetEntity: Localidad::class)]
    private Collection $localidades;

    public function __construct()
    {
        $this->localidades = new ArrayCollection();
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

    public function getCodigo31662(): string
    {
        return $this->codigo31662;
    }

    public function setCodigo31662(string $codigo31662): self
    {
        $this->codigo31662 = $codigo31662;
        return $this;
    }

    public function getLocalidades(): Collection
    {
        return $this->localidades;
    }
}
