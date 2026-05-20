<?php
require_once '../DB/Conexion.php';

header('Content-Type: application/json; charset=utf-8');

$database = new Database();
$conn = $database->getConnection();

$sql = "SELECT id_curso, nombre_curso, clave_certificado, fecha_inicio, fecha_fin, fecha_creacion, activo
        FROM cursos
        WHERE activo = 1
        ORDER BY COALESCE(fecha_creacion, fecha_inicio) DESC, id_curso DESC";

$result = $conn->query($sql);
if (!$result) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al consultar cursos: ' . $conn->error
    ], JSON_UNESCAPED_UNICODE);
    $database->closeConnection();
    exit();
}

$cursos = [];
while ($row = $result->fetch_assoc()) {
    $cursos[] = [
        'id_curso' => (int) $row['id_curso'],
        'nombre_curso' => $row['nombre_curso'],
        'clave_certificado' => $row['clave_certificado'],
        'fecha_inicio' => $row['fecha_inicio'],
        'fecha_fin' => $row['fecha_fin'],
        'fecha_creacion' => $row['fecha_creacion'],
        'activo' => (int) $row['activo']
    ];
}

$database->closeConnection();

echo json_encode([
    'success' => true,
    'cursos' => $cursos
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
