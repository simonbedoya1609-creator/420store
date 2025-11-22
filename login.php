<?php
/**
 * =====================================================
 * ARCHIVO: login.php
 * DESCRIPCIÓN: Autenticación de usuarios y vendedores
 * PROYECTO: 420 Store
 * =====================================================
 */

require_once 'conexion.php';

// Permitir CORS para desarrollo
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaJSON(false, 'Método no permitido');
}

try {
    // Obtener datos del formulario
    $email = isset($_POST['email']) ? limpiarEntrada($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $tipo_login = isset($_POST['tipo']) ? limpiarEntrada($_POST['tipo']) : 'usuario';
    
    // =====================================================
    // VALIDACIONES
    // =====================================================
    
    if (empty($email) || empty($password)) {
        respuestaJSON(false, 'Por favor, completa todos los campos');
    }
    
    if (!validarEmail($email)) {
        respuestaJSON(false, 'El correo electrónico no es válido');
    }
    
    // =====================================================
    // AUTENTICACIÓN SEGÚN TIPO
    // =====================================================
    
    if ($tipo_login === 'vendedor') {
        // Login de vendedor
        $sql = "SELECT id, nombre, email, password, estado FROM vendedores WHERE email = ?";
        $vendedor = obtenerUno($sql, [$email]);
        
        if (!$vendedor) {
            registrarLog("Intento de login fallido (vendedor no existe): $email", 'warning');
            respuestaJSON(false, 'Credenciales incorrectas');
        }
        
        // Verificar estado
        if ($vendedor['estado'] !== 'activo') {
            registrarLog("Intento de login de vendedor inactivo: $email", 'warning');
            respuestaJSON(false, 'Tu cuenta está inactiva. Contacta al administrador.');
        }
        
        // Verificar contraseña
        if (!verificarPassword($password, $vendedor['password'])) {
            registrarLog("Contraseña incorrecta para vendedor: $email", 'warning');
            respuestaJSON(false, 'Credenciales incorrectas');
        }
        
        // Iniciar sesión de vendedor
        iniciarSesion();
        $_SESSION['vendedor_id'] = $vendedor['id'];
        $_SESSION['vendedor_nombre'] = $vendedor['nombre'];
        $_SESSION['vendedor_email'] = $vendedor['email'];
        $_SESSION['tipo_sesion'] = 'vendedor';
        $_SESSION['es_vendedor'] = true; // IMPORTANTE
        
        registrarLog("Login exitoso de vendedor: $email", 'info');
        
        respuestaJSON(true, '¡Bienvenido vendedor!', [
            'tipo' => 'vendedor',
            'id' => $vendedor['id'],
            'nombre' => $vendedor['nombre'],
            'email' => $vendedor['email'],
            'es_vendedor' => true,
            'redirect' => 'vendedores.html'
        ]);
        
    } else {
        // Login de usuario/cliente
        $sql = "SELECT id, nombre, email, password, puntos, nivel, estado FROM usuarios WHERE email = ?";
        $usuario = obtenerUno($sql, [$email]);
        
        if (!$usuario) {
            registrarLog("Intento de login fallido (usuario no existe): $email", 'warning');
            respuestaJSON(false, 'Credenciales incorrectas');
        }
        
        // Verificar estado
        if ($usuario['estado'] !== 'activo') {
            registrarLog("Intento de login de usuario bloqueado: $email", 'warning');
            respuestaJSON(false, 'Tu cuenta está bloqueada. Contacta al soporte.');
        }
        
        // Verificar contraseña
        if (!verificarPassword($password, $usuario['password'])) {
            registrarLog("Contraseña incorrecta para usuario: $email", 'warning');
            respuestaJSON(false, 'Credenciales incorrectas');
        }
        
        // Actualizar última conexión
        $sql_update = "UPDATE usuarios SET ultima_conexion = NOW() WHERE id = ?";
        ejecutarConsulta($sql_update, [$usuario['id']]);
        
        // Iniciar sesión de usuario
        iniciarSesion();
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_nivel'] = $usuario['nivel'];
        $_SESSION['usuario_puntos'] = $usuario['puntos'];
        $_SESSION['tipo_sesion'] = 'usuario';
        $_SESSION['es_vendedor'] = false; // IMPORTANTE
        
        registrarLog("Login exitoso de usuario: $email", 'info');
        
        // Crear notificación de bienvenida
        crearNotificacion(
            $usuario['id'],
            'info',
            '¡Bienvenido de vuelta! 👋',
            "Hola {$usuario['nombre']}, es genial verte de nuevo. Tienes {$usuario['puntos']} puntos."
        );
        
        respuestaJSON(true, '¡Bienvenido de vuelta!', [
            'tipo' => 'usuario',
            'id' => $usuario['id'],
            'nombre' => $usuario['nombre'],
            'email' => $usuario['email'],
            'puntos' => $usuario['puntos'],
            'nivel' => $usuario['nivel'],
            'es_vendedor' => false,
            'redirect' => 'index.html'
        ]);
    }
    
} catch (Exception $e) {
    registrarLog("Excepción en login: " . $e->getMessage(), 'error');
    respuestaJSON(false, 'Ocurrió un error inesperado. Por favor, intenta más tarde.');
}
?>