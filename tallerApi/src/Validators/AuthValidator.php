<?php
namespace App\Validators;

/**
 * Class AuthValidator
 *
 * Handles validation for authentication-related data, specifically for login operations.
 * Ensures that required fields are present in the input data.
 */
class AuthValidator
{
    /**
     * Sanitizes a string for security.
     * @param string $input The string to sanitize.
     * @return string The sanitized string.
     */
    private static function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validates and sanitizes login data.
     *
     * Checks if the required fields 'usuario' and 'password' are present and not empty.
     *
     * @param array $data The input data containing login credentials (modified by reference).
     * @return array An array of error messages; empty if validation passes.
     */
    public static function validateLogin(array &$data): array
    {
        $errors = []; // Initialize an array to collect validation errors

        // Sanitize and check usuario
        $data['usuario'] = self::sanitizeString($data['usuario'] ?? '');
        if (empty($data['usuario'])) {
            $errors[] = 'Username is required';
        }

        // Check password (no sanitize)
        if (empty($data['password'])) {
            $errors[] = 'Password is required';
        }

        return $errors; // Return the array of errors; empty if no errors
    }
}