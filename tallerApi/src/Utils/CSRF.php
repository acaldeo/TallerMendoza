<?php
/**
 * Clase CSRF
 * 
 * Protege contra ataques Cross-Site Request Forgery
 */

namespace App\Utils;

class CSRF
{
    /**
     * Genera un token CSRF y lo guarda en sesión
     */
    public static function generateToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }

    /**
     * Valida un token CSRF
     */
    public static function validateToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Obtiene el token CSRF actual
     */
    public static function getToken(): ?string
    {
        return $_SESSION['csrf_token'] ?? null;
    }

    /**
     * Regenera el token CSRF
     */
    public static function regenerateToken(): string
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
}
