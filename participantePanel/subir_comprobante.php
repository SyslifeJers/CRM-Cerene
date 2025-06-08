<?php
session_start();
require_once '../DB/Conexion.php';

header('Content-Type: application/json');

// Verificar autenticación y método
if (!isset($_SESSION['participante_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

try {
    // 1. Validar que la inscripción pertenece al participante
    $checkStmt = $conn->prepare("SELECT id_inscripcion FROM inscripciones WHERE id_inscripcion = ? AND id_participante = ?");
    $checkStmt->bind_param("ii", $_POST['id_inscripcion'], $_SESSION['participante_id']);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows === 0) {
        throw new Exception('La inscripción no existe o no pertenece a este usuario');
    }

    // 2. Validar archivo
    $target_dir = "../comprobantes/";
    $file_name = uniqid() . '_' . basename($_FILES["comprobante"]["name"]);
    $target_file = $target_dir . $file_name;
    
    // Validar tipo de archivo
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    if (!in_array($file_type, ['pdf', 'jpg', 'jpeg', 'png'])) {
        throw new Exception('Solo se permiten archivos PDF, JPG, JPEG o PNG');
    }

    // Validar tamaño (2MB máximo)
    if ($_FILES["comprobante"]["size"] > 2097152) {
        throw new Exception('El archivo excede el tamaño máximo de 2MB');
    }

    // 3. Mover archivo
    if (!move_uploaded_file($_FILES["comprobante"]["tmp_name"], $target_file)) {
        throw new Exception('Error al guardar el archivo en el servidor');
    }

    // 4. Actualizar la base de datos
    $updateStmt = $conn->prepare("UPDATE inscripciones SET 
        estado = 'comprobante_enviado',
        metodo_pago = ?,
        referencia_pago = ?,
        monto_pagado = ?,
        comprobante_path = ?,
        fecha_cambio_estado = CURRENT_TIMESTAMP
        WHERE id_inscripcion = ?");
    
    // Asegurar tipos correctos
    $metodo_pago = $_POST['metodo_pago'];
    $referencia = $_POST['referencia_pago'];
    $monto = (float)$_POST['monto_pagado'];
    $id_inscripcion = (int)$_POST['id_inscripcion'];
    
    $updateStmt->bind_param("ssdsi", $metodo_pago, $referencia, $monto, $file_name, $id_inscripcion);
    
    if (!$updateStmt->execute()) {
        // Borrar archivo si falla la BD
        unlink($target_file);
        throw new Exception('Error en la base de datos: ' . $updateStmt->error);
    }

    // Éxito
    echo json_encode([
        'success' => true,
        'message' => 'Comprobante subido correctamente. Estará en revisión.'
    ]);

} catch (Exception $e) {
    // Registrar error para depuración
    error_log('Error en subir_comprobante.php: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) $database->closeConnection();
}
?>