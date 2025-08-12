<?php
session_start();
require_once '../DB/Conexion.php';

header('Content-Type: application/json');

// Verificar autenticación y método
if (!isset($_SESSION['participante_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado, vuelve a iniciar sesión']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

try {
    $id_inscripcion = (int)$_POST['id_inscripcion'];

    // 1. Validar inscripción
    $checkStmt = $conn->prepare("SELECT id_inscripcion, IdOpcionPago FROM inscripciones WHERE id_inscripcion = ? AND id_participante = ?");
    $checkStmt->bind_param("ii", $id_inscripcion, $_SESSION['participante_id']);
    $checkStmt->execute();
    $resultCheck = $checkStmt->get_result();

    if ($resultCheck->num_rows === 0) {
        throw new Exception('La inscripción no existe o no pertenece a este usuario');
    }

    $inscripcionData = $resultCheck->fetch_assoc();
    $id_opcion_pago = $inscripcionData['IdOpcionPago'];

    // 2. Validar archivo
    $target_dir = "../comprobantes/";
    $file_name = uniqid() . '_' . basename($_FILES["comprobante"]["name"]);
    $target_file = $target_dir . $file_name;

    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    if (!in_array($file_type, ['pdf', 'jpg', 'jpeg', 'png'])) {
        throw new Exception('Solo se permiten archivos PDF, JPG, JPEG o PNG');
    }

    if ($_FILES["comprobante"]["size"] > 2097152) {
        throw new Exception('El archivo excede el tamaño máximo de 2MB');
    }

    if (!move_uploaded_file($_FILES["comprobante"]["tmp_name"], $target_file)) {
        throw new Exception('Error al guardar el archivo en el servidor');
    }

    // 3. Datos comunes
    $metodo_pago = $_POST['metodo_pago'];
    $referencia  = $_POST['referencia_pago'];
    $monto       = (float)$_POST['monto_pagado'];

    if ($id_opcion_pago) {
        // Múltiples pagos - revisar si existe uno rechazado
        $rechazadoStmt = $conn->prepare("SELECT id_comprobante, numero_pago FROM comprobantes_inscripcion WHERE id_inscripcion = ? AND validado = 3 ORDER BY numero_pago LIMIT 1");
        $rechazadoStmt->bind_param("i", $id_inscripcion);
        $rechazadoStmt->execute();
        $rechazo = $rechazadoStmt->get_result()->fetch_assoc();
        $rechazadoStmt->close();

        if ($rechazo) {
            // Reemplazar comprobante rechazado
            $updateComprobante = $conn->prepare("UPDATE comprobantes_inscripcion SET  comprobante_path = ?, fecha_carga = NOW(), validado = 0, nota = NULL WHERE id_comprobante = ?");
            $updateComprobante->bind_param("si", $file_name, $rechazo['id_comprobante']);
            $updateComprobante->execute();
        } else {
            // Nuevo comprobante (no hay rechazados)
            $countStmt = $conn->prepare("SELECT COUNT(*) AS pagos FROM comprobantes_inscripcion WHERE id_inscripcion = ?");
            $countStmt->bind_param("i", $id_inscripcion);
            $countStmt->execute();
            $numero_pago = $countStmt->get_result()->fetch_assoc()['pagos'] + 1;
            $countStmt->close();

            $insertStmt = $conn->prepare("INSERT INTO comprobantes_inscripcion (id_inscripcion, numero_pago, metodo_pago, referencia_pago, monto_pagado, comprobante_path) VALUES (?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param("iissds", $id_inscripcion, $numero_pago, $metodo_pago, $referencia, $monto, $file_name);
            $insertStmt->execute();
        }

        // Estado general
        $updateEstado = $conn->prepare("UPDATE inscripciones SET estado = 'Revision de pago', fecha_cambio_estado = CURRENT_TIMESTAMP WHERE id_inscripcion = ?");
        $updateEstado->bind_param("i", $id_inscripcion);
        $updateEstado->execute();

    } else {
        // Un solo comprobante (sin opción múltiple)
        $updateStmt = $conn->prepare("UPDATE inscripciones SET
            estado = 'comprobante_enviado',
            metodo_pago = ?,
            referencia_pago = ?,
            monto_pagado = ?,
            comprobante_path = ?,
            fecha_cambio_estado = CURRENT_TIMESTAMP
            WHERE id_inscripcion = ?");
        $updateStmt->bind_param("ssdsi", $metodo_pago, $referencia, $monto, $file_name, $id_inscripcion);
        $updateStmt->execute();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Comprobante subido correctamente. Estará en revisión.'
    ]);

} catch (Exception $e) {
    error_log('Error en subir_comprobante.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $database->closeConnection();
}
?>
