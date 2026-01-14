<?php
namespace App\Utils;

use Exception;
use Throwable;

/**
 * Utility class for handling errors and exceptions in the API.
 * Provides methods to register global handlers and process exceptions into standardized API responses.
 */
class ErrorHandler
{
    /**
     * Handles exceptions by mapping them to appropriate HTTP status codes and sending an error response.
     *
     * @param Throwable $e The exception to handle.
     * @return void
     */
    public static function handleException(Throwable $e): void
    {
        $code = $e->getCode() ?: 500; // Get the exception code or default to 500

        // Map specific error messages to HTTP codes
        if ($e->getMessage() === 'Workshop not found' ||
            $e->getMessage() === 'Appointment not found') {
            $code = 404; // Not found
        } elseif (strpos($e->getMessage(), 'Invalid credentials') !== false ||
                  strpos($e->getMessage(), 'Unauthorized access') !== false) {
            $code = 401; // Unauthorized
        } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
            $code = 403; // Forbidden
        } elseif ($code < 400 || $code >= 600) {
            $code = 500; // Ensure code is within valid error range
        }

        ApiResponse::error($e->getMessage(), $code); // Send the error response using ApiResponse
    }

    /**
     * Registers global exception and error handlers to use this class's handleException method.
     *
     * @return void
     */
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']); // Register global exception handler
        set_error_handler(function($severity, $message, $file, $line) { // Register global error handler to convert errors to exceptions
            throw new Exception("Error: $message in $file:$line", 500);
        });
    }
}