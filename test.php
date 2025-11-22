<<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "✅ PHP funciona correctamente!<br>";
echo "Fecha: " . date('Y-m-d H:i:s') . "<br>";
echo "Versión PHP: " . phpversion() . "<br><br>";

echo "=== PRUEBA DE CONEXIÓN A BD ===<br>";

$host = 'localhost';
$dbname = 'tienda_deportiva_420';
$user = 'root';
$pass = '';

echo "Host: $host<br>";
echo "Database: $dbname<br>";
echo "User: $user<br><br>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    echo "✅ CONEXIÓN A BASE DE DATOS EXITOSA!<br><br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total usuarios en BD: " . $result['total'];
    
} catch(PDOException $e) {
    echo "❌ ERROR DE CONEXIÓN:<br>";
    echo $e->getMessage();
}
?>