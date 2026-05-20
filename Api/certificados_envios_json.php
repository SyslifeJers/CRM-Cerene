<?php
require_once '../DB/Conexion.php';

header('Content-Type: application/json; charset=utf-8');

$id_curso = isset($_GET['id_curso']) ? intval($_GET['id_curso']) : 0;
if ($id_curso <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Debe especificar un id_curso válido'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$claveCertificadoSelect = 'i.clave_certificado';

$sql = "SELECT ce.id_envio, ce.id_curso, ce.id_inscripcion, ce.id_participante, ce.email, ce.nombre_participante,
               ce.nombre_archivo, ce.estatus, ce.mensaje, ce.fecha_generacion, ce.fecha_envio, ce.fecha_registro,
               $claveCertificadoSelect
        FROM certificados_envios ce
        LEFT JOIN inscripciones i ON i.id_inscripcion = ce.id_inscripcion
        WHERE ce.id_curso = ?
        ORDER BY ce.fecha_registro DESC, ce.id_envio DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al preparar consulta: ' . $conn->error
    ], JSON_UNESCAPED_UNICODE);
    $database->closeConnection();
    exit();
}

$stmt->bind_param('i', $id_curso);
$stmt->execute();
$result = $stmt->get_result();

$datos = [];
while ($row = $result->fetch_assoc()) {
    $datos[] = [
        'id_envio' => (int) $row['id_envio'],
        'id_curso' => (int) $row['id_curso'],
        'id_inscripcion' => (int) $row['id_inscripcion'],
        'id_participante' => (int) $row['id_participante'],
        'email' => $row['email'],
        'nombre_participante' => $row['nombre_participante'],
        'nombre_archivo' => $row['nombre_archivo'],
        'clave_certificado' => $row['clave_certificado'],
        'estatus' => $row['estatus'],
        'mensaje' => $row['mensaje'],
        'fecha_generacion' => $row['fecha_generacion'],
        'fecha_envio' => $row['fecha_envio'],
        'fecha_registro' => $row['fecha_registro']
    ];
}

$stmt->close();
$database->closeConnection();

echo json_encode([
    'success' => true,
    'datos' => $datos
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
