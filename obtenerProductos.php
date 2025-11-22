<?php
/**
 * =====================================================
 * ARCHIVO: obtenerProductos.php
 * DESCRIPCIÓN: Obtener productos desde la base de datos
 * PROYECTO: 420 Store
 * =====================================================
 */

require_once 'conexion.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

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
            $busqueda = isset($_GET['busqueda']) ? limpiarEntrada($_GET['busqueda']) : '';
            
            // Validar orden
            $ordenes_validos = ['nombre', 'precio', 'fecha_creacion', 'stock'];
            if (!in_array($orden, $ordenes_validos)) {
                $orden = 'nombre';
            }
            
            // Construir consulta
            $where = ["estado = 'disponible'"];
            $params = [];
            
            if (!empty($categoria)) {
                $where[] = "categoria = ?";
                $params[] = $categoria;
            }
            
            if (!empty($busqueda)) {
                $where[] = "(nombre LIKE ? OR descripcion LIKE ?)";
                $params[] = "%$busqueda%";
                $params[] = "%$busqueda%";
            }
            
            $where_clause = implode(' AND ', $where);
            
            $sql = "SELECT id, nombre, descripcion, precio, imagen_url, stock, categoria, descuento
                    FROM productos 
                    WHERE $where_clause
                    ORDER BY $orden ASC 
                    LIMIT ?";
            
            $params[] = $limite;
            $productos = obtenerTodos($sql, $params);
            
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
        // OBTENER PRODUCTOS DESTACADOS
        // =====================================================
        case 'destacados':
            $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 6;
            
            $sql = "SELECT id, nombre, descripcion, precio, imagen_url, stock, categoria
                    FROM productos 
                    WHERE estado = 'disponible' 
                    ORDER BY fecha_creacion DESC 
                    LIMIT ?";
            
            $productos = obtenerTodos($sql, [$limite]);
            
            respuestaJSON(true, 'Productos destacados', [
                'productos' => $productos
            ]);
            break;
        
        // =====================================================
        // OBTENER OFERTAS
        // =====================================================
        case 'ofertas':
            $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 10;
            
            $sql = "SELECT id, nombre, descripcion, precio, imagen_url, stock, categoria, descuento
                    FROM productos 
                    WHERE estado = 'disponible' AND descuento > 0
                    ORDER BY descuento DESC 
                    LIMIT ?";
            
            $productos = obtenerTodos($sql, [$limite]);
            
            respuestaJSON(true, 'Ofertas obtenidas', [
                'productos' => $productos
            ]);
            break;
        
        // =====================================================
        // BUSCAR PRODUCTOS
        // =====================================================
        case 'buscar':
            if (!isset($_GET['termino'])) {
                respuestaJSON(false, 'Término de búsqueda no proporcionado');
            }
            
            $termino = limpiarEntrada($_GET['termino']);
            $sql = "SELECT id, nombre, descripcion, precio, imagen_url, stock, categoria
                    FROM productos 
                    WHERE (nombre LIKE ? OR descripcion LIKE ?) 
                    AND estado = 'disponible' 
                    ORDER BY nombre ASC 
                    LIMIT 50";
            
            $busqueda = "%$termino%";
            $productos = obtenerTodos($sql, [$busqueda, $busqueda]);
            
            respuestaJSON(true, 'Búsqueda completada', [
                'productos' => $productos,
                'total' => count($productos),
                'termino' => $termino
            ]);
            break;
        
        // =====================================================
        // OBTENER CATEGORÍAS
        // =====================================================
        case 'categorias':
            $sql = "SELECT DISTINCT categoria, COUNT(*) as cantidad
                    FROM productos 
                    WHERE estado = 'disponible' 
                    GROUP BY categoria
                    ORDER BY categoria";
            
            $stmt = ejecutarConsulta($sql);
            $categorias = $stmt->fetchAll();
            
            respuestaJSON(true, 'Categorías obtenidas', [
                'categorias' => $categorias
            ]);
            break;
        
        // =====================================================
        // PRODUCTOS POR CATEGORÍA
        // =====================================================
        case 'por_categoria':
            if (!isset($_GET['categoria'])) {
                respuestaJSON(false, 'Categoría no proporcionada');
            }
            
            $categoria = limpiarEntrada($_GET['categoria']);
            $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 20;
            
            $sql = "SELECT id, nombre, descripcion, precio, imagen_url, stock, categoria, descuento
                    FROM productos 
                    WHERE categoria = ? AND estado = 'disponible'
                    ORDER BY nombre ASC 
                    LIMIT ?";
            
            $productos = obtenerTodos($sql, [$categoria, $limite]);
            
            respuestaJSON(true, 'Productos obtenidos', [
                'productos' => $productos,
                'categoria' => $categoria,
                'total' => count($productos)
            ]);
            break;
        
        // =====================================================
        // FILTRAR PRODUCTOS
        // =====================================================
        case 'filtrar':
            $min_precio = isset($_GET['min_precio']) ? floatval($_GET['min_precio']) : 0;
            $max_precio = isset($_GET['max_precio']) ? floatval($_GET['max_precio']) : 999999999;
            $categoria = isset($_GET['categoria']) ? limpiarEntrada($_GET['categoria']) : '';
            $orden = isset($_GET['orden']) ? limpiarEntrada($_GET['orden']) : 'nombre';
            
            $where = ["estado = 'disponible'", "precio BETWEEN ? AND ?"];
            $params = [$min_precio, $max_precio];
            
            if (!empty($categoria)) {
                $where[] = "categoria = ?";
                $params[] = $categoria;
            }
            
            $where_clause = implode(' AND ', $where);
            
            // Validar orden
            $orden_sql = match($orden) {
                'precio_asc' => 'precio ASC',
                'precio_desc' => 'precio DESC',
                'nombre_asc' => 'nombre ASC',
                'nombre_desc' => 'nombre DESC',
                'nuevo' => 'fecha_creacion DESC',
                default => 'nombre ASC'
            };
            
            $sql = "SELECT id, nombre, descripcion, precio, imagen_url, stock, categoria, descuento
                    FROM productos 
                    WHERE $where_clause
                    ORDER BY $orden_sql
                    LIMIT 50";
            
            $productos = obtenerTodos($sql, $params);
            
            respuestaJSON(true, 'Productos filtrados', [
                'productos' => $productos,
                'total' => count($productos),
                'filtros' => [
                    'min_precio' => $min_precio,
                    'max_precio' => $max_precio,
                    'categoria' => $categoria,
                    'orden' => $orden
                ]
            ]);
            break;
        
        default:
            respuestaJSON(false, 'Acción no válida');
    }
    
} catch (Exception $e) {
    registrarLog("Error en obtenerProductos: " . $e->getMessage(), 'error');
    respuestaJSON(false, 'Error al procesar la solicitud');
}
?>