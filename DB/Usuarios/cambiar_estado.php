<?php
require_once '/DB/Conexion.php';

$db = new Database();
$id = $_GET['id'];
$nuevoEstado = $_GET['estado'];

$stmt = $db->getConnection()->prepare("UPDATE usuarios SET activo = ? WHERE id = ?");
$stmt->bind_param("ii", $nuevoEstado, $id);

header('Content-Type: application/json');
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$db->closeConnection();
?>