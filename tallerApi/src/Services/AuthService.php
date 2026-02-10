<?php
namespace App\Services;

use App\Entities\Usuario;
use Doctrine\ORM\EntityManager;
use Exception;

/**
 * Service class responsible for handling user authentication operations.
 *
 * This class provides methods for user login, logout, authentication status checking,
 * and retrieving the currently authenticated user. It manages session-based authentication
 * and interacts with the database through Doctrine EntityManager.
 */
class AuthService
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
     * Authenticates a user with the provided credentials.
     *
     * Retrieves the user from the database by username, verifies the password,
     * and establishes a session if authentication succeeds.
     *
     * @param string $usuario The username of the user attempting to log in.
     * @param string $password The plain text password for authentication.
     * @return Usuario The authenticated user entity.
     * @throws Exception If the credentials are invalid.
     */
    public function login(string $usuario, string $password): Usuario
    {
        // Retrieve user from database by username
        $user = $this->em->getRepository(Usuario::class)
            ->findOneBy(['usuario' => $usuario]);

        // Verify user exists and password is correct
        if (!$user || !$user->verificarPassword($password)) {
            throw new Exception('Invalid credentials');
        }

        // Set session variables for authenticated user
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['usuario'] = $user->getUsuario();
        // Guardar taller_id solo si el usuario tiene un taller asignado
        $taller = $user->getTaller();
        $_SESSION['taller_id'] = $taller ? $taller->getId() : null;

        return $user;
    }

    /**
     * Logs out the current user by destroying the session.
     *
     * This method clears all session data, effectively ending the user's authenticated session.
     */
    public function logout(): void
    {
        // Destroy the entire session to log out the user
        session_destroy();
    }

    /**
     * Checks if a user is currently authenticated.
     *
     * Determines authentication status by checking for the presence of a user ID in the session.
     *
     * @return bool True if a user is authenticated, false otherwise.
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Retrieves the currently authenticated user.
     *
     * Fetches the user entity from the database using the user ID stored in the session.
     * Returns null if no user is authenticated.
     *
     * @return Usuario|null The current user entity or null if not authenticated.
     */
    public function getCurrentUser(): ?Usuario
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return $this->em->find(Usuario::class, $_SESSION['user_id']);
    }
}