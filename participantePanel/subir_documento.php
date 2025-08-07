<?php
session_start();
require_once '../DB/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['participante_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$id = $_SESSION['participante_id'];

try {
    if (!isset($_FILES['documento'])) {
        throw new Exception('No se envió archivo');
    }

   $target_dir = '../documentos/';

// Obtener extensión
$ext = strtolower(pathinfo($_FILES['documento']['name'], PATHINFO_EXTENSION));

// Validar formato
if (!in_array($ext, ['pdf','jpg','jpeg','png'])) {
    throw new Exception('Formato no permitido');
}
if ($_FILES['documento']['size'] > 2097152) {
    throw new Exception('El archivo supera 2MB');
}

// Nombre final del archivo
$nombre_archivo = 'certificado_' . $id . '.' . $ext;
$target_file = $target_dir . $nombre_archivo;

// Buscar documento actual
$stmt = $conn->prepare('SELECT documento FROM participantes WHERE id_participante = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($docActual);
$stmt->fetch();
$stmt->close();

// Subir archivo
if (!move_uploaded_file($_FILES['documento']['tmp_name'], $target_file)) {
    throw new Exception('Error al guardar archivo');
}

// Borrar anterior si existe y no es el mismo nombre
if ($docActual && $docActual !== $nombre_archivo) {
    @unlink($target_dir . $docActual);
}

// Actualizar base de datos
$stmt = $conn->prepare('UPDATE participantes SET documento = ? WHERE id_participante = ?');
$stmt->bind_param('si', $nombre_archivo, $id);
if (!$stmt->execute()) {
    @unlink($target_file);
    throw new Exception('Error al actualizar registro');
}

echo json_encode(['success' => true, 'message' => 'Documento cargado correctamente']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $database->closeConnection();
}
