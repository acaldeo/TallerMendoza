<?php
namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * Entidad que representa la configuración de email para un taller.
 * Gestiona los ajustes SMTP y preferencias de notificación para un taller específico.
 *
 * Propósito general:
 * - Almacenar la configuración necesaria para enviar emails desde el sistema (notificaciones de turnos).
 * - Permitir a los administradores configurar servidores SMTP personalizados.
 * - Gestionar si las notificaciones están activas o no.
 * - Mantener un registro de creación y actualización de la configuración.
 *
 * Dependencias:
 * - Depende de la entidad Taller (relación uno-a-uno o muchos-a-uno, pero en práctica uno por taller).
 * - Es utilizada por ConfiguracionService para operaciones de configuración.
 * - EmailService usa esta configuración para enviar emails.
 * - Los controladores (AdminController) interactúan con esta entidad a través de servicios.
 *
 * Interacciones con otras capas:
 * - La capa de servicios (ConfiguracionService) maneja la lógica de guardar y obtener configuraciones.
 * - EmailService valida y utiliza los datos SMTP para enviar correos.
 * - Los validadores pueden verificar la integridad de los datos antes de asignarlos.
 * - El EntityManager persiste y recupera instancias desde la base de datos.
 */
#[ORM\Entity]
#[ORM\Table(name: 'configuracion_email')]
#[ORM\HasLifecycleCallbacks]
class ConfiguracionEmail
{
    /**
     * El identificador único para la configuración de email.
     * Clave primaria generada automáticamente.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    /**
     * El taller asociado con esta configuración de email.
     * Relación muchos-a-uno con la entidad Taller, no puede ser nula.
     */
    #[ORM\ManyToOne(targetEntity: Taller::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Taller $taller;

    /**
     * La dirección de email a la que enviar notificaciones.
     * Almacenada como cadena con longitud máxima de 255 caracteres.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $emailDestino = null;

    /**
     * La dirección de email desde la que enviar.
     * Almacenada como cadena con longitud máxima de 255 caracteres.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $emailOrigen = null;

    /**
     * El nombre desde el que enviar.
     * Almacenado como cadena con longitud máxima de 255 caracteres.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $nombreOrigen = null;

    /**
     * Bandera para habilitar o deshabilitar notificaciones.
     * Por defecto es false.
     */
    #[ORM\Column(type: 'boolean')]
    private bool $activo = false;

    /**
     * El host del servidor SMTP.
     * Nulo por defecto, almacenado como cadena con longitud máxima de 255 caracteres.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $smtpHost = null;

    /**
     * El puerto del servidor SMTP.
     * Nulo por defecto, por defecto 587.
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $smtpPort = 587;

    /**
     * El nombre de usuario SMTP.
     * Nulo por defecto, almacenado como cadena con longitud máxima de 255 caracteres.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $smtpUsuario = null;

    /**
     * La contraseña SMTP.
     * Nula por defecto, almacenada como cadena con longitud máxima de 255 caracteres.
     * Nota: En producción, esto debería estar encriptado.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $smtpPassword = null;

    /**
     * La fecha y hora cuando la configuración fue creada.
     * Se establece automáticamente en el constructor.
     */
    #[ORM\Column(type: 'datetime')]
    private DateTime $createdAt;

    /**
     * La fecha y hora cuando la configuración fue actualizada por última vez.
     * Se actualiza automáticamente vía callback preUpdate.
     */
    #[ORM\Column(type: 'datetime')]
    private DateTime $updatedAt;

    /**
     * Constructor de la entidad ConfiguracionEmail.
     * Inicializa createdAt y updatedAt a la hora actual.
     */
    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    /**
     * Callback de ciclo de vida pre-update.
     * Actualiza la marca de tiempo updatedAt antes de persistir cambios.
     */
    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    /**
     * Obtiene el identificador único de la configuración de email.
     *
     * @return int El ID de la configuración.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Obtiene el taller asociado con esta configuración.
     *
     * @return Taller La entidad Taller.
     */
    public function getTaller(): Taller
    {
        return $this->taller;
    }

