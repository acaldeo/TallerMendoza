<?php
/**
 * Servicio EmailService
 *
 * Maneja el envío de notificaciones por email para el sistema de gestión de turnos.
 * Usa la configuración SMTP almacenada en la base de datos para cada taller.
 *
 * Propósito general:
 * - Enviar notificaciones automáticas cuando se crean nuevos turnos.
 * - Usar configuraciones SMTP personalizadas por taller.
 * - Generar emails HTML atractivos con detalles del turno.
 *
 * Dependencias:
 * - Utiliza PHPMailer para el envío de emails.
 * - Depende de ConfiguracionEmail para obtener ajustes SMTP.
 * - Usa EntityManager para acceder a configuraciones en BD.
 * - Es llamado por TurnoService al crear turnos.
 *
 * Interacciones con otras capas:
 * - TurnoService lo invoca para notificar nuevos turnos.
 * - Accede a la BD para obtener configuración de email.
 * - Maneja errores de envío sin fallar la operación principal.
 */

namespace App\Services;

use App\Entities\ConfiguracionEmail;
use App\Entities\Turno;
use Doctrine\ORM\EntityManager;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private EntityManager $em;

    /**
     * Constructor que inyecta el EntityManager.
     * @param EntityManager $em Instancia del EntityManager para acceso a BD.
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Envía notificación de nuevo turno al administrador del taller.
     * @param Turno $turno La entidad del turno recién creado.
     * @return bool True si el email se envió correctamente, false en caso contrario.
     */
    public function enviarNotificacionNuevoTurno(Turno $turno): bool
    {
        // Obtener configuración de email del taller
        $config = $this->obtenerConfiguracion($turno->getTaller()->getId());

        // Verificar si hay configuración activa
        if (!$config || !$config->isActivo()) {
            return false; // No hay configuración o está inactiva
        }

        // Preparar asunto y mensaje del email
        $asunto = "Nuevo Turno #{$turno->getNumeroTurno()} - {$turno->getTaller()->getNombre()}";
        $mensaje = $this->generarMensajeNuevoTurno($turno);

        // Enviar el email
        return $this->enviarEmail($config, $asunto, $mensaje);
    }

    /**
     * Obtiene la configuración de email del taller desde la base de datos.
     * @param int $tallerId El ID del taller.
     * @return ConfiguracionEmail|null La configuración de email o null si no existe.
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
     * Genera el mensaje HTML para la notificación de nuevo turno.
     * Incluye detalles del turno, cliente y enlace al panel de administración.
     * @param Turno $turno La entidad del turno.
     * @return string El mensaje HTML del email.
     */
    private function generarMensajeNuevoTurno(Turno $turno): string
    {
        // Determinar color del estado para el email
        $estado = $turno->getEstado() === 'EN_TALLER' ?
            '<span style="color: #28a745; font-weight: bold;">EN TALLER</span>' :
            '<span style="color: #dc3545; font-weight: bold;">EN ESPERA</span>';

        // Generar HTML del email con estilos inline
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
                    <a href='https://tallermendoza.com/mendoza/admin.html'
                       style='background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin-bottom: 20px;'>
                        Ir a Panel de Administración
                    </a>
                    <br>
                    <small style='color: #6c757d;'>
                        Sistema de Gestión de Turnos - Notificación automática
                    </small>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Envía el email usando PHPMailer con configuración SMTP.
     * Configura el servidor SMTP, remitente, destinatario y contenido.
     * @param ConfiguracionEmail $config La configuración de email del taller.
     * @param string $asunto El asunto del email.
     * @param string $mensaje El cuerpo HTML del email.
     * @return bool True si se envió correctamente, false en caso de error.
     */
    private function enviarEmail(ConfiguracionEmail $config, string $asunto, string $mensaje): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Configurar servidor SMTP usando datos de la configuración
            $mail->isSMTP();
            $mail->Host = $config->getSmtpHost();
            $mail->SMTPAuth = true;
            $mail->Username = $config->getSmtpUsuario();
            $mail->Password = $config->getSmtpPassword();
            // Determinar encriptación basada en el puerto (465 para SMTPS, otros para STARTTLS)
            $mail->SMTPSecure = $config->getSmtpPort() == 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $config->getSmtpPort();

            // Configurar remitente y destinatario
            $mail->setFrom($config->getEmailOrigen(), $config->getNombreOrigen());
            $mail->addAddress($config->getEmailDestino());
            $mail->addReplyTo($config->getEmailOrigen(), $config->getNombreOrigen());

            // Configurar contenido del email
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $mensaje;
            $mail->CharSet = 'UTF-8';

            // Enviar el email
            $mail->send();
            return true;
        } catch (Exception $e) {
            // Registrar error en log y retornar false
            error_log("Error enviando email: " . $mail->ErrorInfo);
            return false;
        }
    }
}