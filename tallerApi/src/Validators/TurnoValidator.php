<?php
namespace App\Validators;

/**
 * Class TurnoValidator
 *
 * Handles validation for turno (appointment) creation data.
 * Ensures that required fields are present and meet specific criteria,
 * and optional fields adhere to length constraints.
 */
class TurnoValidator
{
    /**
     * Validates turno creation data.
     *
     * Checks required fields: nombreCliente (min 2 chars), telefono (8-15 digits), modeloVehiculo, patente.
     * Optional field: descripcionProblema (max 255 chars if present).
     *
     * @param array $data The input data for turno creation.
     * @return array An array of error messages; empty if validation passes.
     */
    public static function validate(array $data): array
    {
        $errors = []; // Initialize an array to collect validation errors

        // Validate nombreCliente: must be present and at least 2 characters after trimming
        if (empty($data['nombreCliente']) || strlen(trim($data['nombreCliente'])) < 2) {
            $errors[] = 'Client name must be at least 2 characters';
        }

        // Validate telefono: must be present and match 8-15 digits pattern
        if (empty($data['telefono']) || !preg_match('/^\d{8,15}$/', $data['telefono'])) {
            $errors[] = 'Phone number must contain between 8 and 15 digits';
        }

        // Validate modeloVehiculo: must be present and not empty
        if (empty($data['modeloVehiculo'])) {
            $errors[] = 'Vehicle model is required';
        }

        // Validate patente: must be present and not empty
        if (empty($data['patente'])) {
            $errors[] = 'Vehicle license plate is required';
        }

        // Validate descripcionProblema: if present, must not exceed 255 characters
        if (isset($data['descripcionProblema']) && strlen($data['descripcionProblema']) > 255) {
            $errors[] = 'Problem description cannot exceed 255 characters';
        }

        return $errors; // Return the array of errors; empty if no errors
    }
}