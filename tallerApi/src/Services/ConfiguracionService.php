<?php
/**
 * Servicio ConfiguracionService
 * 
 * Maneja la configuración de email para notificaciones.
 */

namespace App\Services;

use App\Entities\ConfiguracionEmail;
use App\Entities\Taller;
use Doctrine\ORM\EntityManager;
use Exception;

class ConfiguracionService
{
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Obtiene la configuración de email del taller
     */
    public function obtenerConfiguracion(int $tallerId): ?array
    {
        $config = $this->em->getRepository(ConfiguracionEmail::class)
            ->createQueryBuilder('c')
            ->where('c.taller = :tallerId')
            ->setParameter('tallerId', $tallerId)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$config) {
            return null;
        }

        return [
            'id' => $config->getId(),
            'smtpHost' => $config->getSmtpHost(),
            'smtpPort' => $config->getSmtpPort(),
            'smtpUsuario' => $config->getSmtpUsuario(),
            'emailOrigen' => $config->getEmailOrigen(),
            'nombreOrigen' => $config->getNombreOrigen(),
            'emailDestino' => $config->getEmailDestino(),
            'activo' => $config->isActivo()
        ];
    }

    /**
     * Guarda o actualiza la configuración de email
     */
    public function guardarConfiguracion(int $tallerId, array $datos): void
    {
        $taller = $this->em->find(Taller::class, $tallerId);
        if (!$taller) {
            throw new Exception('Taller no encontrado');
        }

        $config = $this->em->getRepository(ConfiguracionEmail::class)
            ->createQueryBuilder('c')
            ->where('c.taller = :tallerId')
            ->setParameter('tallerId', $tallerId)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$config) {
            $config = new ConfiguracionEmail();
            $config->setTaller($taller);
        }

        $config->setSmtpHost($datos['smtpHost'] ?? 'smtp.gmail.com')
               ->setSmtpPort($datos['smtpPort'] ?? 587)
               ->setSmtpUsuario($datos['smtpUsuario'] ?? '')
               ->setEmailOrigen($datos['emailOrigen'] ?? '')
               ->setNombreOrigen($datos['nombreOrigen'] ?? '')
               ->setEmailDestino($datos['emailDestino'] ?? '')
               ->setActivo($datos['activo'] ?? false);

        // Solo actualizar password si se proporciona
        if (!empty($datos['smtpPassword'])) {
            $config->setSmtpPassword($datos['smtpPassword']);
        }

        $this->em->persist($config);
        $this->em->flush();
    }

    /**
     * Prueba el envío de email con la configuración
     */
    public function probarConfiguracion(int $tallerId): bool
    {
        $config = $this->em->getRepository(ConfiguracionEmail::class)
            ->createQueryBuilder('c')
            ->where('c.taller = :tallerId')
            ->setParameter('tallerId', $tallerId)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$config || !$config->isActivo()) {
            throw new Exception('No hay configuración activa');
        }

        $asunto = "Prueba de configuración - {$config->getTaller()->getNombre()}";
        $mensaje = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>✅ Configuración de Email Correcta</h2>
            <p>Este es un email de prueba para verificar que la configuración funciona correctamente.</p>
            <p><strong>Taller:</strong> {$config->getTaller()->getNombre()}</p>
            <p><strong>Fecha:</strong> " . date('d/m/Y H:i:s') . "</p>
        </body>
        </html>";

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            "From: {$config->getNombreOrigen()} <{$config->getEmailOrigen()}>",
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