<?php
namespace App\Utils;

/**
 * Utility class for standardizing API responses.
 * Provides static methods to send consistent JSON responses for success and error cases.
 */
class ApiResponse
{
    /**
     * Sends a successful API response.
     *
     * @param mixed $data The data to include in the response.
     * @param int $httpCode The HTTP status code (default 200).
     * @return void
     */
    public static function success($data = null, int $httpCode = 200): void
    {
        http_response_code($httpCode); // Set the HTTP response code
        echo json_encode([ // Encode and output the JSON response
            'success' => true,
            'data' => $data,
            'error' => null
        ]);
    }

    /**
     * Sends an error API response.
     *
     * @param string $message The error message.
     * @param int $httpCode The HTTP status code (default 400).
     * @return void
     */
    public static function error(string $message, int $httpCode = 400): void
    {
        http_response_code($httpCode); // Set the HTTP response code
        echo json_encode([ // Encode and output the JSON response
            'success' => false,
            'data' => null,
            'error' => $message
        ]);
    }
}