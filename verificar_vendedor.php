<?php
require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

iniciarSesion();

try {
    // Verificar si hay sesión de vendedor
    if (!isset($_SESSION['vendedor_id']) || $_SESSION['tipo_sesion'] !== 'vendedor') {
        respuestaJSON(false, 'No hay sesión de vendedor activa', [
            'sesion_activa' => false,
            'es_vendedor' => false,
            'vendedor' => null
        ]);
    }
    
    // Obtener datos del vendedor
    $vendedor_id = $_SESSION['vendedor_id'];
    
    $sql = "SELECT id, nombre, email, estado, fecha_registro 
            FROM vendedores 
            WHERE id = ?";
    
    $vendedor = obtenerUno($sql, [$vendedor_id]);
    
    if (!$vendedor) {
        // Vendedor no encontrado, cerrar sesión
        cerrarSesion();
        respuestaJSON(false, 'Vendedor no encontrado', [
            'sesion_activa' => false,
            'es_vendedor' => false,
            'vendedor' => null
        ]);
    }
    
    // Verificar que el vendedor está activo
    if ($vendedor['estado'] !== 'activo') {
        cerrarSesion();
        respuestaJSON(false, 'Vendedor inactivo o bloqueado', [
            'sesion_activa' => false,
            'es_vendedor' => false,
            'vendedor' => null
        ]);
    }
    
    // Respuesta exitosa
    respuestaJSON(true, 'Sesión de vendedor activa', [
        'sesion_activa' => true,
        'es_vendedor' => true,
        'vendedor' => [
            'id' => $vendedor['id'],
            'nombre' => $vendedor['nombre'],
            'email' => $vendedor['email'],
            'estado' => $vendedor['estado'],
            'fecha_registro' => $vendedor['fecha_registro']
        ]
    ]);
    
} catch (Exception $e) {
    registrarLog("Error en verificar_vendedor: " . $e->getMessage(), 'error');
    respuestaJSON(false, 'Error al verificar sesión', [
        'sesion_activa' => false,
        'es_vendedor' => false,
        'vendedor' => null
    ]);
}
?>