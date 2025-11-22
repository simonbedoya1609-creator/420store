<?php
require_once 'conexion.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

iniciarSesion();

if (!sesionActiva()) {
    respuestaJSON(false, 'Debes iniciar sesión');
}

$accion = isset($_GET['accion']) ? $_GET['accion'] : '';
$usuario_id = $_SESSION['usuario_id'];

try {
    switch ($accion) {
        
        // =====================================================
        // PROCESAR PEDIDO (COMPRA)
        // =====================================================
        case 'procesar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respuestaJSON(false, 'Método no permitido');
            }
            
            // Obtener items del carrito
            $sql = "SELECT c.id as carrito_id, c.cantidad, c.producto_id,
                           p.nombre, p.precio, p.stock
                    FROM carrito c
                    INNER JOIN productos p ON c.producto_id = p.id
                    WHERE c.usuario_id = ?";
            
            $items = obtenerTodos($sql, [$usuario_id]);
            
            if (empty($items)) {
                respuestaJSON(false, 'El carrito está vacío');
            }
            
            // Verificar stock de todos los productos
            foreach ($items as $item) {
                if ($item['stock'] < $item['cantidad']) {
                    respuestaJSON(false, "Stock insuficiente para {$item['nombre']}");
                }
            }
            
            // Calcular total
            $total = 0;
            foreach ($items as $item) {
                $total += $item['precio'] * $item['cantidad'];
            }
            
            // Obtener nivel del usuario para calcular puntos
            $sql = "SELECT nivel, puntos FROM usuarios WHERE id = ?";
            $usuario = obtenerUno($sql, [$usuario_id]);
            
            $puntos_bonus = match($usuario['nivel']) {
                'Diamante' => 20,
                'Oro' => 15,
                'Plata' => 10,
                default => 5
            };
            
            // Iniciar transacción
            $db = getDB();
            $db->beginTransaction();
            
            try {
                // Crear pedido
                $sql = "INSERT INTO pedidos (usuario_id, total, puntos_ganados, estado) 
                        VALUES (?, ?, ?, 'pagado')";
                $pedido_id = insertar($sql, [$usuario_id, $total, $puntos_bonus]);
                
                // Insertar detalles del pedido y actualizar stock
                foreach ($items as $item) {
                    // Detalle del pedido
                    $sql = "INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario)
                            VALUES (?, ?, ?, ?)";
                    insertar($sql, [$pedido_id, $item['producto_id'], $item['cantidad'], $item['precio']]);
                    
                    // Actualizar stock
                    $sql = "UPDATE productos SET stock = stock - ? WHERE id = ?";
                    ejecutarConsulta($sql, [$item['cantidad'], $item['producto_id']]);
                }
                
                // Actualizar puntos del usuario
                $nuevos_puntos = $usuario['puntos'] + $puntos_bonus;
                $sql = "UPDATE usuarios SET puntos = ?, ultima_conexion = NOW() WHERE id = ?";
                ejecutarConsulta($sql, [$nuevos_puntos, $usuario_id]);
                
                // Vaciar carrito
                $sql = "DELETE FROM carrito WHERE usuario_id = ?";
                ejecutarConsulta($sql, [$usuario_id]);
                
                // Crear notificación
                crearNotificacion(
                    $usuario_id,
                    'exito',
                    '¡Compra exitosa! 🎉',
                    "Has ganado $puntos_bonus puntos. Total: $nuevos_puntos puntos. Pedido #$pedido_id"
                );
                
                // Confirmar transacción
                $db->commit();
                
                registrarLog("Pedido procesado: Usuario $usuario_id, Pedido #$pedido_id, Total: $total", 'info');
                
                respuestaJSON(true, '¡Compra realizada exitosamente!', [
                    'pedido_id' => $pedido_id,
                    'total' => $total,
                    'puntos_ganados' => $puntos_bonus,
                    'puntos_totales' => $nuevos_puntos,
                    'nivel' => $usuario['nivel']
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;
        
        // =====================================================
        // OBTENER HISTORIAL DE PEDIDOS
        // =====================================================
        case 'historial':
            $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 10;
            
            $sql = "SELECT id, total, puntos_ganados, estado, fecha_pedido
                    FROM pedidos
                    WHERE usuario_id = ?
                    ORDER BY fecha_pedido DESC
                    LIMIT ?";
            
            $pedidos = obtenerTodos($sql, [$usuario_id, $limite]);
            
            respuestaJSON(true, 'Historial obtenido', [
                'pedidos' => $pedidos,
                'total_pedidos' => count($pedidos)
            ]);
            break;
        
        // =====================================================
        // OBTENER DETALLES DE UN PEDIDO
        // =====================================================
        case 'detalle':
            $pedido_id = isset($_GET['pedido_id']) ? intval($_GET['pedido_id']) : 0;
            
            // Verificar que el pedido pertenece al usuario
            $sql = "SELECT id, total, puntos_ganados, estado, fecha_pedido
                    FROM pedidos
                    WHERE id = ? AND usuario_id = ?";
            $pedido = obtenerUno($sql, [$pedido_id, $usuario_id]);
            
            if (!$pedido) {
                respuestaJSON(false, 'Pedido no encontrado');
            }
            
            // Obtener items del pedido
            $sql = "SELECT dp.cantidad, dp.precio_unitario,
                           p.nombre, p.imagen_url
                    FROM detalle_pedidos dp
                    INNER JOIN productos p ON dp.producto_id = p.id
                    WHERE dp.pedido_id = ?";
            
            $items = obtenerTodos($sql, [$pedido_id]);
            
            respuestaJSON(true, 'Detalle del pedido', [
                'pedido' => $pedido,
                'items' => $items
            ]);
            break;
        
        // =====================================================
        // OBTENER ESTADÍSTICAS DEL USUARIO
        // =====================================================
        case 'estadisticas':
            // Total gastado
            $sql = "SELECT COALESCE(SUM(total), 0) as total_gastado, 
                           COUNT(*) as total_pedidos
                    FROM pedidos
                    WHERE usuario_id = ? AND estado != 'cancelado'";
            $stats = obtenerUno($sql, [$usuario_id]);
            
            // Último pedido
            $sql = "SELECT fecha_pedido FROM pedidos WHERE usuario_id = ? ORDER BY fecha_pedido DESC LIMIT 1";
            $ultimo = obtenerUno($sql, [$usuario_id]);
            
            respuestaJSON(true, 'Estadísticas obtenidas', [
                'total_gastado' => $stats['total_gastado'],
                'total_pedidos' => $stats['total_pedidos'],
                'ultimo_pedido' => $ultimo ? $ultimo['fecha_pedido'] : null
            ]);
            break;
        
        default:
            respuestaJSON(false, 'Acción no válida');
    }
    
} catch (Exception $e) {
    registrarLog("Error en pedidos: " . $e->getMessage(), 'error');
    respuestaJSON(false, 'Error al procesar la solicitud: ' . $e->getMessage());
}
?>