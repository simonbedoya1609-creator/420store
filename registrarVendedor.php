<?php
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
    $nombre = isset($_POST['nombre']) ? limpiarEntrada($_POST['nombre']) : '';
    $email = isset($_POST['email']) ? limpiarEntrada($_POST['email']) : '';
    $telefono = isset($_POST['telefono']) ? limpiarEntrada($_POST['telefono']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmar_password = isset($_POST['confirmar_password']) ? $_POST['confirmar_password'] : '';
    
    // =====================================================
    // VALIDACIONES
    // =====================================================
    
    // Validar campos obligatorios
    if (empty($nombre) || empty($email) || empty($password)) {
        respuestaJSON(false, 'Los campos nombre, email y contraseña son obligatorios');
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
    
    // Validar teléfono (opcional pero si se proporciona debe ser válido)
    if (!empty($telefono) && !preg_match('/^[0-9\s\-\+\(\)]{7,20}$/', $telefono)) {
        respuestaJSON(false, 'El formato del teléfono no es válido');
    }
    
    // Validar longitud de contraseña
    if (strlen($password) < 6) {
        respuestaJSON(false, 'La contraseña debe tener al menos 6 caracteres');
    }
    
    // Validar que las contraseñas coincidan (si se proporciona confirmación)
    if (!empty($confirmar_password) && $password !== $confirmar_password) {
        respuestaJSON(false, 'Las contraseñas no coinciden');
    }
    
    // =====================================================
    // VERIFICAR SI EL EMAIL YA EXISTE
    // =====================================================
    
    // Verificar en tabla de vendedores
    $sql = "SELECT id FROM vendedores WHERE email = ?";
    $vendedor_existente = obtenerUno($sql, [$email]);
    
    if ($vendedor_existente) {
        respuestaJSON(false, 'Este correo electrónico ya está registrado como vendedor');
    }
    
    // También verificar que no esté en usuarios (evitar duplicados)
    $sql = "SELECT id FROM usuarios WHERE email = ?";
    $usuario_existente = obtenerUno($sql, [$email]);
    
    if ($usuario_existente) {
        respuestaJSON(false, 'Este correo electrónico ya está registrado');
    }
    
    // =====================================================
    // REGISTRAR VENDEDOR
    // =====================================================
    
    // Hashear contraseña de forma segura
    $password_hash = hashPassword($password);
    
    // Insertar en la base de datos
    $sql = "INSERT INTO vendedores (nombre, email, password, telefono, estado) VALUES (?, ?, ?, ?, 'activo')";
    
    $vendedor_id = insertar($sql, [$nombre, $email, $password_hash, $telefono]);
    
    if ($vendedor_id) {
        // Registrar en log
        registrarLog("Nuevo vendedor registrado: $email (ID: $vendedor_id)", 'info');
        
        // Iniciar sesión automáticamente como vendedor
        iniciarSesion();
        $_SESSION['vendedor_id'] = $vendedor_id;
        $_SESSION['vendedor_nombre'] = $nombre;
        $_SESSION['vendedor_email'] = $email;
        $_SESSION['tipo_sesion'] = 'vendedor';
        
        respuestaJSON(true, '¡Registro exitoso! Bienvenido al equipo de 420 Store', [
            'vendedor_id' => $vendedor_id,
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono,
            'redirect' => 'vendedores.html'
        ]);
    } else {
        registrarLog("Error al registrar vendedor: $email", 'error');
        respuestaJSON(false, 'Error al crear la cuenta de vendedor. Intenta nuevamente.');
    }
    
} catch (Exception $e) {
    registrarLog("Excepción en registro de vendedor: " . $e->getMessage(), 'error');
    respuestaJSON(false, 'Ocurrió un error inesperado. Por favor, intenta más tarde.');
}
?>