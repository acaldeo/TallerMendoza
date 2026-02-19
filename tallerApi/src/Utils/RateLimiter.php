<?php
/**
 * Clase RateLimiter
 * 
 * Protege contra ataques de fuerza bruta limitando intentos de login
 */

namespace App\Utils;

class RateLimiter
{
    private static $attempts = [];
    private static $lockouts = [];

    /**
     * Verifica si una IP está bloqueada
     */
    public static function isBlocked(string $ip): bool
    {
        if (isset(self::$lockouts[$ip])) {
            $lockoutTime = self::$lockouts[$ip];
            $lockoutDuration = (int)Env::get('LOGIN_LOCKOUT_TIME', 900);
            
            if (time() - $lockoutTime < $lockoutDuration) {
                return true;
            }
            
            // Desbloquear si ya pasó el tiempo
            unset(self::$lockouts[$ip]);
            unset(self::$attempts[$ip]);
        }
        
        return false;
    }

    /**
     * Registra un intento fallido
     */
    public static function recordFailedAttempt(string $ip): void
    {
        if (!isset(self::$attempts[$ip])) {
            self::$attempts[$ip] = [];
        }
        
        self::$attempts[$ip][] = time();
        
        // Limpiar intentos antiguos (más de 15 minutos)
        self::$attempts[$ip] = array_filter(self::$attempts[$ip], function($timestamp) {
            return time() - $timestamp < 900;
        });
        
        // Bloquear si excede el máximo de intentos
        $maxAttempts = (int)Env::get('LOGIN_MAX_ATTEMPTS', 5);
        if (count(self::$attempts[$ip]) >= $maxAttempts) {
            self::$lockouts[$ip] = time();
        }
    }

    /**
     * Limpia intentos después de login exitoso
     */
    public static function clearAttempts(string $ip): void
    {
        unset(self::$attempts[$ip]);
        unset(self::$lockouts[$ip]);
    }

    /**
     * Obtiene tiempo restante de bloqueo
     */
    public static function getRemainingLockoutTime(string $ip): int
    {
        if (!isset(self::$lockouts[$ip])) {
            return 0;
        }
        
        $lockoutDuration = (int)Env::get('LOGIN_LOCKOUT_TIME', 900);
        $elapsed = time() - self::$lockouts[$ip];
        $remaining = $lockoutDuration - $elapsed;
        
        return max(0, $remaining);
    }
}
