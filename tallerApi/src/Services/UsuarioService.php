<?php
/**
 * Service class for managing administrative users in the system.
 *
 * Handles the creation, listing, updating, and deletion of users.
 * Ensures user uniqueness and proper workshop associations.
 */

namespace App\Services;

use App\Entities\Taller;
use App\Entities\Usuario;
use Doctrine\ORM\EntityManager;
use Exception;

class UsuarioService
{
    /** @var EntityManager Doctrine EntityManager for database operations */
    private EntityManager $em;

    /**
     * Constructor to inject the Doctrine EntityManager dependency.
     *
     * @param EntityManager $em The EntityManager instance for database interactions.
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Lists all users for a specific workshop.
     *
     * @param int $tallerId The ID of the workshop whose users to list.
     * @return array Array of user data arrays.
     * @throws Exception If the workshop is not found.
     */
    public function listarUsuarios(int $tallerId): array
    {
        // Find the workshop entity
        $taller = $this->em->find(Taller::class, $tallerId);
        if (!$taller) {
            throw new Exception('Workshop not found');
        }

        // Query users for this workshop
        $usuarios = $this->em->createQuery(
            'SELECT u FROM App\Entities\Usuario u WHERE u.taller = :taller ORDER BY u.usuario ASC'
        )->setParameter('taller', $taller)->getResult();

        // Format user data for response
        return array_map(function(Usuario $usuario) {
            $taller = $usuario->getTaller();
            return [
                'id' => $usuario->getId(),
                'usuario' => $usuario->getUsuario(),
                'tallerId' => $taller ? $taller->getId() : null,
                'tallerNombre' => $taller ? $taller->getNombre() : null
            ];
        }, $usuarios);
    }

    /**
     * Creates a new administrative user for a workshop.
     *
     * @param int $tallerId The ID of the workshop where the user will be created.
     * @param array $datos Array containing user data: usuario, password.
     * @return Usuario The newly created user entity.
     * @throws Exception If the workshop is not found or user already exists.
     */
    public function crearUsuario(int $tallerId, array $datos): Usuario
    {
        // Find the workshop entity
        $taller = $this->em->find(Taller::class, $tallerId);
        if (!$taller) {
            throw new Exception('Workshop not found');
        }

        // Check if user already exists
        $existeUsuario = $this->em->getRepository(Usuario::class)
            ->findOneBy(['usuario' => $datos['usuario']]);

        if ($existeUsuario) {
            throw new Exception('User already exists');
        }

        // Create new user entity
        $usuario = new Usuario();
        $usuario->setTaller($taller)
               ->setUsuario($datos['usuario'])
               ->setPasswordHash(password_hash($datos['password'], PASSWORD_DEFAULT));

        // Persist and flush the new user
        $this->em->persist($usuario);
        $this->em->flush();

        return $usuario;
    }

    /**
     * Updates the password of a specific user.
     *
     * @param int $usuarioId The ID of the user whose password to update.
     * @param string $nuevaPassword The new password for the user.
     * @throws Exception If the user is not found.
     */
    public function actualizarPassword(int $usuarioId, string $nuevaPassword): void
    {
        // Find the user entity
        $usuario = $this->em->find(Usuario::class, $usuarioId);
        if (!$usuario) {
            throw new Exception('User not found');
        }

        // Update password hash
        $usuario->setPasswordHash(password_hash($nuevaPassword, PASSWORD_DEFAULT));
        $this->em->flush();
    }

    /**
     * Deletes a specific user (cannot delete oneself).
     *
     * @param int $usuarioId The ID of the user to delete.
     * @param int $usuarioActualId The ID of the current user performing the action.
     * @throws Exception If trying to delete oneself or user not found.
     */
    public function eliminarUsuario(int $usuarioId, int $usuarioActualId): void
    {
        // Prevent self-deletion
        if ($usuarioId === $usuarioActualId) {
            throw new Exception('Cannot delete yourself');
        }

        // Find the user entity
        $usuario = $this->em->find(Usuario::class, $usuarioId);
        if (!$usuario) {
            throw new Exception('User not found');
        }

        // Remove and flush the user
        $this->em->remove($usuario);
        $this->em->flush();
    }
}