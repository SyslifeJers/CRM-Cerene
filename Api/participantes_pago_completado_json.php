<?php
require_once '../DB/Conexion.php';

header('Content-Type: application/json; charset=utf-8');

$id_curso = isset($_GET['id_curso']) ? intval($_GET['id_curso']) : 0;
if ($id_curso <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Debe especificar un id_curso válido'
    ]);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$estatusCertificadoSelect = 'i.estatus_certificado';
$claveCertificadoSelect = 'i.clave_certificado AS clave_certificado';
$stmtCurso = $conn->prepare("SELECT id_curso, nombre_curso, clave_certificado FROM cursos WHERE id_curso = ?");
$stmtCurso->bind_param('i', $id_curso);
$stmtCurso->execute();
$curso = $stmtCurso->get_result()->fetch_assoc();
$stmtCurso->close();

if (!$curso) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Curso no encontrado'
    ]);
    exit();
}

$sql = "SELECT
            i.id_inscripcion,
            i.id_participante,
            i.estado,
            $estatusCertificadoSelect,
            $claveCertificadoSelect,
            i.monto_pagado,
            i.fecha_inscripcion,
            i.fecha_cambio_estado,
            p.titulo,
            p.nombre,
            p.apellido,
            p.email,
            p.telefono,
            p.cedula,
            p.documento,
            c.nombre_curso,
            c.costo,
            COALESCE(pagos.total_validado, 0) AS total_validado
        FROM inscripciones i
        INNER JOIN cursos c ON c.id_curso = i.id_curso
        INNER JOIN participantes p ON p.id_participante = i.id_participante
        LEFT JOIN (
            SELECT id_inscripcion, SUM(monto_pagado) AS total_validado
            FROM comprobantes_inscripcion
            WHERE validado = 1
            GROUP BY id_inscripcion
        ) pagos ON pagos.id_inscripcion = i.id_inscripcion
        WHERE i.id_curso = ?
          AND (
              i.estado = 'pago_validado'
              OR i.monto_pagado >= c.costo
              OR pagos.total_validado >= c.costo
          )
        ORDER BY p.apellido, p.nombre";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id_curso);
$stmt->execute();
$result = $stmt->get_result();

$participantes = [];
while ($row = $result->fetch_assoc()) {
    $participantes[] = [
        'id_inscripcion' => (int) $row['id_inscripcion'],
        'id_participante' => (int) $row['id_participante'],
        'nombre_completo' => trim(($row['titulo'] ? $row['titulo'] . ' ' : '') . $row['nombre'] . ' ' . $row['apellido']),
        'email' => $row['email'],
        'telefono' => $row['telefono'],
        'cedula' => $row['cedula'],
        'documento' => $row['documento'],
        'estado_pago' => $row['estado'],
        'estatus_certificado' => $row['estatus_certificado'],
        'clave_certificado' => $row['clave_certificado'],
        'monto_pagado' => (float) $row['monto_pagado'],
        'total_validado' => (float) $row['total_validado'],
        'costo_curso' => (float) $row['costo'],
        'fecha_inscripcion' => $row['fecha_inscripcion'],
        'fecha_pago_completado' => $row['fecha_cambio_estado']
    ];
}

$stmt->close();
$database->closeConnection();

echo json_encode([
    'success' => true,
    'curso' => [
        'id_curso' => (int) $curso['id_curso'],
        'nombre_curso' => $curso['nombre_curso'],
        'clave_certificado' => $curso['clave_certificado']
    ],
    'total' => count($participantes),
    'participantes' => $participantes
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
