<?php
namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * Entity representing email configuration for a workshop.
 * Manages SMTP settings and notification preferences for a specific workshop.
 */
#[ORM\Entity]
#[ORM\Table(name: 'configuracion_email')]
#[ORM\HasLifecycleCallbacks]
class ConfiguracionEmail
{
    /**
     * The unique identifier for the email configuration.
     * Auto-generated primary key.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    /**
     * The workshop associated with this email configuration.
     * Many-to-one relationship with Taller entity, cannot be null.
     */
    #[ORM\ManyToOne(targetEntity: Taller::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Taller $taller;

    /**
     * The email address to send notifications to.
     * Stored as a string with maximum length of 255 characters.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $emailDestino = null;

    /**
     * The email address to send from.
     * Stored as a string with maximum length of 255 characters.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $emailOrigen = null;

    /**
     * The name to send from.
     * Stored as a string with maximum length of 255 characters.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $nombreOrigen = null;

    /**
     * Flag to enable or disable notifications.
     * Defaults to false.
     */
    #[ORM\Column(type: 'boolean')]
    private bool $activo = false;

    /**
     * The SMTP server host.
     * Nullable, stored as a string with maximum length of 255 characters.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $smtpHost = null;

    /**
     * The SMTP server port.
     * Nullable, defaults to 587.
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $smtpPort = 587;

    /**
     * The SMTP username.
     * Nullable, stored as a string with maximum length of 255 characters.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $smtpUsuario = null;

    /**
     * The SMTP password.
     * Nullable, stored as a string with maximum length of 255 characters.
     * Note: In production, this should be encrypted.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $smtpPassword = null;

    /**
     * The date and time when the configuration was created.
     * Automatically set in constructor.
     */
    #[ORM\Column(type: 'datetime')]
    private DateTime $createdAt;

    /**
     * The date and time when the configuration was last updated.
     * Automatically updated via preUpdate callback.
     */
    #[ORM\Column(type: 'datetime')]
    private DateTime $updatedAt;

    /**
     * Constructor for the ConfiguracionEmail entity.
     * Initializes createdAt and updatedAt to current time.
     */
    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    /**
     * Pre-update lifecycle callback.
     * Updates the updatedAt timestamp before persisting changes.
     */
    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    /**
     * Gets the unique identifier of the email configuration.
     *
     * @return int The configuration ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets the workshop associated with this configuration.
     *
     * @return Taller The Taller entity.
     */
    public function getTaller(): Taller
    {
        return $this->taller;
    }

    /**
     * Sets the workshop for this configuration.
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
     * Gets the destination email address.
     *
     * @return string|null The email address.
     */
    public function getEmailDestino(): ?string
    {
        return $this->emailDestino;
    }

    /**
     * Sets the destination email address.
     *
     * @param string|null $emailDestino The email address.
     * @return self Returns the instance for method chaining.
     */
    public function setEmailDestino(?string $emailDestino): self
    {
        $this->emailDestino = $emailDestino;
        return $this;
    }

    /**
     * Gets the origin email address.
     *
     * @return string|null The email address.
     */
    public function getEmailOrigen(): ?string
    {
        return $this->emailOrigen;
    }

    /**
     * Sets the origin email address.
     *
     * @param string|null $emailOrigen The email address.
     * @return self Returns the instance for method chaining.
     */
    public function setEmailOrigen(?string $emailOrigen): self
    {
        $this->emailOrigen = $emailOrigen;
        return $this;
    }

    /**
     * Gets the origin name.
     *
     * @return string|null The name.
     */
    public function getNombreOrigen(): ?string
    {
        return $this->nombreOrigen;
    }

    /**
     * Sets the origin name.
     *
     * @param string|null $nombreOrigen The name.
     * @return self Returns the instance for method chaining.
     */
    public function setNombreOrigen(?string $nombreOrigen): self
    {
        $this->nombreOrigen = $nombreOrigen;
        return $this;
    }

    /**
     * Gets the active flag.
     *
     * @return bool True if notifications are active, false otherwise.
     */
    public function isActivo(): bool
    {
        return $this->activo;
    }

    /**
     * Sets the active flag.
     *
     * @param bool $activo The active flag.
     * @return self Returns the instance for method chaining.
     */
    public function setActivo(bool $activo): self
    {
        $this->activo = $activo;
        return $this;
    }

    /**
     * Gets the SMTP host.
     *
     * @return string|null The SMTP host, or null if not set.
     */
    public function getSmtpHost(): ?string
    {
        return $this->smtpHost;
    }

    /**
     * Sets the SMTP host.
     *
     * @param string|null $smtpHost The SMTP host.
     * @return self Returns the instance for method chaining.
     */
    public function setSmtpHost(?string $smtpHost): self
    {
        $this->smtpHost = $smtpHost;
        return $this;
    }

    /**
     * Gets the SMTP port.
     *
     * @return int|null The SMTP port, or null if not set.
     */
    public function getSmtpPort(): ?int
    {
        return $this->smtpPort;
    }

    /**
     * Sets the SMTP port.
     *
     * @param int|null $smtpPort The SMTP port.
     * @return self Returns the instance for method chaining.
     */
    public function setSmtpPort(?int $smtpPort): self
    {
        $this->smtpPort = $smtpPort;
        return $this;
    }

    /**
     * Gets the SMTP username.
     *
     * @return string|null The SMTP username, or null if not set.
     */
    public function getSmtpUsuario(): ?string
    {
        return $this->smtpUsuario;
    }

    /**
     * Sets the SMTP username.
     *
     * @param string|null $smtpUsuario The SMTP username.
     * @return self Returns the instance for method chaining.
     */
    public function setSmtpUsuario(?string $smtpUsuario): self
    {
        $this->smtpUsuario = $smtpUsuario;
        return $this;
    }

    /**
     * Gets the SMTP password.
     *
     * @return string|null The SMTP password, or null if not set.
     */
    public function getSmtpPassword(): ?string
    {
        return $this->smtpPassword;
    }

    /**
     * Sets the SMTP password.
     *
     * @param string|null $smtpPassword The SMTP password.
     * @return self Returns the instance for method chaining.
     */
    public function setSmtpPassword(?string $smtpPassword): self
    {
        $this->smtpPassword = $smtpPassword;
        return $this;
    }

    /**
     * Gets the creation date and time.
     *
     * @return DateTime The creation timestamp.
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * Gets the last update date and time.
     *
     * @return DateTime The last update timestamp.
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }
}