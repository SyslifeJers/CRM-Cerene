<?php
session_start();
header('Content-Type: application/json');
require_once '../DB/Conexion.php';

if (!isset($_SESSION['participante_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$id_curso = $_POST['id_curso'] ?? null;
$activo = $_POST['activo'] ?? null;

if (!$id_curso || !is_numeric($id_curso) || ($activo != 0 && $activo != 1)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->prepare("UPDATE cursos SET activo = ? WHERE id_curso = ?");
    $stmt->bind_param("ii", $activo, $id_curso);

    if ($stmt->execute()) {
        $mensaje = $activo ? 'Curso activado correctamente' : 'Curso desactivado correctamente';
        echo json_encode(['success' => true, 'message' => $mensaje]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al cambiar el estado']);
    }

} catch (Exception $e) {
    error_log("Error al cambiar estado del curso: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}
