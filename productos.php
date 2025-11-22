<?php
require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

$accion = isset($_GET['accion']) ? $_GET['accion'] : 'listar';

try {
    switch ($accion) {
        
        // =====================================================
        // LISTAR PRODUCTOS
        // =====================================================
        case 'listar':
            $categoria = isset($_GET['categoria']) ? limpiarEntrada($_GET['categoria']) : '';
            $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 100;
            $orden = isset($_GET['orden']) ? limpiarEntrada($_GET['orden']) : 'nombre';
            
            // Validar orden
            $ordenes_validos = ['nombre', 'precio', 'stock', 'fecha_creacion'];
            if (!in_array($orden, $ordenes_validos)) {
                $orden = 'nombre';
            }
            
            if (!empty($categoria)) {
                $sql = "SELECT * FROM productos 
                        WHERE categoria = ? AND estado = 'disponible' 
                        ORDER BY $orden ASC 
                        LIMIT ?";
                $productos = obtenerTodos($sql, [$categoria, $limite]);
            } else {
                $sql = "SELECT * FROM productos 
                        WHERE estado = 'disponible' 
                        ORDER BY $orden ASC 
                        LIMIT ?";
                $productos = obtenerTodos($sql, [$limite]);
            }
            
            respuestaJSON(true, 'Productos obtenidos', [
                'productos' => $productos,
                'total' => count($productos)
            ]);
            break;
        
        // =====================================================
        // OBTENER UN PRODUCTO
        // =====================================================
        case 'obtener':
            if (!isset($_GET['id'])) {
                respuestaJSON(false, 'ID de producto no proporcionado');
            }
            
            $id = intval($_GET['id']);
            $sql = "SELECT * FROM productos WHERE id = ?";
            $producto = obtenerUno($sql, [$id]);
            
            if ($producto) {
                respuestaJSON(true, 'Producto encontrado', ['producto' => $producto]);
            } else {
                respuestaJSON(false, 'Producto no encontrado');
            }
            break;
        
        // =====================================================
        // BUSCAR PRODUCTOS
        // =====================================================
        case 'buscar':
            if (!isset($_GET['termino'])) {
                respuestaJSON(false, 'Término de búsqueda no proporcionado');
            }
            
            $termino = limpiarEntrada($_GET['termino']);
            $sql = "SELECT * FROM productos 
                    WHERE (nombre LIKE ? OR descripcion LIKE ?) 
                    AND estado = 'disponible' 
                    ORDER BY nombre ASC 
                    LIMIT 50";
            
            $busqueda = "%$termino%";
            $productos = obtenerTodos($sql, [$busqueda, $busqueda]);
            
            respuestaJSON(true, 'Búsqueda completada', [
                'productos' => $productos,
                'total' => count($productos)
            ]);
            break;
        
        // =====================================================
        // OBTENER CATEGORÍAS
        // =====================================================
        case 'categorias':
            $sql = "SELECT DISTINCT categoria FROM productos WHERE estado = 'disponible' ORDER BY categoria";
            $stmt = ejecutarConsulta($sql);
            $categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            respuestaJSON(true, 'Categorías obtenidas', [
                'categorias' => $categorias
            ]);
            break;
        
        // =====================================================
        // AGREGAR PRODUCTO (Solo administradores/vendedores)
        // =====================================================
        case 'agregar':
            iniciarSesion();
            
            // Verificar que sea vendedor o admin
            if (!isset($_SESSION['vendedor_id']) && !isset($_SESSION['admin_id'])) {
                respuestaJSON(false, 'No tienes permisos para agregar productos');
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respuestaJSON(false, 'Método no permitido');
            }
            
            $nombre = limpiarEntrada($_POST['nombre']);
            $descripcion = limpiarEntrada($_POST['descripcion']);
            $precio = floatval($_POST['precio']);
            $imagen_url = limpiarEntrada($_POST['imagen_url']);
            $stock = intval($_POST['stock']);
            $categoria = limpiarEntrada($_POST['categoria']);
            
            if (empty($nombre) || $precio <= 0) {
                respuestaJSON(false, 'Datos incompletos o inválidos');
            }
            
            $sql = "INSERT INTO productos (nombre, descripcion, precio, imagen_url, stock, categoria) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $id = insertar($sql, [$nombre, $descripcion, $precio, $imagen_url, $stock, $categoria]);
            
            if ($id) {
                registrarLog("Producto agregado: $nombre (ID: $id)", 'info');
                respuestaJSON(true, 'Producto agregado exitosamente', ['producto_id' => $id]);
            } else {
                respuestaJSON(false, 'Error al agregar producto');
            }
            break;
        
        // =====================================================
        // EDITAR PRODUCTO (Solo administradores/vendedores)
        // =====================================================
        case 'editar':
            iniciarSesion();
            
            if (!isset($_SESSION['vendedor_id']) && !isset($_SESSION['admin_id'])) {
                respuestaJSON(false, 'No tienes permisos para editar productos');
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respuestaJSON(false, 'Método no permitido');
            }
            
            $id = intval($_POST['id']);
            $nombre = limpiarEntrada($_POST['nombre']);
            $descripcion = limpiarEntrada($_POST['descripcion']);
            $precio = floatval($_POST['precio']);
            $stock = intval($_POST['stock']);
            
            $sql = "UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ? WHERE id = ?";
            
            if (ejecutarConsulta($sql, [$nombre, $descripcion, $precio, $stock, $id])) {
                registrarLog("Producto editado: $nombre (ID: $id)", 'info');
                respuestaJSON(true, 'Producto actualizado');
            } else {
                respuestaJSON(false, 'Error al actualizar producto');
            }
            break;
        
        // =====================================================
        // ELIMINAR PRODUCTO (Solo administradores)
        // =====================================================
        case 'eliminar':
            iniciarSesion();
            
            if (!isset($_SESSION['admin_id'])) {
                respuestaJSON(false, 'No tienes permisos para eliminar productos');
            }
            
            if (!isset($_POST['id'])) {
                respuestaJSON(false, 'ID no proporcionado');
            }
            
            $id = intval($_POST['id']);
            
            // En lugar de eliminar, marcar como descontinuado
            $sql = "UPDATE productos SET estado = 'descontinuado' WHERE id = ?";
            
            if (ejecutarConsulta($sql, [$id])) {
                registrarLog("Producto eliminado (ID: $id)", 'info');
                respuestaJSON(true, 'Producto eliminado');
            } else {
                respuestaJSON(false, 'Error al eliminar producto');
            }
            break;
        
        default:
            respuestaJSON(false, 'Acción no válida');
    }
    
} catch (Exception $e) {
    registrarLog("Error en productos: " . $e->getMessage(), 'error');
    respuestaJSON(false, 'Error al procesar la solicitud');
}
?>