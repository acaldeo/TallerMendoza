<?php
namespace App\Validators;

/**
 * Clase TurnoValidator
 *
 * Maneja la validación de datos para la creación de turnos (citas).
 * Asegura que los campos requeridos estén presentes y cumplan criterios específicos,
 * y que los campos opcionales cumplan con restricciones de longitud.
 *
 * Propósito general:
 * - Validar datos de entrada antes de crear turnos.
 * - Prevenir datos inválidos que puedan causar errores en la BD o lógica.
 * - Proporcionar mensajes de error descriptivos para el usuario.
 *
 * Dependencias:
 * - No tiene dependencias externas, solo usa funciones PHP nativas.
 * - Es utilizada por controladores (TallerController) antes de procesar datos.
 *
 * Interacciones con otras capas:
 * - Los controladores llaman a este validador antes de pasar datos a servicios.
 * - Retorna array de errores que se envían en respuestas de error.
 */
class TurnoValidator
{
    /**
     * Valida datos para la creación de turno.
     *
     * Verifica campos requeridos: nombreCliente (mín 2 chars), telefono (8-15 dígitos), modeloVehiculo, patente.
     * Campo opcional: descripcionProblema (máx 255 chars si presente).
     *
     * @param array $data Los datos de entrada para creación de turno.
     * @return array Un array de mensajes de error; vacío si la validación pasa.
     */
    public static function validate(array $data): array
    {
        $errors = []; // Inicializar array para recolectar errores de validación

        // Validar nombreCliente: debe estar presente y tener al menos 2 caracteres después de trim
        if (empty($data['nombreCliente']) || strlen(trim($data['nombreCliente'])) < 2) {
            $errors[] = 'El nombre del cliente debe tener al menos 2 caracteres';
        }

        // Validar telefono: debe estar presente y coincidir con patrón de 8-15 dígitos
        if (empty($data['telefono']) || !preg_match('/^\d{8,15}$/', $data['telefono'])) {
            $errors[] = 'El número de teléfono debe contener entre 8 y 15 dígitos';
        }

        // Validar modeloVehiculo: debe estar presente y no vacío
        if (empty($data['modeloVehiculo'])) {
            $errors[] = 'El modelo del vehículo es requerido';
        }

        // Validar patente: debe estar presente y no vacía
        if (empty($data['patente'])) {
            $errors[] = 'La patente del vehículo es requerida';
        }

        // Validar descripcionProblema: si está presente, no debe exceder 255 caracteres
        if (isset($data['descripcionProblema']) && strlen($data['descripcionProblema']) > 255) {
            $errors[] = 'La descripción del problema no puede exceder 255 caracteres';
        }

        return $errors; // Retornar el array de errores; vacío si no hay errores
    }
}