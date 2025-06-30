<?php
session_start();
header('Content-Type: application/json');
require_once '../../DB/Conexion.php';

if (!isset($_SESSION['participante_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$id_contenido = $_POST['id_contenido'] ?? null;

if (!$id_contenido || !is_numeric($id_contenido)) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Verificar si existe
    $stmt = $conn->prepare("SELECT archivo_ruta FROM contenido_curso WHERE id_contenido = ?");
    $stmt->bind_param("i", $id_contenido);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Contenido no encontrado']);
        exit();
    }

    $contenido = $result->fetch_assoc();

    // Eliminar registro
    $stmt = $conn->prepare("DELETE FROM contenido_curso WHERE id_contenido = ?");
    $stmt->bind_param("i", $id_contenido);
    if (!$stmt->execute()) {
        throw new Exception("Error al eliminar: " . $conn->error);
    }

    // Borrar archivo físico si existía
    if (!empty($contenido['archivo_ruta']) && file_exists("../../" . $contenido['archivo_ruta'])) {
        unlink("../../" . $contenido['archivo_ruta']);
    }

    echo json_encode(['success' => true, 'message' => 'Contenido eliminado correctamente']);
    exit();

} catch (Exception $e) {
    error_log("Error al eliminar contenido: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    exit();
}
