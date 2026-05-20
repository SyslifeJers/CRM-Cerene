<?php
require_once '../DB/Conexion.php';

header('Content-Type: application/json; charset=utf-8');

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'JSON inválido'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$required = ['id_curso', 'id_inscripcion', 'id_participante', 'email', 'nombre_participante', 'estatus'];
foreach ($required as $field) {
    if (!isset($payload[$field])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Falta el campo ' . $field
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

$estatusPermitidos = ['generado', 'enviado', 'correo_invalido', 'archivo_faltante', 'error_envio', 'informacion_incompleta'];
if (!in_array($payload['estatus'], $estatusPermitidos)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Estatus no permitido'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$id_curso = intval($payload['id_curso']);
$id_inscripcion = intval($payload['id_inscripcion']);
$id_participante = intval($payload['id_participante']);
$email = $payload['email'];
$nombre_participante = $payload['nombre_participante'];
$nombre_archivo = isset($payload['nombre_archivo']) ? $payload['nombre_archivo'] : null;
$estatus = $payload['estatus'];
$mensaje = isset($payload['mensaje']) ? $payload['mensaje'] : null;
$fecha_envio = $estatus === 'enviado' ? date('Y-m-d H:i:s') : null;

$conn->begin_transaction();

try {
    $sql = "INSERT INTO certificados_envios
            (id_curso, id_inscripcion, id_participante, email, nombre_participante, nombre_archivo, estatus, mensaje, fecha_generacion, fecha_envio)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error al preparar insert: ' . $conn->error);
    }

    $stmt->bind_param(
        'iiissssss',
        $id_curso,
        $id_inscripcion,
        $id_participante,
        $email,
        $nombre_participante,
        $nombre_archivo,
        $estatus,
        $mensaje,
        $fecha_envio
    );

    if (!$stmt->execute()) {
        throw new Exception('Error al registrar certificado: ' . $stmt->error);
    }
    $stmt->close();

    $estatusInscripcion = 'problema';
    if ($estatus === 'enviado') {
        $estatusInscripcion = 'enviado';
    } elseif ($estatus === 'generado') {
        $estatusInscripcion = 'pendiente';
    }

    $update = $conn->prepare("UPDATE inscripciones SET estatus_certificado = ?, nota = ? WHERE id_inscripcion = ?");
    if (!$update) {
        throw new Exception('Error al preparar update: ' . $conn->error);
    }

    $update->bind_param('ssi', $estatusInscripcion, $mensaje, $id_inscripcion);
    if (!$update->execute()) {
        throw new Exception('Error al actualizar inscripción: ' . $update->error);
    }
    $update->close();

    $database->generarClaveCertificadoInscripcion($id_inscripcion);
    $claveCertificado = null;
    $stmtClave = $conn->prepare("SELECT clave_certificado FROM inscripciones WHERE id_inscripcion = ?");
    $stmtClave->bind_param('i', $id_inscripcion);
    $stmtClave->execute();
    $claveCertificado = $stmtClave->get_result()->fetch_assoc()['clave_certificado'] ?? null;
    $stmtClave->close();

    $conn->commit();
    $database->closeConnection();

    echo json_encode([
        'success' => true,
        'message' => 'Registro guardado correctamente',
        'clave_certificado' => $claveCertificado
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    $conn->rollback();
    $database->closeConnection();

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
