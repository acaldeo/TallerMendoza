<?php
/**
 * UsuarioValidator class
 *
 * Validates input data for user management operations.
 */

namespace App\Validators;

class UsuarioValidator
{
    /**
     * Validates data for creating a new user.
     *
     * Checks username (min 3 chars, alphanumeric + underscore) and password (min 6 chars).
     *
     * @param array $data The input data containing usuario and password.
     * @return array An array of error messages; empty if validation passes.
     */
    public static function validateCrear(array $data): array
    {
        $errors = [];

        // Validate username: must be present, at least 3 characters after trimming
        if (empty($data['usuario']) || strlen(trim($data['usuario'])) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        }

        // Validate username format: only letters, numbers, and underscores
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['usuario'] ?? '')) {
            $errors[] = 'Username can only contain letters, numbers, and underscores';
        }

        // Validate password: must be present and at least 6 characters
        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }

        return $errors;
    }

    /**
     * Validates data for updating a user's password.
     *
     * Checks that the new password is at least 6 characters long.
     *
     * @param array $data The input data containing the new password.
     * @return array An array of error messages; empty if validation passes.
     */
    public static function validatePassword(array $data): array
    {
        $errors = [];

        // Validate password: must be present and at least 6 characters
        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }

        return $errors;
    }
}