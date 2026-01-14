<?php
namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'usuarios')]
#[ORM\UniqueConstraint(name: 'unique_usuario', columns: ['usuario'])]
/**
 * Entity representing a user in the system.
 * Manages user authentication and association with a workshop.
 */
class Usuario
{
    /**
     * The unique identifier for the user.
     * Auto-generated primary key.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    /**
     * The workshop associated with this user.
     * Many-to-one relationship with Taller entity, cannot be null.
     */
    #[ORM\ManyToOne(targetEntity: Taller::class, inversedBy: 'usuarios')]
    #[ORM\JoinColumn(nullable: false)]
    private Taller $taller;

    /**
     * The username for authentication.
     * Must be unique across the system.
     */
    #[ORM\Column(type: 'string', length: 100)]
    private string $usuario;

    /**
     * The hashed password for the user.
     * Stored securely using password hashing.
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $passwordHash;

    /**
     * Gets the unique identifier of the user.
     *
     * @return int The user ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets the workshop associated with this user.
     *
     * @return Taller The Taller entity.
     */
    public function getTaller(): Taller
    {
        return $this->taller;
    }

    /**
     * Sets the workshop for this user.
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
     * Gets the username.
     *
     * @return string The username.
     */
    public function getUsuario(): string
    {
        return $this->usuario;
    }

    /**
     * Sets the username.
     *
     * @param string $usuario The username.
     * @return self Returns the instance for method chaining.
     */
    public function setUsuario(string $usuario): self
    {
        $this->usuario = $usuario;
        return $this;
    }

    /**
     * Gets the password hash.
     *
     * @return string The hashed password.
     */
    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    /**
     * Sets the password hash.
     *
     * @param string $passwordHash The hashed password.
     * @return self Returns the instance for method chaining.
     */
    public function setPasswordHash(string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    /**
     * Verifies if the provided password matches the stored hash.
     *
     * @param string $password The plain text password to verify.
     * @return bool True if the password is correct, false otherwise.
     */
    public function verificarPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }
}