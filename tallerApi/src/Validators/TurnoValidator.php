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
     * Sanitiza una cadena de texto para prevenir XSS.
     * @param string $input La cadena a sanitizar.
     * @return string La cadena sanitizada.
     */
    private static function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Valida y sanitiza datos para la creación de turno.
     *
     * Verifica campos requeridos: nombreCliente (mín 2 chars), telefono (8-15 dígitos), modeloVehiculo, patente.
     * Campo opcional: descripcionProblema (máx 255 chars si presente).
     * Sanitiza strings para prevenir XSS.
     *
     * @param array $data Los datos de entrada para creación de turno (modificado por referencia para sanitizar).
     * @return array Un array de mensajes de error; vacío si la validación pasa.
     */
    public static function validate(array &$data): array
    {
        $errors = []; // Inicializar array para recolectar errores de validación

        // Sanitizar y validar nombreCliente
        $data['nombreCliente'] = self::sanitizeString($data['nombreCliente'] ?? '');
        if (empty($data['nombreCliente']) || strlen($data['nombreCliente']) < 2) {
            $errors[] = 'El nombre del cliente debe tener al menos 2 caracteres';
        }

        // Validar telefono: debe estar presente y coincidir con patrón de 8-15 dígitos
        $data['telefono'] = trim($data['telefono'] ?? '');
        if (empty($data['telefono']) || !preg_match('/^\d{8,15}$/', $data['telefono'])) {
            $errors[] = 'El número de teléfono debe contener entre 8 y 15 dígitos';
        }

        // Sanitizar y validar modeloVehiculo
        $data['modeloVehiculo'] = self::sanitizeString($data['modeloVehiculo'] ?? '');
        if (empty($data['modeloVehiculo'])) {
            $errors[] = 'El modelo del vehículo es requerido';
        }

        // Sanitizar y validar patente
        $data['patente'] = self::sanitizeString($data['patente'] ?? '');
        if (empty($data['patente'])) {
            $errors[] = 'La patente del vehículo es requerida';
        }

        // Sanitizar descripcionProblema si presente
        if (isset($data['descripcionProblema'])) {
            $data['descripcionProblema'] = self::sanitizeString($data['descripcionProblema']);
            if (strlen($data['descripcionProblema']) > 255) {
                $errors[] = 'La descripción del problema no puede exceder 255 caracteres';
            }
        }

        return $errors; // Retornar el array de errores; vacío si no hay errores
    }
}