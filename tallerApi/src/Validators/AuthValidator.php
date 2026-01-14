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
     * Validates login data.
     *
     * Checks if the required fields 'usuario' and 'password' are present and not empty.
     *
     * @param array $data The input data containing login credentials.
     * @return array An array of error messages; empty if validation passes.
     */
    public static function validateLogin(array $data): array
    {
        $errors = []; // Initialize an array to collect validation errors

        // Check if the 'usuario' field is provided and not empty
        if (empty($data['usuario'])) {
            $errors[] = 'Username is required';
        }

        // Check if the 'password' field is provided and not empty
        if (empty($data['password'])) {
            $errors[] = 'Password is required';
        }

        return $errors; // Return the array of errors; empty if no errors
    }
}