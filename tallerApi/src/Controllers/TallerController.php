<?php
namespace App\Controllers;

use App\Services\TurnoService;
use App\Utils\ApiResponse;
use App\Validators\TurnoValidator;
use Exception;

/**
 * TallerController handles operations related to workshop turns, such as creating turns and retrieving workshop status.
 */
class TallerController
{
    /** @var TurnoService Service for managing turn-related operations */
    private TurnoService $turnoService;

    /**
     * Constructor initializes the turn service using the global entity manager.
     */
    public function __construct()
    {
        $this->turnoService = new TurnoService($GLOBALS['entityManager']);
    }

    /**
     * Creates a new turn for a specific workshop after validating input data.
     * @param int $tallerId The ID of the workshop
     * @return void
     */
    public function crearTurno(int $tallerId): void
    {
        try {
            // Decode JSON input from the request body
            $input = json_decode(file_get_contents('php://input'), true);

            // Check if JSON is valid
            if (!$input) {
                ApiResponse::error('Invalid JSON', 400);
                return;
            }

            // Validate turn input
            $errors = TurnoValidator::validate($input);
            if (!empty($errors)) {
                ApiResponse::error(implode(', ', $errors), 400);
                return;
            }

            // Create the turn using the turn service
            $turno = $this->turnoService->crearTurno($tallerId, $input);

            // Return success response with turn details
            ApiResponse::success([
                'id' => $turno->getId(),
                'numeroTurno' => $turno->getNumeroTurno(),
                'estado' => $turno->getEstado()
            ], 201);
        } catch (Exception $e) {
            // Re-throw exceptions for higher-level handling
            throw $e;
        }
    }

    /**
     * Retrieves the current status of a workshop.
     * @param int $tallerId The ID of the workshop
     * @return void
     */
    public function obtenerEstado(int $tallerId): void
    {
        try {
            // Retrieve the workshop status using the turn service
            $estado = $this->turnoService->obtenerEstadoTaller($tallerId);
            // Return success response with status data
            ApiResponse::success($estado);
        } catch (Exception $e) {
            // Re-throw exceptions for higher-level handling
            throw $e;
        }
    }
}