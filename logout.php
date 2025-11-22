<?php
/**
 * =====================================================
 * ARCHIVO: logout.php
 * DESCRIPCIÓN: Cerrar sesión de usuarios/vendedores
 * PROYECTO: 420 Store
 * =====================================================
 */

require_once 'conexion.php';

iniciarSesion();

// Guardar email antes de destruir sesión (para log)
$email = isset($_SESSION['usuario_email']) ? $_SESSION['usuario_email'] : 
         (isset($_SESSION['vendedor_email']) ? $_SESSION['vendedor_email'] : 'Desconocido');

$tipo = isset($_SESSION['tipo_sesion']) ? $_SESSION['tipo_sesion'] : 'usuario';

// Cerrar sesión
cerrarSesion();

// Registrar en log
registrarLog("Sesión cerrada: $email (tipo: $tipo)", 'info');

// Redirigir a la página de login
header('Location: ../login.html?logout=1');
exit;
?>