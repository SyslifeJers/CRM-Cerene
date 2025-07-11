<?php
require_once '../DB/Conexion.php';

header('Content-Type: application/json');
session_start();

// Verificar si es administrador
/*if (!isset($_SESSION['es_admin']) || !$_SESSION['es_admin']) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}
*/
$database = new Database();

try {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $accion = $data['accion'] ?? '';
    $id_inscripcion = $data['id_inscripcion'] ?? 0;
    
    if ($accion === 'aprobar') {
        // Aprobar comprobante y actualizar monto
        $monto = isset($data['monto_pagado']) ? (float)$data['monto_pagado'] : null;
        $stmt = $database->getConnection()->prepare("UPDATE inscripciones
            SET estado = 'pago_validado',
                monto_pagado = ?,
                fecha_cambio_estado = CURRENT_TIMESTAMP
            WHERE id_inscripcion = ?");
        $stmt->bind_param("di", $monto, $id_inscripcion);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Comprobante aprobado correctamente'
        ]);
        
    } elseif ($accion === 'rechazar') {
        // Rechazar comprobante
        $database->getConnection()->begin_transaction();
        
        try {
            // 1. Registrar el rechazo
            $stmt = $database->getConnection()->prepare("INSERT INTO rechazos_inscripciones 
                (id_inscripcion, motivo, detalle) 
                VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $id_inscripcion, $data['motivo'], $data['detalle']);
            $stmt->execute();
            
            // 2. Cambiar estado de la inscripción
            $stmt = $database->getConnection()->prepare("UPDATE inscripciones 
                SET estado = 'registrado', 
                    fecha_cambio_estado = CURRENT_TIMESTAMP 
                WHERE id_inscripcion = ?");
            $stmt->bind_param("i", $id_inscripcion);
            $stmt->execute();
            
            $database->getConnection()->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Comprobante rechazado y participante notificado'
            ]);
            
        } catch (Exception $e) {
            $database->getConnection()->rollback();
            throw $e;
        }
    } elseif ($accion === 'cambiar_estado') {
$nuevo_estado = $data['estado'] ?? '';
if (empty($nuevo_estado)) {
    throw new Exception('El estado es requerido');
}

$conn = $database->getConnection();

// 1. Obtener estado actual
$sqlEstadoActual = "SELECT estado FROM inscripciones WHERE id_inscripcion = ?";
$stmtEstado = $conn->prepare($sqlEstadoActual);
$stmtEstado->bind_param("i", $id_inscripcion);
$stmtEstado->execute();
$resultEstado = $stmtEstado->get_result();
$estado_actual = $resultEstado->fetch_assoc()['estado'] ?? null;

// 2. Actualizar el estado si es diferente
$stmt = $conn->prepare("UPDATE inscripciones
    SET estado = ?, fecha_cambio_estado = CURRENT_TIMESTAMP
    WHERE id_inscripcion = ?");
$stmt->bind_param("si", $nuevo_estado, $id_inscripcion);
$stmt->execute();

// 3. Si cambia de 'pagos_programados' a 'pago_validado', sumar y actualizar
if ($estado_actual === 'pagos_programados' && $nuevo_estado === 'pago_validado') {
    $sqlSuma = "SELECT SUM(monto_pagado) AS total_pagado 
                FROM comprobantes_inscripcion 
                WHERE validado = 1 AND id_inscripcion = ?";
    $stmtSuma = $conn->prepare($sqlSuma);
    $stmtSuma->bind_param("i", $id_inscripcion);
    $stmtSuma->execute();
    $resultado = $stmtSuma->get_result();
    $fila = $resultado->fetch_assoc();
    $total_pagado = $fila['total_pagado'] ?? 0;

    $sqlUpdateMonto = "UPDATE inscripciones SET monto_pagado = ? WHERE id_inscripcion = ?";
    $stmtUpdate = $conn->prepare($sqlUpdateMonto);
    $stmtUpdate->bind_param("di", $total_pagado, $id_inscripcion);
    $stmtUpdate->execute();
}
        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado correctamente'
        ]);
        
    } elseif ($accion === 'asignar_opcion_pago') {
        $id_opcion = $data['id_opcion'] ?? 0;
        if (!$id_opcion) {
            throw new Exception('Opción de pago no válida');
        }
        $stmt = $database->getConnection()->prepare("UPDATE inscripciones
            SET IdOpcionPago  = ?,
                estado = 'pagos programados',
                fecha_cambio_estado = CURRENT_TIMESTAMP
            WHERE id_inscripcion = ?");
        $stmt->bind_param("ii", $id_opcion, $id_inscripcion);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Opción de pago asignada'
        ]);
    } else {
        throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>