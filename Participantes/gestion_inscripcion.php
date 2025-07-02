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
        // Aprobar comprobante
        $stmt = $database->getConnection()->prepare("UPDATE inscripciones 
            SET estado = 'pago_validado', 
                fecha_cambio_estado = CURRENT_TIMESTAMP 
            WHERE id_inscripcion = ?");
        $stmt->bind_param("i", $id_inscripcion);
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
        // Cambiar el estado de la inscripción
        $nuevo_estado = $data['estado'] ?? '';
        if (empty($nuevo_estado)) {
            throw new Exception('El estado es requerido');
        }
        $stmt = $database->getConnection()->prepare("UPDATE inscripciones
            SET estado = ?,
                fecha_cambio_estado = CURRENT_TIMESTAMP
            WHERE id_inscripcion = ?");
        $stmt->bind_param("si", $nuevo_estado, $id_inscripcion);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Estado de inscripción actualizado correctamente'
        ]);
    } elseif ($accion === 'asignar_opcion_pago') {
        $id_opcion = $data['id_opcion'] ?? 0;
        if (!$id_opcion) {
            throw new Exception('Opción de pago no válida');
        }
        $stmt = $database->getConnection()->prepare("UPDATE inscripciones
            SET id_opcion_pago = ?,
                estado = 'pagos_programados',
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