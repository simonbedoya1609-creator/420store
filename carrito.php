<?php
/**
 * =====================================================
 * ARCHIVO: carrito.php
 * DESCRIPCIÓN: Gestión del carrito en base de datos
 * PROYECTO: 420 Store
 * =====================================================
 */

require_once 'conexion.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

iniciarSesion();

// Verificar que haya sesión activa
if (!sesionActiva()) {
    respuestaJSON(false, 'Debes iniciar sesión para usar el carrito');
}

$accion = isset($_GET['accion']) ? $_GET['accion'] : '';
$usuario_id = $_SESSION['usuario_id'];

try {
    switch ($accion) {
        
        // =====================================================
        // OBTENER CARRITO DEL USUARIO
        // =====================================================
        case 'obtener':
            $sql = "SELECT c.id, c.cantidad, p.id as producto_id, p.nombre, p.precio, p.imagen_url
                    FROM carrito c
                    INNER JOIN productos p ON c.producto_id = p.id
                    WHERE c.usuario_id = ?
                    ORDER BY c.fecha_agregado DESC";
            
            $items = obtenerTodos($sql, [$usuario_id]);
            
            $total = 0;
            foreach ($items as $item) {
                $total += $item['precio'] * $item['cantidad'];
            }
            
            respuestaJSON(true, 'Carrito obtenido', [
                'items' => $items,
                'total' => $total,
                'cantidad_items' => count($items)
            ]);
            break;
        
        // =====================================================
        // AGREGAR PRODUCTO AL CARRITO
        // =====================================================
        case 'agregar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respuestaJSON(false, 'Método no permitido');
            }
            
            $producto_id = isset($_POST['producto_id']) ? intval($_POST['producto_id']) : 0;
            $cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;
            
            if ($producto_id <= 0) {
                respuestaJSON(false, 'ID de producto inválido');
            }
            
            // Verificar que el producto existe
            $sql = "SELECT id, nombre, stock FROM productos WHERE id = ?";
            $producto = obtenerUno($sql, [$producto_id]);
            
            if (!$producto) {
                respuestaJSON(false, 'Producto no encontrado');
            }
            
            if ($producto['stock'] < $cantidad) {
                respuestaJSON(false, 'Stock insuficiente');
            }
            
            // Verificar si ya existe en el carrito
            $sql = "SELECT id, cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ?";
            $item_existente = obtenerUno($sql, [$usuario_id, $producto_id]);
            
            if ($item_existente) {
                // Actualizar cantidad
                $nueva_cantidad = $item_existente['cantidad'] + $cantidad;
                $sql = "UPDATE carrito SET cantidad = ? WHERE id = ?";
                ejecutarConsulta($sql, [$nueva_cantidad, $item_existente['id']]);
            } else {
                // Insertar nuevo item
                $sql = "INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)";
                insertar($sql, [$usuario_id, $producto_id, $cantidad]);
            }
            
            registrarLog("Producto agregado al carrito: Usuario $usuario_id, Producto $producto_id", 'info');
            
            respuestaJSON(true, 'Producto agregado al carrito', [
                'producto_nombre' => $producto['nombre']
            ]);
            break;
        
        // =====================================================
        // ACTUALIZAR CANTIDAD DE UN ITEM
        // =====================================================
        case 'actualizar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respuestaJSON(false, 'Método no permitido');
            }
            
            $carrito_id = isset($_POST['carrito_id']) ? intval($_POST['carrito_id']) : 0;
            $cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;
            
            if ($cantidad <= 0) {
                respuestaJSON(false, 'Cantidad inválida');
            }
            
            // Verificar que el item pertenece al usuario
            $sql = "SELECT c.id, p.stock 
                    FROM carrito c
                    INNER JOIN productos p ON c.producto_id = p.id
                    WHERE c.id = ? AND c.usuario_id = ?";
            $item = obtenerUno($sql, [$carrito_id, $usuario_id]);
            
            if (!$item) {
                respuestaJSON(false, 'Item no encontrado en tu carrito');
            }
            
            if ($item['stock'] < $cantidad) {
                respuestaJSON(false, 'Stock insuficiente');
            }
            
            $sql = "UPDATE carrito SET cantidad = ? WHERE id = ?";
            ejecutarConsulta($sql, [$cantidad, $carrito_id]);
            
            respuestaJSON(true, 'Cantidad actualizada');
            break;
        
        // =====================================================
        // ELIMINAR ITEM DEL CARRITO
        // =====================================================
        case 'eliminar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respuestaJSON(false, 'Método no permitido');
            }
            
            $carrito_id = isset($_POST['carrito_id']) ? intval($_POST['carrito_id']) : 0;
            
            // Verificar que el item pertenece al usuario
            $sql = "SELECT id FROM carrito WHERE id = ? AND usuario_id = ?";
            $item = obtenerUno($sql, [$carrito_id, $usuario_id]);
            
            if (!$item) {
                respuestaJSON(false, 'Item no encontrado en tu carrito');
            }
            
            $sql = "DELETE FROM carrito WHERE id = ?";
            ejecutarConsulta($sql, [$carrito_id]);
            
            respuestaJSON(true, 'Producto eliminado del carrito');
            break;
        
        // =====================================================
        // VACIAR CARRITO
        // =====================================================
        case 'vaciar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respuestaJSON(false, 'Método no permitido');
            }
            
            $sql = "DELETE FROM carrito WHERE usuario_id = ?";
            ejecutarConsulta($sql, [$usuario_id]);
            
            registrarLog("Carrito vaciado: Usuario $usuario_id", 'info');
            
            respuestaJSON(true, 'Carrito vaciado');
            break;
        
        // =====================================================
        // SINCRONIZAR CARRITO DE LOCALSTORAGE A BD
        // =====================================================
        case 'sincronizar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respuestaJSON(false, 'Método no permitido');
            }
            
            $items_local = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
            
            if (!is_array($items_local)) {
                respuestaJSON(false, 'Datos inválidos');
            }
            
            $sincronizados = 0;
            
            foreach ($items_local as $item) {
                if (!isset($item['producto_id']) || !isset($item['cantidad'])) {
                    continue;
                }
                
                $producto_id = intval($item['producto_id']);
                $cantidad = intval($item['cantidad']);
                
                // Verificar si ya existe
                $sql = "SELECT id FROM carrito WHERE usuario_id = ? AND producto_id = ?";
                $existe = obtenerUno($sql, [$usuario_id, $producto_id]);
                
                if (!$existe) {
                    $sql = "INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)";
                    insertar($sql, [$usuario_id, $producto_id, $cantidad]);
                    $sincronizados++;
                }
            }
            
            respuestaJSON(true, "Carrito sincronizado: $sincronizados items agregados");
            break;
        
        default:
            respuestaJSON(false, 'Acción no válida');
    }
    
} catch (Exception $e) {
    registrarLog("Error en carrito: " . $e->getMessage(), 'error');
    respuestaJSON(false, 'Error al procesar la solicitud');
}
?>