<?php
/**
 * Clase Env
 * 
 * Carga y gestiona variables de entorno desde archivo .env
 */

namespace App\Utils;

class Env
{
    private static $loaded = false;
    private static $vars = [];

    /**
     * Carga variables de entorno desde archivo .env
     */
    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        $envFile = $path . '/.env';
        
        if (!file_exists($envFile)) {
            return; // Usar valores por defecto si no existe .env
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorar comentarios
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parsear línea
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Remover comillas si existen
                $value = trim($value, '"\'');
                
                // Guardar en array y en $_ENV
                self::$vars[$name] = $value;
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }

        self::$loaded = true;
    }

    /**
     * Obtiene una variable de entorno
     */
    public static function get(string $key, $default = null)
    {
        return self::$vars[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }
}
