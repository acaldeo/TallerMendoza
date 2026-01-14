<?php
/**
 * Servicio EmailService
 * 
 * Maneja el envío de notificaciones por email.
 * Usa la configuración SMTP almacenada en la base de datos.
 */

namespace App\Services;

use App\Entities\ConfiguracionEmail;
use App\Entities\Turno;
use Doctrine\ORM\EntityManager;

class EmailService
{
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Envía notificación de nuevo turno
     */
    public function enviarNotificacionNuevoTurno(Turno $turno): bool
    {
        $config = $this->obtenerConfiguracion($turno->getTaller()->getId());
        
        if (!$config || !$config->isActivo()) {
            return false; // No hay configuración o está inactiva
        }

        $asunto = "Nuevo Turno #{$turno->getNumeroTurno()} - {$turno->getTaller()->getNombre()}";
        
        $mensaje = $this->generarMensajeNuevoTurno($turno);
        
        return $this->enviarEmail($config, $asunto, $mensaje);
    }

    /**
     * Obtiene la configuración de email del taller
     */
    private function obtenerConfiguracion(int $tallerId): ?ConfiguracionEmail
    {
        return $this->em->getRepository(ConfiguracionEmail::class)
            ->createQueryBuilder('c')
            ->where('c.taller = :tallerId')
            ->setParameter('tallerId', $tallerId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Genera el mensaje HTML para nuevo turno
     */
    private function generarMensajeNuevoTurno(Turno $turno): string
    {
        $estado = $turno->getEstado() === 'EN_TALLER' ? 
            '<span style="color: #28a745; font-weight: bold;">EN TALLER</span>' : 
            '<span style="color: #dc3545; font-weight: bold;">EN ESPERA</span>';

        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;'>
                <h2 style='color: #007bff; text-align: center; margin-bottom: 30px;'>
                    🔧 Nuevo Turno Registrado
                </h2>
                
                <div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;'>
                    <h3 style='margin-top: 0; color: #495057;'>Turno #{$turno->getNumeroTurno()}</h3>
                    <p><strong>Estado:</strong> {$estado}</p>
                    <p><strong>Taller:</strong> {$turno->getTaller()->getNombre()}</p>
                    <p><strong>Fecha:</strong> {$turno->getFechaCreacion()->format('d/m/Y H:i:s')}</p>
                </div>
                
                <div style='background: #fff; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px;'>
                    <h4 style='color: #495057; margin-top: 0;'>Datos del Cliente:</h4>
                    <p><strong>Nombre:</strong> {$turno->getNombreCliente()}</p>
                    <p><strong>Teléfono:</strong> <a href='tel:{$turno->getTelefono()}'>{$turno->getTelefono()}</a></p>
                    <p><strong>Vehículo:</strong> {$turno->getModeloVehiculo()}</p>
                    <p><strong>Problema:</strong> {$turno->getDescripcionProblema()}</p>
                </div>
                
                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;'>
                    <small style='color: #6c757d;'>
                        Sistema de Gestión de Turnos - Notificación automática
                    </small>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Envía el email usando PHP mail()
     */
    private function enviarEmail(ConfiguracionEmail $config, string $asunto, string $mensaje): bool
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            "From: {$config->getNombreOrigen()} <{$config->getEmailOrigen()}>",
            "Reply-To: {$config->getEmailOrigen()}",
            'X-Mailer: PHP/' . phpversion()
        ];

        return mail(
            $config->getEmailDestino(),
            $asunto,
            $mensaje,
            implode("\r\n", $headers)
        );
    }
}