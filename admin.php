<?php
/**
 * =====================================================
 * ARCHIVO: admin.php
 * DESCRIPCIÓN: Panel de administración
 * PROYECTO: 420 Store
 * =====================================================
 */

require_once 'conexion.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

iniciarSesion();

// Verificar que sea administrador (por ahora simplificado)
// En producción deberías tener una tabla de administradores
$es_admin = isset($_SESSION['admin_id']) || isset($_SESSION['vendedor_id']);

$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

try {
    switch ($accion) {
        
        // =====================================================
        // LISTAR USUARIOS
        // =====================================================
        case 'listar_usuarios':
            $sql = "SELECT id, nombre, email, puntos, nivel, estado, fecha_registro, ultima_conexion
                    FROM usuarios
                    ORDER BY fecha_registro DESC";
            
            $usuarios = obtenerTodos($sql);
            
            respuestaJSON(true, 'Usuarios obtenidos', [
                'usuarios' => $usuarios,
                'total' => count($usuarios)
            ]);
            break;

            // AGREGAR ESTE CASE en admin.php después del case 'listar_usuarios':

        case 'listar_vendedores':
            $sql = "SELECT id, nombre, email, telefono, estado, fecha_registro
                    FROM vendedores
                    ORDER BY fecha_registro DESC";
    
           $vendedores = obtenerTodos($sql);
    
    respuestaJSON(true, 'Vendedores obtenidos', [
        'vendedores' => $vendedores,
        'total' => count($vendedores)
    ]);
    break;
        
        // =====================================================
        // BLOQUEAR/DESBLOQUEAR USUARIO
        // =====================================================
        case 'cambiar_estado_usuario':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respuestaJSON(false, 'Método no permitido');
            }
            
            $usuario_id = isset($_POST['usuario_id']) ? intval($_POST['usuario_id']) : 0;
            $nuevo_estado = isset($_POST['estado']) ? limpiarEntrada($_POST['estado']) : '';
            
            if (!in_array($nuevo_estado, ['activo', 'bloqueado'])) {
                respuestaJSON(false, 'Estado inválido');
            }
            
            $sql = "UPDATE usuarios SET estado = ? WHERE id = ?";
            ejecutarConsulta($sql, [$nuevo_estado, $usuario_id]);
            
            registrarLog("Estado de usuario cambiado: ID $usuario_id a $nuevo_estado", 'info');
            
            respuestaJSON(true, "Usuario $nuevo_estado");
            break;
        
        // =====================================================
        // AGREGAR PRODUCTO
        // =====================================================
        case 'agregar_producto':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respuestaJSON(false, 'Método no permitido');
            }
            
            $nombre = limpiarEntrada($_POST['nombre']);
            $descripcion = limpiarEntrada($_POST['descripcion']);
            $precio = floatval($_POST['precio']);
            $imagen_url = limpiarEntrada($_POST['imagen_url']);
            $stock = intval($_POST['stock']);
            $categoria = limpiarEntrada($_POST['categoria']);
            $descuento = isset($_POST['descuento']) ? intval($_POST['descuento']) : 0;
            
            if (empty($nombre) || $precio <= 0) {
                respuestaJSON(false, 'Datos incompletos o inválidos');
            }
            
            $sql = "INSERT INTO productos (nombre, descripcion, precio, imagen_url, stock, categoria, descuento) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $id = insertar($sql, [$nombre, $descripcion, $precio, $imagen_url, $stock, $categoria, $descuento]);
            
            if ($id) {
                registrarLog("Producto agregado: $nombre (ID: $id)", 'info');
                respuestaJSON(true, 'Producto agregado exitosamente', ['producto_id' => $id]);
            } else {
                respuestaJSON(false, 'Error al agregar producto');
            }
            break;
        
        // =====================================================
        // EDITAR PRODUCTO
        // =====================================================
        case 'editar_producto':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respuestaJSON(false, 'Método no permitido');
            }
            
            $id = intval($_POST['id']);
            $nombre = limpiarEntrada($_POST['nombre']);
            $descripcion = limpiarEntrada($_POST['descripcion']);
            $precio = floatval($_POST['precio']);
            $stock = intval($_POST['stock']);
            $categoria = limpiarEntrada($_POST['categoria']);
            $descuento = intval($_POST['descuento']);
            
            $sql = "UPDATE productos 
                    SET nombre = ?, descripcion = ?, precio = ?, stock = ?, categoria = ?, descuento = ?
                    WHERE id = ?";
            
            if (ejecutarConsulta($sql, [$nombre, $descripcion, $precio, $stock, $categoria, $descuento, $id])) {
                registrarLog("Producto editado: $nombre (ID: $id)", 'info');
                respuestaJSON(true, 'Producto actualizado');
            } else {
                respuestaJSON(false, 'Error al actualizar producto');
            }
            break;
        
        // =====================================================
        // ELIMINAR PRODUCTO
        // =====================================================
        case 'eliminar_producto':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respuestaJSON(false, 'Método no permitido');
            }
            
            $id = intval($_POST['id']);
            
            // Marcar como descontinuado en lugar de eliminar
            $sql = "UPDATE productos SET estado = 'descontinuado' WHERE id = ?";
            
            if (ejecutarConsulta($sql, [$id])) {
                registrarLog("Producto eliminado (ID: $id)", 'info');
                respuestaJSON(true, 'Producto eliminado');
            } else {
                respuestaJSON(false, 'Error al eliminar producto');
            }
            break;
        
        // =====================================================
        // ESTADÍSTICAS GENERALES
        // =====================================================
        case 'estadisticas':
            // Total usuarios
            $sql = "SELECT COUNT(*) as total FROM usuarios WHERE estado = 'activo'";
            $total_usuarios = obtenerUno($sql)['total'];
            
            // Total productos
            $sql = "SELECT COUNT(*) as total FROM productos WHERE estado = 'disponible'";
            $total_productos = obtenerUno($sql)['total'];
            
            // Total pedidos
            $sql = "SELECT COUNT(*) as total, COALESCE(SUM(total), 0) as ventas_totales 
                    FROM pedidos WHERE estado != 'cancelado'";
            $stats_pedidos = obtenerUno($sql);
            
            // Pedidos del mes
            $sql = "SELECT COUNT(*) as total, COALESCE(SUM(total), 0) as ventas_mes
                    FROM pedidos 
                    WHERE estado != 'cancelado' 
                    AND MONTH(fecha_pedido) = MONTH(CURRENT_DATE())
                    AND YEAR(fecha_pedido) = YEAR(CURRENT_DATE())";
            $stats_mes = obtenerUno($sql);
            
            // Productos más vendidos
            $sql = "SELECT p.nombre, SUM(dp.cantidad) as total_vendido
                    FROM detalle_pedidos dp
                    INNER JOIN productos p ON dp.producto_id = p.id
                    GROUP BY dp.producto_id
                    ORDER BY total_vendido DESC
                    LIMIT 5";
            $mas_vendidos = obtenerTodos($sql);
            
            // Usuarios más activos
            $sql = "SELECT u.nombre, u.email, COUNT(pe.id) as total_pedidos, SUM(pe.total) as total_gastado
                    FROM usuarios u
                    INNER JOIN pedidos pe ON u.id = pe.usuario_id
                    WHERE pe.estado != 'cancelado'
                    GROUP BY u.id
                    ORDER BY total_gastado DESC
                    LIMIT 5";
            $usuarios_activos = obtenerTodos($sql);
            
            respuestaJSON(true, 'Estadísticas obtenidas', [
                'total_usuarios' => $total_usuarios,
                'total_productos' => $total_productos,
                'total_pedidos' => $stats_pedidos['total'],
                'ventas_totales' => $stats_pedidos['ventas_totales'],
                'pedidos_mes' => $stats_mes['total'],
                'ventas_mes' => $stats_mes['ventas_mes'],
                'productos_mas_vendidos' => $mas_vendidos,
                'usuarios_mas_activos' => $usuarios_activos
            ]);
            break;
        
        // =====================================================
        // LISTAR PEDIDOS
        // =====================================================
        case 'listar_pedidos':
            $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 50;
            
            $sql = "SELECT p.id, p.total, p.puntos_ganados, p.estado, p.fecha_pedido,
                           u.nombre as usuario_nombre, u.email as usuario_email
                    FROM pedidos p
                    INNER JOIN usuarios u ON p.usuario_id = u.id
                    ORDER BY p.fecha_pedido DESC
                    LIMIT ?";
            
            $pedidos = obtenerTodos($sql, [$limite]);
            
            respuestaJSON(true, 'Pedidos obtenidos', [
                'pedidos' => $pedidos,
                'total' => count($pedidos)
            ]);
            break;
        
        // =====================================================
        // CAMBIAR ESTADO DE PEDIDO
        // =====================================================
        case 'cambiar_estado_pedido':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respuestaJSON(false, 'Método no permitido');
            }
            
            $pedido_id = intval($_POST['pedido_id']);
            $nuevo_estado = limpiarEntrada($_POST['estado']);
            
            $estados_validos = ['pendiente', 'pagado', 'enviado', 'entregado', 'cancelado'];
            if (!in_array($nuevo_estado, $estados_validos)) {
                respuestaJSON(false, 'Estado inválido');
            }
            
            $sql = "UPDATE pedidos SET estado = ? WHERE id = ?";
            ejecutarConsulta($sql, [$nuevo_estado, $pedido_id]);
            
            // Notificar al usuario
            $sql = "SELECT usuario_id FROM pedidos WHERE id = ?";
            $pedido = obtenerUno($sql, [$pedido_id]);
            
            if ($pedido) {
                crearNotificacion(
                    $pedido['usuario_id'],
                    'info',
                    'Estado de pedido actualizado',
                    "Tu pedido #$pedido_id está ahora en estado: $nuevo_estado"
                );
            }
            
            registrarLog("Estado de pedido cambiado: Pedido #$pedido_id a $nuevo_estado", 'info');
            
            respuestaJSON(true, 'Estado actualizado');
            break;
        
        default:
            respuestaJSON(false, 'Acción no válida');
    }
    
} catch (Exception $e) {
    registrarLog("Error en admin: " . $e->getMessage(), 'error');
    respuestaJSON(false, 'Error al procesar la solicitud');
}
?>