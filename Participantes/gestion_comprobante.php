<?php
require_once '../DB/Conexion.php';
header('Content-Type: application/json');

$data = $_POST;
$accion = $data['accion'] ?? '';
$id_comprobante = isset($data['id_comprobante']) ? intval($data['id_comprobante']) : 0;
$validado = isset($data['validado']) ? intval($data['validado']) : 0;
$nota = '';
if (isset($data['nota'])) {
    $nota = trim($data['nota']);
}
$monto_pagado = isset($data['monto_pagado']) ? floatval($data['monto_pagado']) : null;
$fecha_carga_input = isset($data['fecha_carga']) ? trim($data['fecha_carga']) : '';

$fecha_carga_db = null;
if ($fecha_carga_input !== '') {
    $formatosPermitidos = ['Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d'];
    foreach ($formatosPermitidos as $formato) {
        $fecha = DateTime::createFromFormat($formato, $fecha_carga_input);
        if ($fecha instanceof DateTime && $fecha->format($formato) === $fecha_carga_input) {
            if ($formato === 'Y-m-d') {
                $fecha->setTime(0, 0, 0);
            }
            $fecha_carga_db = $fecha->format('Y-m-d H:i:s');
            break;
        }
    }
}

if ($accion !== 'actualizar' || !$id_comprobante || !in_array($validado, [0,1,3]) || $monto_pagado === null) {
    echo json_encode(['success'=>false,'message'=>'Datos no válidos']);
    exit();
}

if ($fecha_carga_db === null) {
    echo json_encode(['success'=>false,'message'=>'Fecha no válida']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$stmt = $conn->prepare("UPDATE comprobantes_inscripcion SET validado = ?, nota = ?, monto_pagado = ?, fecha_carga = ? WHERE id_comprobante = ?");
$stmt->bind_param("isdsi", $validado, $nota, $monto_pagado, $fecha_carga_db, $id_comprobante);
if ($stmt->execute()) {
    echo json_encode(['success'=>true,'message'=>'Estado actualizado']);
} else {
    echo json_encode(['success'=>false,'message'=>'Error al actualizar']);
}
$database->closeConnection();
?>
