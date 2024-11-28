<?php
namespace App;

require_once 'db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use \PDO;
use \Exception;
use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\SMTP;
use \PHPMailer\PHPMailer\Exception as PHPMailerException;

class Notificador {
    private $pdo;
    private $config;

    public function __construct() {
        $this->pdo = \getDB();
        $this->cargarConfiguracion();
    }

    private function cargarConfiguracion() {
        $stmt = $this->pdo->query("SELECT * FROM configuracion_notificaciones WHERE activo = 1");
        $this->config = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->config[$row['tipo_evento']] = $row;
        }
    }

    public function notificar($tipo_evento, $datos, $usuario_destino = null) {
        if (!isset($this->config[$tipo_evento])) {
            throw new Exception("Tipo de evento no configurado: " . $tipo_evento);
        }

        $config = $this->config[$tipo_evento];
        
        // Crear notificación en el sistema
        if ($config['enviar_sistema']) {
            $this->crearNotificacionSistema($tipo_evento, $datos, $usuario_destino);
        }

        // Enviar email
        if ($config['enviar_email']) {
            $this->enviarEmail($tipo_evento, $datos, $usuario_destino);
        }
    }

    private function crearNotificacionSistema($tipo_evento, $datos, $usuario_destino) {
        $stmt = $this->pdo->prepare("
            INSERT INTO notificaciones (
                tipo_evento, titulo, mensaje, entidad_tipo, entidad_id, usuario_destino
            ) VALUES (
                :tipo_evento, :titulo, :mensaje, :entidad_tipo, :entidad_id, :usuario_destino
            )
        ");

        $titulo = $this->generarTitulo($tipo_evento, $datos);
        $mensaje = $this->generarMensaje($tipo_evento, $datos);

        $stmt->execute([
            'tipo_evento' => $tipo_evento,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'entidad_tipo' => $datos['entidad_tipo'],
            'entidad_id' => $datos['entidad_id'],
            'usuario_destino' => $usuario_destino
        ]);
    }

    private function enviarEmail($tipo_evento, $datos, $usuario_destino) {
        $plantilla = $this->config[$tipo_evento]['plantilla_email'];
        $contenido = $this->procesarPlantilla($plantilla, $datos);
        
        // Registrar email pendiente
        $stmt = $this->pdo->prepare("
            INSERT INTO registro_emails (
                email_destino, asunto, contenido, estado
            ) VALUES (
                :email_destino, :asunto, :contenido, 'pendiente'
            )
        ");

        $stmt->execute([
            'email_destino' => $datos['email_destino'],
            'asunto' => $this->generarTitulo($tipo_evento, $datos),
            'contenido' => $contenido
        ]);
    }

    private function generarTitulo($tipo_evento, $datos) {
        switch ($tipo_evento) {
            case 'presupuesto_creado':
                return "Nuevo presupuesto #{$datos['numero_presupuesto']}";
            case 'presupuesto_modificado':
                return "Presupuesto #{$datos['numero_presupuesto']} modificado";
            case 'cambio_estado':
                return "Cambio de estado en presupuesto #{$datos['numero_presupuesto']}";
            case 'presupuesto_vencido':
                return "Presupuesto #{$datos['numero_presupuesto']} próximo a vencer";
            case 'revision_pendiente':
                return "Revisión pendiente - Presupuesto #{$datos['numero_presupuesto']}";
            default:
                return "Notificación del sistema";
        }
    }

    private function generarMensaje($tipo_evento, $datos) {
        $plantilla = $this->config[$tipo_evento]['plantilla_email'];
        return $this->procesarPlantilla($plantilla, $datos);
    }

    private function procesarPlantilla($plantilla, $datos) {
        foreach ($datos as $key => $value) {
            $plantilla = str_replace("{{$key}}", $value, $plantilla);
        }
        return $plantilla;
    }

    public function procesarEmailsPendientes() {
        $stmt = $this->pdo->query("
            SELECT * FROM registro_emails 
            WHERE estado = 'pendiente' 
            AND intentos < 3 
            ORDER BY created_at ASC 
            LIMIT 10
        ");

        while ($email = $stmt->fetch(PDO::FETCH_ASSOC)) {
            try {
                $this->enviarEmailReal($email);
                
                $this->pdo->prepare("
                    UPDATE registro_emails 
                    SET estado = 'enviado', 
                        ultimo_intento = NOW() 
                    WHERE id = ?
                ")->execute([$email['id']]);
            } catch (\Exception $e) {
                $this->pdo->prepare("
                    UPDATE registro_emails 
                    SET estado = 'error', 
                        error_mensaje = ?, 
                        intentos = intentos + 1, 
                        ultimo_intento = NOW() 
                    WHERE id = ?
                ")->execute([$e->getMessage(), $email['id']]);
            }
        }
    }

    private function enviarEmailReal($email) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USER'];
            $mail->Password = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $_ENV['SMTP_PORT'];

            $mail->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
            $mail->addAddress($email['email_destino']);
            
            $mail->isHTML(true);
            $mail->Subject = $email['asunto'];
            $mail->Body = $email['contenido'];
            
            $mail->send();
        } catch (\Exception $e) {
            throw new \Exception("Error al enviar email: " . $e->getMessage());
        }
    }

    public function obtenerNotificacionesUsuario($usuario, $solo_no_leidas = true) {
        $sql = "
            SELECT * FROM notificaciones 
            WHERE usuario_destino = :usuario
        ";
        
        if ($solo_no_leidas) {
            $sql .= " AND leida = 0";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT 50";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['usuario' => $usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function marcarComoLeida($notificacion_id) {
        $stmt = $this->pdo->prepare("
            UPDATE notificaciones 
            SET leida = 1, 
                fecha_lectura = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$notificacion_id]);
    }
} 