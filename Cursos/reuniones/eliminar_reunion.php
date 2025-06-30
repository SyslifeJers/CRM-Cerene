<?php
session_start();
header('Content-Type: application/json');
require_once '../../DB/Conexion.php';

if (!isset($_SESSION['participante_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$id_reunion = $_POST['id_reunion'] ?? null;
$id_curso = $_POST['id_curso'] ?? null;

if (!$id_reunion || !$id_curso || !is_numeric($id_reunion) || !is_numeric($id_curso)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Verificar existencia
    $stmt = $conn->prepare("SELECT id_reunion FROM reuniones_zoom WHERE id_reunion = ?");
    $stmt->bind_param("i", $id_reunion);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'La reunión no existe']);
        exit();
    }

    // Eliminar
    $stmt = $conn->prepare("DELETE FROM reuniones_zoom WHERE id_reunion = ?");
    $stmt->bind_param("i", $id_reunion);

    if (!$stmt->execute()) {
        throw new Exception("Error al eliminar: " . $conn->error);
    }

    echo json_encode(['success' => true, 'message' => 'Reunión eliminada correctamente']);
    exit();

} catch (Exception $e) {
    error_log("Error al eliminar reunión: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    exit();
}
