<?php
namespace App\Controllers;

use App\Services\TurnoService;
use App\Utils\ApiResponse;
use App\Validators\TurnoValidator;
use Exception;

/**
 * TallerController maneja operaciones relacionadas con turnos de talleres, como crear turnos y obtener el estado del taller.
 *
 * Propósito general:
 * - Proporcionar endpoints públicos para que los clientes creen turnos sin autenticación.
 * - Mostrar el estado actual del taller (cola de turnos, etc.).
 *
 * Dependencias:
 * - Utiliza TurnoService para la lógica de negocio de turnos.
 * - Usa ApiResponse para enviar respuestas estandarizadas.
 * - Usa TurnoValidator para validar datos de entrada.
 *
 * Interacciones con otras capas:
 * - Recibe solicitudes HTTP desde index.php para operaciones públicas.
 * - Delega creación y consulta de turnos al servicio correspondiente.
 * - Valida entradas antes de procesar para asegurar integridad de datos.
 * - Envía respuestas JSON con detalles de turnos o estado.
 * - Lanza excepciones que son manejadas por ErrorHandler.
 */
class TallerController
{
    /** @var TurnoService Servicio para gestionar operaciones relacionadas con turnos */
    private TurnoService $turnoService;

    /**
     * Constructor inicializa el servicio de turnos usando el entity manager global.
     */
    public function __construct()
    {
        $this->turnoService = new TurnoService($GLOBALS['entityManager']);
    }

    /**
     * Crea un nuevo turno para un taller específico después de validar los datos de entrada.
     * @param int $tallerId El ID del taller
     * @return void
     */
    public function crearTurno(int $tallerId): void
    {
        try {
            // Decodificar entrada JSON del cuerpo de la solicitud
            $input = json_decode(file_get_contents('php://input'), true);

            // Verificar si el JSON es válido
            if (!$input) {
                ApiResponse::error('JSON inválido', 400);
                return;
            }

            // Validar entrada de turno
            $errors = TurnoValidator::validate($input);
            if (!empty($errors)) {
                ApiResponse::error(implode(', ', $errors), 400);
                return;
            }

            // Crear el turno usando el servicio de turnos
            $turno = $this->turnoService->crearTurno($tallerId, $input);

            // Retornar respuesta de éxito con detalles del turno
            ApiResponse::success([
                'id' => $turno->getId(),
                'numeroTurno' => $turno->getNumeroTurno(),
                'estado' => $turno->getEstado()
            ], 201);
        } catch (Exception $e) {
            // Re-lanzar excepciones para manejo de nivel superior
            throw $e;
        }
    }

    /**
     * Obtiene el estado actual de un taller.
     * @param int $tallerId El ID del taller
     * @return void
     */
    public function obtenerEstado(int $tallerId): void
    {
        try {
            // Obtener el estado del taller usando el servicio de turnos
            $estado = $this->turnoService->obtenerEstadoTaller($tallerId);
            // Retornar respuesta de éxito con datos de estado
            ApiResponse::success($estado);
        } catch (Exception $e) {
            // Re-lanzar excepciones para manejo de nivel superior
            throw $e;
        }
    }
}