    /**
     * Establece el taller para esta configuración.
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
     * Obtiene la dirección de email de destino.
     *
     * @return string|null La dirección de email.
     */
    public function getEmailDestino(): ?string
    {
        return $this->emailDestino;
    }

    /**
     * Establece la dirección de email de destino.
     *
     * @param string|null $emailDestino La dirección de email.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setEmailDestino(?string $emailDestino): self
    {
        $this->emailDestino = $emailDestino;
        return $this;
    }

    /**
     * Obtiene la dirección de email de origen.
     *
     * @return string|null La dirección de email.
     */
    public function getEmailOrigen(): ?string
    {
        return $this->emailOrigen;
    }

    /**
     * Establece la dirección de email de origen.
     *
     * @param string|null $emailOrigen La dirección de email.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setEmailOrigen(?string $emailOrigen): self
    {
        $this->emailOrigen = $emailOrigen;
        return $this;
    }

    /**
     * Obtiene el nombre de origen.
     *
     * @return string|null El nombre.
     */
    public function getNombreOrigen(): ?string
    {
        return $this->nombreOrigen;
    }

    /**
     * Establece el nombre de origen.
     *
     * @param string|null $nombreOrigen El nombre.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setNombreOrigen(?string $nombreOrigen): self
    {
        $this->nombreOrigen = $nombreOrigen;
        return $this;
    }

    /**
     * Obtiene la bandera activa.
     *
     * @return bool True si las notificaciones están activas, false en caso contrario.
     */
    public function isActivo(): bool
    {
        return $this->activo;
    }

    /**
     * Establece la bandera activa.
     *
     * @param bool $activo La bandera activa.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setActivo(bool $activo): self
    {
        $this->activo = $activo;
        return $this;
    }

    /**
     * Obtiene el host SMTP.
     *
     * @return string|null El host SMTP, o null si no está establecido.
     */
    public function getSmtpHost(): ?string
    {
        return $this->smtpHost;
    }

    /**
     * Establece el host SMTP.
     *
     * @param string|null $smtpHost El host SMTP.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setSmtpHost(?string $smtpHost): self
    {
        $this->smtpHost = $smtpHost;
        return $this;
    }

    /**
     * Obtiene el puerto SMTP.
     *
     * @return int|null El puerto SMTP, o null si no está establecido.
     */
    public function getSmtpPort(): ?int
    {
        return $this->smtpPort;
    }

    /**
     * Establece el puerto SMTP.
     *
     * @param int|null $smtpPort El puerto SMTP.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setSmtpPort(?int $smtpPort): self
    {
        $this->smtpPort = $smtpPort;
        return $this;
    }

    /**
     * Obtiene el nombre de usuario SMTP.
     *
     * @return string|null El nombre de usuario SMTP, o null si no está establecido.
     */
    public function getSmtpUsuario(): ?string
    {
        return $this->smtpUsuario;
    }

    /**
     * Establece el nombre de usuario SMTP.
     *
     * @param string|null $smtpUsuario El nombre de usuario SMTP.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setSmtpUsuario(?string $smtpUsuario): self
    {
        $this->smtpUsuario = $smtpUsuario;
        return $this;
    }

    /**
     * Obtiene la contraseña SMTP.
     *
     * @return string|null La contraseña SMTP, o null si no está establecida.
     */
    public function getSmtpPassword(): ?string
    {
        return $this->smtpPassword;
    }

    /**
     * Establece la contraseña SMTP.
     *
     * @param string|null $smtpPassword La contraseña SMTP.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setSmtpPassword(?string $smtpPassword): self
    {
        $this->smtpPassword = $smtpPassword;
        return $this;
    }

    /**
     * Obtiene la fecha y hora de creación.
     *
     * @return DateTime La marca de tiempo de creación.
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * Obtiene la fecha y hora de la última actualización.
     *
     * @return DateTime La marca de tiempo de la última actualización.
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }
}