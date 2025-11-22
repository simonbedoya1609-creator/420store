<?php
require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

iniciarSesion();

try {
    // Verificar si hay sesión activa
    if (!sesionActiva()) {
        respuestaJSON(true, 'No hay sesión activa', [
            'sesion_activa' => false,
            'usuario' => null
        ]);
    }
    
    // Obtener datos del usuario
    $usuario_id = $_SESSION['usuario_id'];
    
    $sql = "SELECT id, nombre, email, puntos, nivel, estado, ultima_conexion 
            FROM usuarios 
            WHERE id = ?";
    
    $usuario = obtenerUno($sql, [$usuario_id]);
    
    if (!$usuario) {
        // Usuario no encontrado, cerrar sesión
        cerrarSesion();
        respuestaJSON(false, 'Usuario no encontrado', [
            'sesion_activa' => false,
            'usuario' => null
        ]);
    }
    
    // Verificar que el usuario está activo
    if ($usuario['estado'] !== 'activo') {
        cerrarSesion();
        respuestaJSON(false, 'Usuario inactivo o bloqueado', [
            'sesion_activa' => false,
            'usuario' => null
        ]);
    }
    
    // Obtener notificaciones no leídas
    $sql_notif = "SELECT COUNT(*) as total 
                  FROM notificaciones 
                  WHERE usuario_id = ? AND leida = 0";
    $notif_result = obtenerUno($sql_notif, [$usuario_id]);
    $total_notificaciones = $notif_result['total'];
    
    // Calcular progreso de nivel
    $puntos_actuales = $usuario['puntos'];
    $nivel_actual = $usuario['nivel'];
    
    $niveles = [
        'Bronce' => ['min' => 0, 'max' => 99],
        'Plata' => ['min' => 100, 'max' => 499],
        'Oro' => ['min' => 500, 'max' => 999],
        'Diamante' => ['min' => 1000, 'max' => 9999999]
    ];
    
    $progreso = 0;
    if (isset($niveles[$nivel_actual])) {
        $min = $niveles[$nivel_actual]['min'];
        $max = $niveles[$nivel_actual]['max'];
        $progreso = (($puntos_actuales - $min) / ($max - $min)) * 100;
        $progreso = min(100, max(0, $progreso));
    }
    
    // Respuesta exitosa
    respuestaJSON(true, 'Sesión activa', [
        'sesion_activa' => true,
        'usuario' => [
            'id' => $usuario['id'],
            'nombre' => $usuario['nombre'],
            'email' => $usuario['email'],
            'puntos' => $usuario['puntos'],
            'nivel' => $usuario['nivel'],
            'estado' => $usuario['estado'],
            'ultima_conexion' => $usuario['ultima_conexion'],
            'progreso_nivel' => round($progreso, 1),
            'notificaciones_pendientes' => $total_notificaciones
        ]
    ]);
    
} catch (Exception $e) {
    registrarLog("Error en verificar_sesion: " . $e->getMessage(), 'error');
    respuestaJSON(false, 'Error al verificar sesión', [
        'sesion_activa' => false,
        'usuario' => null
    ]);
}
?>