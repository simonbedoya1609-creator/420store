<?php
/**
 * =====================================================
 * ARCHIVO: notificaciones.php
 * DESCRIPCIÓN: Gestión de notificaciones de usuarios
 * PROYECTO: 420 Store
 * =====================================================
 */

require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

iniciarSesion();

// Verificar que haya sesión activa
if (!sesionActiva()) {
    respuestaJSON(false, 'Debes iniciar sesión');
}

$accion = isset($_GET['accion']) ? $_GET['accion'] : '';
$usuario_id = $_SESSION['usuario_id'];

try {
    switch ($accion) {
        
        // =====================================================
        // OBTENER NOTIFICACIONES
        // =====================================================
        case 'obtener':
            $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 10;
            $solo_no_leidas = isset($_GET['no_leidas']) && $_GET['no_leidas'] === '1';
            
            if ($solo_no_leidas) {
                $sql = "SELECT * FROM notificaciones 
                        WHERE usuario_id = ? AND leida = 0 
                        ORDER BY fecha_creacion DESC 
                        LIMIT ?";
            } else {
                $sql = "SELECT * FROM notificaciones 
                        WHERE usuario_id = ? 
                        ORDER BY fecha_creacion DESC 
                        LIMIT ?";
            }
            
            $notificaciones = obtenerTodos($sql, [$usuario_id, $limite]);
            
            // Contar no leídas
            $sql_count = "SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = ? AND leida = 0";
            $count_result = obtenerUno($sql_count, [$usuario_id]);
            $total_no_leidas = $count_result['total'];
            
            respuestaJSON(true, 'Notificaciones obtenidas', [
                'notificaciones' => $notificaciones,
                'total_no_leidas' => $total_no_leidas
            ]);
            break;
        
        // =====================================================
        // MARCAR COMO LEÍDA
        // =====================================================
        case 'marcar_leida':
            if (!isset($_POST['notificacion_id'])) {
                respuestaJSON(false, 'ID de notificación no proporcionado');
            }
            
            $notificacion_id = intval($_POST['notificacion_id']);
            
            // Verificar que la notificación pertenece al usuario
            $sql = "SELECT id FROM notificaciones WHERE id = ? AND usuario_id = ?";
            $existe = obtenerUno($sql, [$notificacion_id, $usuario_id]);
            
            if (!$existe) {
                respuestaJSON(false, 'Notificación no encontrada');
            }
            
            if (marcarNotificacionLeida($notificacion_id)) {
                respuestaJSON(true, 'Notificación marcada como leída');
            } else {
                respuestaJSON(false, 'Error al marcar notificación');
            }
            break;
        
        // =====================================================
        // MARCAR TODAS COMO LEÍDAS
        // =====================================================
        case 'marcar_todas_leidas':
            $sql = "UPDATE notificaciones SET leida = 1 WHERE usuario_id = ?";
            
            if (ejecutarConsulta($sql, [$usuario_id])) {
                respuestaJSON(true, 'Todas las notificaciones fueron marcadas como leídas');
            } else {
                respuestaJSON(false, 'Error al marcar notificaciones');
            }
            break;
        
        // =====================================================
        // ELIMINAR NOTIFICACIÓN
        // =====================================================
        case 'eliminar':
            if (!isset($_POST['notificacion_id'])) {
                respuestaJSON(false, 'ID de notificación no proporcionado');
            }
            
            $notificacion_id = intval($_POST['notificacion_id']);
            
            // Verificar que la notificación pertenece al usuario
            $sql = "DELETE FROM notificaciones WHERE id = ? AND usuario_id = ?";
            
            if (ejecutarConsulta($sql, [$notificacion_id, $usuario_id])) {
                respuestaJSON(true, 'Notificación eliminada');
            } else {
                respuestaJSON(false, 'Error al eliminar notificación');
            }
            break;
        
        // =====================================================
        // ELIMINAR TODAS LAS LEÍDAS
        // =====================================================
        case 'eliminar_leidas':
            $sql = "DELETE FROM notificaciones WHERE usuario_id = ? AND leida = 1";
            
            if (ejecutarConsulta($sql, [$usuario_id])) {
                respuestaJSON(true, 'Notificaciones leídas eliminadas');
            } else {
                respuestaJSON(false, 'Error al eliminar notificaciones');
            }
            break;
        
        // =====================================================
        // CREAR NOTIFICACIÓN (Para testing)
        // =====================================================
        case 'crear':
            if (!isset($_POST['tipo']) || !isset($_POST['titulo']) || !isset($_POST['mensaje'])) {
                respuestaJSON(false, 'Datos incompletos');
            }
            
            $tipo = limpiarEntrada($_POST['tipo']);
            $titulo = limpiarEntrada($_POST['titulo']);
            $mensaje = limpiarEntrada($_POST['mensaje']);
            
            // Validar tipo
            $tipos_validos = ['exito', 'info', 'advertencia', 'error'];
            if (!in_array($tipo, $tipos_validos)) {
                $tipo = 'info';
            }
            
            if (crearNotificacion($usuario_id, $tipo, $titulo, $mensaje)) {
                respuestaJSON(true, 'Notificación creada');
            } else {
                respuestaJSON(false, 'Error al crear notificación');
            }
            break;
        
        default:
            respuestaJSON(false, 'Acción no válida');
    }
    
} catch (Exception $e) {
    registrarLog("Error en notificaciones: " . $e->getMessage(), 'error');
    respuestaJSON(false, 'Error al procesar la solicitud');
}
?>