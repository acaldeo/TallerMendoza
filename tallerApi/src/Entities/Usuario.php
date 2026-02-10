<?php
namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'usuarios')]
#[ORM\UniqueConstraint(name: 'unique_usuario', columns: ['usuario'])]
/**
 * Entidad que representa un usuario en el sistema.
 * Gestiona la autenticación de usuarios y la asociación con un taller.
 *
 * Propósito general:
 * - Representar a un administrador o usuario autorizado para gestionar un taller.
 * - Almacenar credenciales de autenticación (usuario y contraseña hasheada).
 * - Vincular usuarios a talleres específicos para control de acceso.
 *
 * Dependencias:
 * - Depende de la entidad Taller (relación muchos-a-uno).
 * - Es utilizada por AuthService para verificar credenciales.
 * - Los controladores (AdminController) usan esta entidad para login y gestión de usuarios.
 *
 * Interacciones con otras capas:
 * - La capa de servicios (AuthService, UsuarioService) maneja la lógica de autenticación y gestión.
 * - Los validadores (AuthValidator, UsuarioValidator) verifican datos antes de asignarlos.
 * - El middleware (AuthMiddleware) usa esta entidad para verificar sesiones.
 * - El EntityManager persiste y recupera instancias desde la base de datos.
 */
class Usuario
{
    /**
     * El identificador único del usuario.
     * Clave primaria generada automáticamente.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    /**
     * El taller asociado con este usuario.
     * Relación muchos-a-uno con la entidad Taller, puede ser nula para super usuarios.
     */
    #[ORM\ManyToOne(targetEntity: Taller::class, inversedBy: 'usuarios')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Taller $taller = null;

    /**
     * El nombre de usuario para autenticación.
     * Debe ser único en todo el sistema.
     */
    #[ORM\Column(type: 'string', length: 100)]
    private string $usuario;

    /**
     * La contraseña hasheada del usuario.
     * Almacenada de forma segura utilizando hash de contraseñas.
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $passwordHash;

    /**
     * El rol del usuario en el sistema.
     * Valores posibles: 'empleado', 'admin', 'super'.
     * Los super usuarios pueden crear/eliminar talleres sin tener uno asignado.
     */
    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'empleado'])]
    private string $rol = 'empleado';

    /**
     * Obtiene el identificador único del usuario.
     *
     * @return int El ID del usuario.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Obtiene el taller asociado con este usuario.
     *
     * @return Taller|null El taller asociado o null si no tiene.
     */
    public function getTaller(): ?Taller
    {
        return $this->taller;
    }

    /**
     * Establece el taller para este usuario.
     *
     * @param Taller|null $taller La entidad Taller o null.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setTaller(?Taller $taller): self
    {
        $this->taller = $taller;
        return $this;
    }

    /**
     * Obtiene el nombre de usuario.
     *
     * @return string El nombre de usuario.
     */
    public function getUsuario(): string
    {
        return $this->usuario;
    }

    /**
     * Establece el nombre de usuario.
     *
     * @param string $usuario El nombre de usuario.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setUsuario(string $usuario): self
    {
        $this->usuario = $usuario;
        return $this;
    }

    /**
     * Obtiene el hash de la contraseña.
     *
     * @return string La contraseña hasheada.
     */
    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    /**
     * Establece el hash de la contraseña.
     *
     * @param string $passwordHash La contraseña hasheada.
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setPasswordHash(string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    /**
     * Obtiene el rol del usuario.
     *
     * @return string El rol del usuario ('empleado', 'admin', 'super').
     */
    public function getRol(): string
    {
        return $this->rol;
    }

    /**
     * Establece el rol del usuario.
     *
     * @param string $rol El nuevo rol ('empleado', 'admin', 'super').
     * @return self Retorna la instancia para encadenamiento de métodos.
     */
    public function setRol(string $rol): self
    {
        $this->rol = $rol;
        return $this;
    }

    /**
     * Verifica si la contraseña proporcionada coincide con el hash almacenado.
     *
     * @param string $password La contraseña en texto plano a verificar.
     * @return bool True si la contraseña es correcta, false en caso contrario.
     */
    public function verificarPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }
}