<?php
require_once '../DB/Conexion.php';
header('Content-Type: application/json');

$data = $_POST;
$accion = $data['accion'] ?? '';
$id_comprobante = isset($data['id_comprobante']) ? intval($data['id_comprobante']) : 0;
$validado = isset($data['validado']) ? intval($data['validado']) : 0;
    $nota = trim($_POST['nota'] ?? null);

if ($accion !== 'actualizar' || !$id_comprobante || !in_array($validado, [0,1,3])) {
    echo json_encode(['success'=>false,'message'=>'Datos no válidos']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$stmt = $conn->prepare("UPDATE comprobantes_inscripcion SET validado = ?, nota = ? WHERE id_comprobante = ?");
$stmt->bind_param("ssi", $validado, $nota, $id_comprobante);
if ($stmt->execute()) {
    echo json_encode(['success'=>true,'message'=>'Estado actualizado']);
} else {
    echo json_encode(['success'=>false,'message'=>'Error al actualizar']);
}
$database->closeConnection();
?>
