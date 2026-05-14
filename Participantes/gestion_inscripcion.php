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
        // Aprobar comprobante y actualizar monto y fecha
        $monto = isset($data['monto_pagado']) ? (float)$data['monto_pagado'] : null;
        $fecha = !empty($data['fecha_pago']) ? $data['fecha_pago'] . ' 00:00:00' : date('Y-m-d H:i:s');
        $stmt = $database->getConnection()->prepare("UPDATE inscripciones
            SET estado = 'pago_validado',
                monto_pagado = ?,
                fecha_cambio_estado = ?
            WHERE id_inscripcion = ?");
        $stmt->bind_param("dsi", $monto, $fecha, $id_inscripcion);
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
    } elseif ($accion === 'guardar_nota') {
        $nota = isset($data['nota']) ? $data['nota'] : null;
        $stmt = $database->getConnection()->prepare("UPDATE inscripciones SET nota = ? WHERE id_inscripcion = ?");
        $stmt->bind_param("si", $nota, $id_inscripcion);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Nota actualizada'
        ]);
    } elseif ($accion === 'subir_comprobante_admin') {
        if (!isset($_SESSION['idAdmin'])) {
            throw new Exception('No autorizado');
        }

        if ($id_inscripcion <= 0) {
            throw new Exception('Inscripción inválida');
        }

        if (!isset($_FILES['comprobante']) || $_FILES['comprobante']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Debe seleccionar un comprobante válido');
        }

        $metodo_pago = trim($data['metodo_pago'] ?? '');
        $referencia = trim($data['referencia_pago'] ?? '');
        $monto = isset($data['monto_pagado']) ? (float) $data['monto_pagado'] : 0;

        if ($metodo_pago === '' || $referencia === '' || $monto <= 0) {
            throw new Exception('Complete método, referencia y monto de pago');
        }

        $conn = $database->getConnection();
        $stmt = $conn->prepare("SELECT i.id_inscripcion, i.IdOpcionPago, i.comprobante_path, p.id_participante, p.nombre, p.apellido
            FROM inscripciones i
            INNER JOIN participantes p ON p.id_participante = i.id_participante
            WHERE i.id_inscripcion = ?");
        $stmt->bind_param("i", $id_inscripcion);
        $stmt->execute();
        $inscripcion = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$inscripcion) {
            throw new Exception('Inscripción no encontrada');
        }

        if (!empty($inscripcion['IdOpcionPago'])) {
            throw new Exception('La carga admin solo aplica a pagos únicos');
        }

        $extension = strtolower(pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            throw new Exception('Solo se permiten archivos PDF, JPG, JPEG o PNG');
        }

        if ($_FILES['comprobante']['size'] > 2097152) {
            throw new Exception('El archivo excede el tamaño máximo de 2MB');
        }

        $comprobantesDir = realpath(__DIR__ . '/../comprobantes');
        if ($comprobantesDir === false) {
            throw new Exception('No se encontró la carpeta de comprobantes');
        }

        $slugNombre = preg_replace('/[^A-Za-z0-9]/', '_', $inscripcion['nombre'] . '_' . $inscripcion['apellido']);
        $slugNombre = trim(preg_replace('/_+/', '_', $slugNombre), '_');
        if ($slugNombre === '') {
            $slugNombre = 'participante_' . (int) $inscripcion['id_participante'];
        }

        $fileName = $slugNombre . '_admin_pago_' . time() . '.' . $extension;
        $targetFile = $comprobantesDir . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($_FILES['comprobante']['tmp_name'], $targetFile)) {
            throw new Exception('Error al guardar el archivo en el servidor');
        }

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("UPDATE inscripciones SET
                estado = 'comprobante_enviado',
                metodo_pago = ?,
                referencia_pago = ?,
                monto_pagado = ?,
                comprobante_path = ?,
                fecha_cambio_estado = CURRENT_TIMESTAMP
                WHERE id_inscripcion = ?");
            $stmt->bind_param("ssdsi", $metodo_pago, $referencia, $monto, $fileName, $id_inscripcion);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            if (!empty($inscripcion['comprobante_path'])) {
                $archivoAnterior = $comprobantesDir . DIRECTORY_SEPARATOR . basename($inscripcion['comprobante_path']);
                if (is_file($archivoAnterior) && $archivoAnterior !== $targetFile) {
                    @unlink($archivoAnterior);
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Comprobante subido correctamente por admin'
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            @unlink($targetFile);
            throw $e;
        }
    } elseif ($accion === 'actualizar_estatus_certificado') {
        $estatus = $data['estatus_certificado'] ?? '';
        $estatusPermitidos = ['pendiente', 'enviado', 'problema', 'reenviado'];

        if (!in_array($estatus, $estatusPermitidos, true)) {
            throw new Exception('Estatus de certificado no válido');
        }

        $columnResult = $database->getConnection()->query("SHOW COLUMNS FROM inscripciones LIKE 'estatus_certificado'");
        if (!$columnResult || $columnResult->num_rows === 0) {
            throw new Exception('Falta ejecutar la migración de estatus_certificado');
        }

        $stmt = $database->getConnection()->prepare("UPDATE inscripciones SET estatus_certificado = ? WHERE id_inscripcion = ?");
        $stmt->bind_param("si", $estatus, $id_inscripcion);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Estatus de certificado actualizado'
        ]);
    } elseif ($accion === 'intercambiar_archivos') {
        if (!isset($_SESSION['idAdmin']) || (int) ($_SESSION['rol'] ?? 0) !== 3) {
            throw new Exception('No autorizado');
        }

        $conn = $database->getConnection();
        $stmt = $conn->prepare("SELECT i.comprobante_path, i.IdOpcionPago, p.documento, p.id_participante
            FROM inscripciones i
            INNER JOIN participantes p ON p.id_participante = i.id_participante
            WHERE i.id_inscripcion = ?");
        $stmt->bind_param("i", $id_inscripcion);
        $stmt->execute();
        $registro = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$registro) {
            throw new Exception('Inscripción no encontrada');
        }

        if (!empty($registro['IdOpcionPago'])) {
            throw new Exception('Esta acción solo aplica a inscripciones con pago único');
        }

        $comprobante = $registro['comprobante_path'] ?: null;
        $documento = $registro['documento'] ?: null;

        if (!$comprobante && !$documento) {
            throw new Exception('No hay archivos para intercambiar');
        }

        $comprobantesDir = realpath(__DIR__ . '/../comprobantes');
        $documentosDir = realpath(__DIR__ . '/../documentos');

        if ($comprobantesDir === false || $documentosDir === false) {
            throw new Exception('No se encontraron las carpetas de archivos');
        }

        $rutaComprobante = $comprobante ? $comprobantesDir . DIRECTORY_SEPARATOR . $comprobante : null;
        $rutaDocumento = $documento ? $documentosDir . DIRECTORY_SEPARATOR . $documento : null;

        if ($rutaComprobante && !is_file($rutaComprobante)) {
            throw new Exception('No se encontró el archivo de comprobante');
        }

        if ($rutaDocumento && !is_file($rutaDocumento)) {
            throw new Exception('No se encontró el archivo de documento');
        }

        $estadoOriginal = [
            'comprobante_path' => $comprobante,
            'documento' => $documento,
        ];

        $restaurarArchivos = function () use ($estadoOriginal, $comprobantesDir, $documentosDir) {
            $comprobanteOriginal = $estadoOriginal['comprobante_path'];
            $documentoOriginal = $estadoOriginal['documento'];

            $rutaComprobanteOriginal = $comprobanteOriginal ? $comprobantesDir . DIRECTORY_SEPARATOR . $comprobanteOriginal : null;
            $rutaDocumentoOriginal = $documentoOriginal ? $documentosDir . DIRECTORY_SEPARATOR . $documentoOriginal : null;
            $rutaComprobanteActual = $documentoOriginal ? $comprobantesDir . DIRECTORY_SEPARATOR . $documentoOriginal : null;
            $rutaDocumentoActual = $comprobanteOriginal ? $documentosDir . DIRECTORY_SEPARATOR . $comprobanteOriginal : null;

            if ($comprobanteOriginal && $documentoOriginal) {
                if ($rutaDocumentoOriginal && is_file($rutaDocumentoOriginal) && !is_file($rutaComprobanteOriginal)) {
                    @rename($rutaDocumentoOriginal, $rutaComprobanteOriginal);
                }
                if ($rutaComprobanteActual && is_file($rutaComprobanteActual) && !is_file($rutaDocumentoOriginal)) {
                    @rename($rutaComprobanteActual, $rutaDocumentoOriginal);
                }
            } elseif ($comprobanteOriginal && !$documentoOriginal) {
                if ($rutaDocumentoActual && is_file($rutaDocumentoActual) && !is_file($rutaComprobanteOriginal)) {
                    @rename($rutaDocumentoActual, $rutaComprobanteOriginal);
                }
            } elseif (!$comprobanteOriginal && $documentoOriginal) {
                if ($rutaComprobanteActual && is_file($rutaComprobanteActual) && !is_file($rutaDocumentoOriginal)) {
                    @rename($rutaComprobanteActual, $rutaDocumentoOriginal);
                }
            }
        };

        $conn->begin_transaction();

        try {
            if ($comprobante && $documento) {
                $tmpComprobante = $comprobantesDir . DIRECTORY_SEPARATOR . '__swap_' . uniqid('comp_', true);
                $tmpDocumento = $documentosDir . DIRECTORY_SEPARATOR . '__swap_' . uniqid('doc_', true);

                if (!rename($rutaComprobante, $tmpComprobante)) {
                    throw new Exception('No se pudo mover temporalmente el comprobante');
                }

                if (!rename($rutaDocumento, $tmpDocumento)) {
                    @rename($tmpComprobante, $rutaComprobante);
                    throw new Exception('No se pudo mover temporalmente el documento');
                }

                if (!rename($tmpDocumento, $comprobantesDir . DIRECTORY_SEPARATOR . $documento)) {
                    @rename($tmpComprobante, $rutaComprobante);
                    @rename($tmpDocumento, $rutaDocumento);
                    throw new Exception('No se pudo mover el documento a comprobantes');
                }

                if (!rename($tmpComprobante, $documentosDir . DIRECTORY_SEPARATOR . $comprobante)) {
                    $restaurarArchivos();
                    throw new Exception('No se pudo mover el comprobante a documentos');
                }

                $stmtInscripcion = $conn->prepare("UPDATE inscripciones SET comprobante_path = ? WHERE id_inscripcion = ?");
                $stmtInscripcion->bind_param("si", $documento, $id_inscripcion);
                $stmtInscripcion->execute();
                $stmtInscripcion->close();

                $stmtParticipante = $conn->prepare("UPDATE participantes SET documento = ? WHERE id_participante = ?");
                $stmtParticipante->bind_param("si", $comprobante, $registro['id_participante']);
                $stmtParticipante->execute();
                $stmtParticipante->close();
            } elseif ($comprobante) {
                if (!rename($rutaComprobante, $documentosDir . DIRECTORY_SEPARATOR . $comprobante)) {
                    throw new Exception('No se pudo mover el comprobante a documentos');
                }

                $stmtInscripcion = $conn->prepare("UPDATE inscripciones SET comprobante_path = NULL WHERE id_inscripcion = ?");
                $stmtInscripcion->bind_param("i", $id_inscripcion);
                $stmtInscripcion->execute();
                $stmtInscripcion->close();

                $stmtParticipante = $conn->prepare("UPDATE participantes SET documento = ? WHERE id_participante = ?");
                $stmtParticipante->bind_param("si", $comprobante, $registro['id_participante']);
                $stmtParticipante->execute();
                $stmtParticipante->close();
            } else {
                if (!rename($rutaDocumento, $comprobantesDir . DIRECTORY_SEPARATOR . $documento)) {
                    throw new Exception('No se pudo mover el documento a comprobantes');
                }

                $stmtInscripcion = $conn->prepare("UPDATE inscripciones SET comprobante_path = ? WHERE id_inscripcion = ?");
                $stmtInscripcion->bind_param("si", $documento, $id_inscripcion);
                $stmtInscripcion->execute();
                $stmtInscripcion->close();

                $stmtParticipante = $conn->prepare("UPDATE participantes SET documento = NULL WHERE id_participante = ?");
                $stmtParticipante->bind_param("i", $registro['id_participante']);
                $stmtParticipante->execute();
                $stmtParticipante->close();
            }

            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Archivos intercambiados correctamente'
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            $restaurarArchivos();
            throw $e;
        }
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
