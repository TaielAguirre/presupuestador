<?php
namespace App;

use \PDO;
use \Exception;

class NotificadorMateriales {
    private $pdo;
    private static $instance = null;
    private $configuracionCache = [];

    private function __construct() {
        $this->pdo = getDB();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Notificaciones específicas
    public function notificarMaterialSinValidar($materialId) {
        $material = $this->obtenerDatosMaterial($materialId);
        $mensaje = "Material '{$material['codigo']} - {$material['descripcion']}' pendiente de validación con Flexxus";
        $this->crearNotificacion('sin_validar', $materialId, $mensaje);
    }

    public function notificarErrorValidacion($materialId, $error) {
        $material = $this->obtenerDatosMaterial($materialId);
        $mensaje = "Error en material '{$material['codigo']} - {$material['descripcion']}': $error";
        $this->crearNotificacion('error_validacion', $materialId, $mensaje);
    }

    public function notificarCambioPrecio($materialId, $precioAnterior, $precioNuevo) {
        $material = $this->obtenerDatosMaterial($materialId);
        $diferencia = $precioNuevo - $precioAnterior;
        $porcentaje = ($diferencia / $precioAnterior) * 100;
        
        $mensaje = sprintf(
            "Cambio de precio en material '%s - %s':\n" .
            "Anterior: $%s\n" .
            "Nuevo: $%s\n" .
            "Diferencia: $%s (%s%%)",
            $material['codigo'],
            $material['descripcion'],
            number_format($precioAnterior, 2),
            number_format($precioNuevo, 2),
            number_format(abs($diferencia), 2),
            number_format($porcentaje, 1)
        );
        
        $this->crearNotificacion('cambio_precio', $materialId, $mensaje);
    }

    public function notificarCodigoDuplicado($materialId, $codigoExistente) {
        $material = $this->obtenerDatosMaterial($materialId);
        $materialExistente = $this->obtenerMaterialPorCodigo($codigoExistente);
        
        $mensaje = sprintf(
            "Código duplicado detectado:\n" .
            "Material nuevo: %s - %s\n" .
            "Material existente: %s - %s",
            $material['codigo'],
            $material['descripcion'],
            $materialExistente['codigo'],
            $materialExistente['descripcion']
        );
        
        $this->crearNotificacion('codigo_duplicado', $materialId, $mensaje);
    }

    public function notificarCambioPrecioSignificativo($materialId, $precioAnterior, $precioNuevo, $porcentajeCambio) {
        $material = $this->obtenerDatosMaterial($materialId);
        $usuario = $this->obtenerUsuarioUltimoCambio($materialId);
        $historialReciente = $this->obtenerHistorialReciente($materialId);
        
        // Analizar tendencia histórica
        $tendencia = $this->analizarTendencia($historialReciente);
        
        // Construir mensaje detallado
        $mensaje = $this->construirMensajeCambioSignificativo(
            $material,
            $precioAnterior,
            $precioNuevo,
            $porcentajeCambio,
            $tendencia,
            $usuario,
            $historialReciente
        );

        // Crear notificación de alta prioridad
        $this->crearNotificacionUrgente('cambio_precio', $materialId, $mensaje);
        
        // Notificar a supervisores
        $this->notificarSupervisores($materialId, $mensaje);
    }

    private function obtenerUsuarioUltimoCambio($materialId) {
        $stmt = $this->pdo->prepare("
            SELECT u.* 
            FROM historial_precios h
            JOIN usuarios u ON h.usuario_id = u.id
            WHERE h.material_id = ?
            ORDER BY h.fecha_cambio DESC
            LIMIT 1
        ");
        $stmt->execute([$materialId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function obtenerHistorialReciente($materialId) {
        $stmt = $this->pdo->prepare("
            SELECT h.*,
                   u.nombre as usuario_nombre,
                   ((h.precio_nuevo - h.precio_anterior) / h.precio_anterior * 100) as porcentaje_cambio
            FROM historial_precios h
            JOIN usuarios u ON h.usuario_id = u.id
            WHERE h.material_id = ?
            ORDER BY h.fecha_cambio DESC
            LIMIT 5
        ");
        $stmt->execute([$materialId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function analizarTendencia($historial) {
        if (empty($historial)) {
            return null;
        }

        $tendencia = [
            'direccion' => 'estable',
            'promedio_cambio' => 0,
            'cambios_significativos' => 0,
            'frecuencia' => 'normal'
        ];

        $sumaCambios = 0;
        $cambiosSignificativos = 0;
        $ultimaFecha = null;
        $intervalos = [];

        foreach ($historial as $index => $cambio) {
            $sumaCambios += $cambio['porcentaje_cambio'];
            
            if (abs($cambio['porcentaje_cambio']) > 20) {
                $cambiosSignificativos++;
            }

            if ($ultimaFecha) {
                $intervalo = strtotime($cambio['fecha_cambio']) - strtotime($ultimaFecha);
                $intervalos[] = $intervalo;
            }
            $ultimaFecha = $cambio['fecha_cambio'];
        }

        $tendencia['promedio_cambio'] = $sumaCambios / count($historial);
        $tendencia['cambios_significativos'] = $cambiosSignificativos;

        if ($tendencia['promedio_cambio'] > 10) {
            $tendencia['direccion'] = 'alza';
        } elseif ($tendencia['promedio_cambio'] < -10) {
            $tendencia['direccion'] = 'baja';
        }

        if (!empty($intervalos)) {
            $promedioIntervalo = array_sum($intervalos) / count($intervalos);
            if ($promedioIntervalo < 7 * 24 * 3600) { // menos de 7 días
                $tendencia['frecuencia'] = 'alta';
            } elseif ($promedioIntervalo > 30 * 24 * 3600) { // más de 30 días
                $tendencia['frecuencia'] = 'baja';
            }
        }

        return $tendencia;
    }

    private function construirMensajeCambioSignificativo($material, $precioAnterior, $precioNuevo, $porcentajeCambio, $tendencia, $usuario, $historial) {
        $mensaje = sprintf(
            "⚠️ ALERTA: Cambio significativo de precio detectado\n\n" .
            "Material: %s - %s\n" .
            "Cambio de Precio:\n" .
            "  • Anterior: $%s\n" .
            "  • Nuevo: $%s\n" .
            "  • Variación: %s%% %s\n\n",
            $material['codigo'],
            $material['descripcion'],
            number_format($precioAnterior, 2),
            number_format($precioNuevo, 2),
            number_format(abs($porcentajeCambio), 1),
            $porcentajeCambio > 0 ? '↑' : '↓'
        );

        if ($tendencia) {
            $mensaje .= sprintf(
                "Análisis de Tendencia:\n" .
                "  • Dirección: %s\n" .
                "  • Promedio de cambios: %s%%\n" .
                "  • Frecuencia de cambios: %s\n" .
                "  • Cambios significativos recientes: %d\n\n",
                ucfirst($tendencia['direccion']),
                number_format($tendencia['promedio_cambio'], 1),
                $tendencia['frecuencia'],
                $tendencia['cambios_significativos']
            );
        }

        $mensaje .= "Historial Reciente:\n";
        foreach ($historial as $cambio) {
            $mensaje .= sprintf(
                "  • %s: %s%% por %s\n",
                date('d/m/Y', strtotime($cambio['fecha_cambio'])),
                number_format($cambio['porcentaje_cambio'], 1),
                $cambio['usuario_nombre']
            );
        }

        return $mensaje;
    }

    private function crearNotificacionUrgente($tipo, $materialId, $mensaje) {
        try {
            // Obtener usuarios con rol de supervisor o administrador
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT u.id, u.email, u.nombre
                FROM usuarios u
                JOIN roles_permisos rp ON u.rol_id = rp.rol_id
                JOIN permisos p ON rp.permiso_id = p.id
                WHERE p.codigo IN ('material_supervisor', 'material_admin')
            ");
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($usuarios as $usuario) {
                // Crear notificación urgente
                $stmt = $this->pdo->prepare("
                    INSERT INTO notificaciones_materiales (
                        tipo,
                        material_id,
                        mensaje,
                        usuario_destino_id,
                        prioridad,
                        requiere_accion
                    ) VALUES (?, ?, ?, ?, 'alta', true)
                ");
                $stmt->execute([$tipo, $materialId, $mensaje, $usuario['id']]);

                // Enviar email inmediato
                $this->enviarEmailUrgente($usuario['email'], $usuario['nombre'], $mensaje);
            }
        } catch (Exception $e) {
            error_log("Error al crear notificación urgente: " . $e->getMessage());
        }
    }

    private function enviarEmailUrgente($email, $nombreUsuario, $mensaje) {
        $asunto = "🚨 ALERTA URGENTE - Cambio Significativo de Precio";
        
        $cuerpo = "Hola $nombreUsuario,\n\n";
        $cuerpo .= "Se ha detectado un cambio significativo que requiere tu atención inmediata:\n\n";
        $cuerpo .= $mensaje . "\n\n";
        $cuerpo .= "Por favor, revisa este cambio lo antes posible.\n";
        $cuerpo .= "Para aprobar o rechazar este cambio, ingresa al sistema.\n\n";
        $cuerpo .= "Saludos,\nSistema de Gestión de Materiales";

        $headers = [
            'From' => 'sistema@empresa.com',
            'Reply-To' => 'soporte@empresa.com',
            'X-Priority' => '1 (Highest)',
            'X-MSMail-Priority' => 'High',
            'Importance' => 'High',
            'X-Mailer' => 'PHP/' . phpversion()
        ];

        mail($email, $asunto, $cuerpo, $headers);
    }

    private function notificarSupervisores($materialId, $mensaje) {
        // Implementar notificación adicional para supervisores si es necesario
        // Por ejemplo: SMS, Slack, Teams, etc.
    }

    // Funciones de soporte
    private function obtenerDatosMaterial($materialId) {
        $stmt = $this->pdo->prepare("
            SELECT id, codigo, descripcion, precio_unitario
            FROM materiales
            WHERE id = ?
        ");
        $stmt->execute([$materialId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function obtenerMaterialPorCodigo($codigo) {
        $stmt = $this->pdo->prepare("
            SELECT id, codigo, descripcion
            FROM materiales
            WHERE codigo = ?
        ");
        $stmt->execute([$codigo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function obtenerConfiguracionUsuario($usuarioId) {
        if (!isset($this->configuracionCache[$usuarioId])) {
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM config_notificaciones
                WHERE usuario_id = ?
            ");
            $stmt->execute([$usuarioId]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$config) {
                // Crear configuración por defecto
                $stmt = $this->pdo->prepare("
                    INSERT INTO config_notificaciones (
                        usuario_id,
                        notificar_sin_validar,
                        notificar_error_validacion,
                        notificar_cambio_precio,
                        notificar_codigo_duplicado,
                        email_notificaciones
                    ) VALUES (?, 1, 1, 1, 1, 1)
                ");
                $stmt->execute([$usuarioId]);
                $config = [
                    'usuario_id' => $usuarioId,
                    'notificar_sin_validar' => true,
                    'notificar_error_validacion' => true,
                    'notificar_cambio_precio' => true,
                    'notificar_codigo_duplicado' => true,
                    'email_notificaciones' => true
                ];
            }
            
            $this->configuracionCache[$usuarioId] = $config;
        }
        
        return $this->configuracionCache[$usuarioId];
    }

    private function crearNotificacion($tipo, $materialId, $mensaje) {
        try {
            // Obtener usuarios con rol adecuado
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT u.id, u.email, u.nombre
                FROM usuarios u
                JOIN roles_permisos rp ON u.rol_id = rp.rol_id
                JOIN permisos p ON rp.permiso_id = p.id
                WHERE p.codigo IN ('material_ver', 'material_editar')
            ");
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($usuarios as $usuario) {
                $config = $this->obtenerConfiguracionUsuario($usuario['id']);
                $campoNotificacion = "notificar_" . $tipo;
                
                if ($config[$campoNotificacion]) {
                    // Crear notificación en la base de datos
                    $stmt = $this->pdo->prepare("
                        INSERT INTO notificaciones_materiales (
                            tipo,
                            material_id,
                            mensaje,
                            usuario_destino_id
                        ) VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$tipo, $materialId, $mensaje, $usuario['id']]);

                    // Enviar email si está configurado
                    if ($config['email_notificaciones']) {
                        $this->enviarEmailNotificacion(
                            $usuario['email'],
                            $usuario['nombre'],
                            $tipo,
                            $mensaje
                        );
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error al crear notificación: " . $e->getMessage());
            // No lanzamos la excepción para no interrumpir el flujo principal
        }
    }

    private function enviarEmailNotificacion($email, $nombreUsuario, $tipo, $mensaje) {
        $tiposTraducidos = [
            'sin_validar' => 'Material sin validar',
            'error_validacion' => 'Error de validación',
            'cambio_precio' => 'Cambio de precio',
            'codigo_duplicado' => 'Código duplicado'
        ];

        $asunto = "Sistema de Materiales - " . $tiposTraducidos[$tipo];
        
        $cuerpo = "Hola $nombreUsuario,\n\n";
        $cuerpo .= "Se ha generado una nueva notificación en el sistema:\n\n";
        $cuerpo .= "Tipo: " . $tiposTraducidos[$tipo] . "\n";
        $cuerpo .= "Mensaje:\n" . $mensaje . "\n\n";
        $cuerpo .= "Para ver más detalles o tomar acción, ingrese al sistema.\n\n";
        $cuerpo .= "Saludos,\nSistema de Gestión de Materiales";

        $headers = [
            'From' => 'sistema@empresa.com',
            'Reply-To' => 'soporte@empresa.com',
            'X-Mailer' => 'PHP/' . phpversion()
        ];

        mail($email, $asunto, $cuerpo, $headers);
    }

    // Gestión de notificaciones
    public function marcarComoLeida($notificacionId) {
        $stmt = $this->pdo->prepare("
            UPDATE notificaciones_materiales
            SET fecha_lectura = NOW(),
                estado = 'leida'
            WHERE id = ?
        ");
        $stmt->execute([$notificacionId]);
    }

    public function marcarComoResuelta($notificacionId, $comentario = null) {
        $stmt = $this->pdo->prepare("
            UPDATE notificaciones_materiales
            SET estado = 'resuelta',
                fecha_resolucion = NOW(),
                comentario_resolucion = ?
            WHERE id = ?
        ");
        $stmt->execute([$comentario, $notificacionId]);
    }

    public function obtenerNotificacionesPendientes($usuarioId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                n.*,
                m.codigo,
                m.descripcion,
                m.precio_unitario
            FROM notificaciones_materiales n
            JOIN materiales m ON n.material_id = m.id
            WHERE n.usuario_destino_id = ?
            AND n.estado = 'pendiente'
            ORDER BY n.fecha_creacion DESC
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerEstadisticasNotificaciones($usuarioId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                tipo,
                estado,
                COUNT(*) as cantidad
            FROM notificaciones_materiales
            WHERE usuario_destino_id = ?
            AND fecha_creacion >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
            GROUP BY tipo, estado
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 