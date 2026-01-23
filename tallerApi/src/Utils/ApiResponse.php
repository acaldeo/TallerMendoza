<?php
namespace App\Utils;

/**
 * Clase utilitaria para estandarizar respuestas de la API.
 * Proporciona métodos estáticos para enviar respuestas JSON consistentes en casos de éxito y error.
 *
 * Propósito general:
 * - Garantizar que todas las respuestas de la API tengan el mismo formato.
 * - Facilitar el envío de respuestas exitosas con datos.
 * - Simplificar el envío de respuestas de error con mensajes descriptivos.
 *
 * Dependencias:
 * - No tiene dependencias externas, solo usa funciones PHP nativas.
 * - Es utilizada por todos los controladores para enviar respuestas.
 *
 * Interacciones con otras capas:
 * - Los controladores llaman a estos métodos para finalizar las respuestas HTTP.
 * - Establece códigos de estado HTTP apropiados.
 * - Codifica datos a JSON para el frontend.
 */
class ApiResponse
{
    /**
     * Envía una respuesta exitosa de la API.
     *
     * @param mixed $data Los datos a incluir en la respuesta (pueden ser null).
     * @param int $httpCode El código de estado HTTP (por defecto 200).
     * @return void
     */
    public static function success($data = null, int $httpCode = 200): void
    {
        http_response_code($httpCode); // Establecer el código de respuesta HTTP
        echo json_encode([ // Codificar y enviar la respuesta JSON
            'success' => true,
            'data' => $data,
            'error' => null
        ]);
    }

    /**
     * Envía una respuesta de error de la API.
     *
     * @param string $message El mensaje de error.
     * @param int $httpCode El código de estado HTTP (por defecto 400).
     * @return void
     */
    public static function error(string $message, int $httpCode = 400): void
    {
        http_response_code($httpCode); // Establecer el código de respuesta HTTP
        echo json_encode([ // Codificar y enviar la respuesta JSON
            'success' => false,
            'data' => null,
            'error' => $message
        ]);
    }
}