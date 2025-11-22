<?php

require_once 'conexion.php';

iniciarSesion();

// Verificar si hay sesión de vendedor o admin
$es_admin = isset($_SESSION['vendedor_id']) || isset($_SESSION['admin_id']);

if (!$es_admin) {
    // Si no es admin, redirigir al login
    header('Location: ../login.html?error=acceso_denegado');
    exit;
}

// Obtener información del usuario logueado
$usuario_admin = null;

if (isset($_SESSION['vendedor_id'])) {
    $sql = "SELECT id, nombre, email FROM vendedores WHERE id = ?";
    $usuario_admin = obtenerUno($sql, [$_SESSION['vendedor_id']]);
    $usuario_admin['tipo'] = 'vendedor';
} elseif (isset($_SESSION['admin_id'])) {
    $sql = "SELECT id, nombre, email FROM administradores WHERE id = ?";
    $usuario_admin = obtenerUno($sql, [$_SESSION['admin_id']]);
    $usuario_admin['tipo'] = 'admin';
}

?>