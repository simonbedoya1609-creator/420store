<?php
require_once 'conexion.php';

// Permitir CORS para desarrollo (comentar en producción)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaJSON(false, 'Método no permitido');
}

try {
    // Obtener datos del formulario
    $nombre = isset($_POST['nombre']) ? limpiarEntrada($_POST['nombre']) : '';
    $email = isset($_POST['email']) ? limpiarEntrada($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmar_password = isset($_POST['confirmar_password']) ? $_POST['confirmar_password'] : '';
    
    // =====================================================
    // VALIDACIONES
    // =====================================================
    
    // Validar que todos los campos estén llenos
    if (empty($nombre) || empty($email) || empty($password) || empty($confirmar_password)) {
        respuestaJSON(false, 'Todos los campos son obligatorios');
    }
    
    // Validar longitud del nombre
    if (strlen($nombre) < 3) {
        respuestaJSON(false, 'El nombre debe tener al menos 3 caracteres');
    }
    
    if (strlen($nombre) > 100) {
        respuestaJSON(false, 'El nombre es demasiado largo');
    }
    
    // Validar formato de email
    if (!validarEmail($email)) {
        respuestaJSON(false, 'El correo electrónico no es válido');
    }
    
    // Validar longitud de contraseña
    if (strlen($password) < 6) {
        respuestaJSON(false, 'La contraseña debe tener al menos 6 caracteres');
    }
    
    // Validar que las contraseñas coincidan
    if ($password !== $confirmar_password) {
        respuestaJSON(false, 'Las contraseñas no coinciden');
    }
    
    // =====================================================
    // VERIFICAR SI EL EMAIL YA EXISTE
    // =====================================================
    
    $sql = "SELECT id FROM usuarios WHERE email = ?";
    $usuario_existente = obtenerUno($sql, [$email]);
    
    if ($usuario_existente) {
        respuestaJSON(false, 'Este correo electrónico ya está registrado');
    }
    
    // =====================================================
    // REGISTRAR USUARIO
    // =====================================================
    
    // Hashear contraseña de forma segura
    $password_hash = hashPassword($password);
    
    // Insertar en la base de datos
    $sql = "INSERT INTO usuarios (nombre, email, password, puntos, nivel) VALUES (?, ?, ?, 0, 'Bronce')";
    
    $usuario_id = insertar($sql, [$nombre, $email, $password_hash]);
    
    if ($usuario_id) {
        // Crear notificación de bienvenida
        crearNotificacion(
            $usuario_id,
            'exito',
            '¡Bienvenido a 420 Store! 🌿',
            'Gracias por registrarte. Disfruta de nuestros productos premium y comienza a ganar puntos con cada compra.'
        );
        
        // Registrar en log
        registrarLog("Nuevo usuario registrado: $email (ID: $usuario_id)", 'info');
        
        // Iniciar sesión automáticamente
        iniciarSesion();
        $_SESSION['usuario_id'] = $usuario_id;
        $_SESSION['usuario_nombre'] = $nombre;
        $_SESSION['usuario_email'] = $email;
        $_SESSION['usuario_nivel'] = 'Bronce';
        $_SESSION['usuario_puntos'] = 0;
        
        respuestaJSON(true, 'Registro exitoso. ¡Bienvenido a 420 Store!', [
            'usuario_id' => $usuario_id,
            'nombre' => $nombre,
            'email' => $email,
            'nivel' => 'Bronce',
            'puntos' => 0,
            'redirect' => 'index.html'
        ]);
    } else {
        registrarLog("Error al registrar usuario: $email", 'error');
        respuestaJSON(false, 'Error al crear la cuenta. Intenta nuevamente.');
    }
    
} catch (Exception $e) {
    registrarLog("Excepción en registro: " . $e->getMessage(), 'error');
    respuestaJSON(false, 'Ocurrió un error inesperado. Por favor, intenta más tarde.');
}
?>