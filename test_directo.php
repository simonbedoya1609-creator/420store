<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEST DIRECTO DE REGISTRO ===<br><br>";

// Simular POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['nombre'] = 'Simon Bedoya Test';
$_POST['email'] = 'simontest@gmail.com';
$_POST['password'] = 'test123456';
$_POST['confirmar_password'] = 'test123456';

echo "Datos preparados:<br>";
echo "Nombre: " . $_POST['nombre'] . "<br>";
echo "Email: " . $_POST['email'] . "<br><br>";

echo "Intentando ejecutar registrarUsuario.php...<br><br>";

// Incluir el archivo
include 'registrarUsuario.php';
?>