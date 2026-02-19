<?php
namespace App\Services;

use App\Entities\Usuario;
use App\Utils\RateLimiter;
use Doctrine\ORM\EntityManager;
use Exception;

/**
 * Service class responsible for handling user authentication operations.
 * Includes rate limiting protection against brute force attacks.
 */
class AuthService
{
    /** @var EntityManager Doctrine EntityManager for database operations */
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Authenticates a user with rate limiting protection
     */
    public function login(string $usuario, string $password): Usuario
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        // Check if IP is blocked
        if (RateLimiter::isBlocked($ip)) {
            $remaining = RateLimiter::getRemainingLockoutTime($ip);
            $minutes = ceil($remaining / 60);
            throw new Exception("Demasiados intentos fallidos. Intenta nuevamente en {$minutes} minutos.");
        }
        
        // Retrieve user from database
        $user = $this->em->getRepository(Usuario::class)
            ->findOneBy(['usuario' => $usuario]);

        // Verify credentials
        if (!$user || !$user->verificarPassword($password)) {
            RateLimiter::recordFailedAttempt($ip);
            throw new Exception('Invalid credentials');
        }
        
        // Clear attempts after successful login
        RateLimiter::clearAttempts($ip);

        // Set session variables
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['usuario'] = $user->getUsuario();
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