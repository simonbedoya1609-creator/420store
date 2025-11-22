<?php
header('Content-Type: application/json');
include("conexion.php");

$sql = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.categoria, p.imagen, p.stock, v.nombre AS vendedor
        FROM productos p
        LEFT JOIN vendedores v ON p.id_vendedor = v.id";
$result = $conn->query($sql);

$productos = [];
if ($result) {
    while($row = $result->fetch_assoc()){
        $productos[] = $row;
    }
}

echo json_encode($productos);
$conn->close();
?>
