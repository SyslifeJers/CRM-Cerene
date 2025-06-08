<?php
session_start();
require_once '../DB/Conexion.php';

header('Content-Type: application/json');

// 1. Verificar autenticación
if (!isset($_SESSION['participante_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// 2. Obtener y validar datos JSON
$data = json_decode(file_get_contents('php://input'), true);
$clave_curso = $data['clave_curso'] ?? '';

if (empty($clave_curso)) {
    echo json_encode(['success' => false, 'message' => 'Clave de curso no proporcionada']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

try {
    // 3. Verificar que el curso existe y está activo
    $sql_curso = "SELECT id_curso, requiere_pago FROM cursos WHERE clave_curso = ? AND activo = 1";
    $stmt_curso = $conn->prepare($sql_curso);
    
    if ($stmt_curso === false) {
        throw new Exception("Error al preparar consulta: " . $conn->error);
    }
    
    if (!$stmt_curso->bind_param("s", $clave_curso)) {
        throw new Exception("Error al vincular parámetros: " . $stmt_curso->error);
    }
    
    if (!$stmt_curso->execute()) {
        throw new Exception("Error al ejecutar consulta: " . $stmt_curso->error);
    }
    
    $result_curso = $stmt_curso->get_result();
    
    if ($result_curso->num_rows === 0) {
        throw new Exception('Clave de curso no válida o curso inactivo');
    }
    
    $curso = $result_curso->fetch_assoc();
    $id_curso = $curso['id_curso'];
    $id_participante = $_SESSION['participante_id'];
    
    // 4. Verificar que el participante no está ya inscrito
    $sql_inscripcion = "SELECT id_inscripcion FROM inscripciones WHERE id_curso = ? AND id_participante = ?";
    $stmt_inscripcion = $conn->prepare($sql_inscripcion);
    
    if ($stmt_inscripcion === false) {
        throw new Exception("Error al preparar consulta de inscripción: " . $conn->error);
    }
    
    if (!$stmt_inscripcion->bind_param("ii", $id_curso, $id_participante)) {
        throw new Exception("Error al vincular parámetros de inscripción: " . $stmt_inscripcion->error);
    }
    
    if (!$stmt_inscripcion->execute()) {
        throw new Exception("Error al ejecutar consulta de inscripción: " . $stmt_inscripcion->error);
    }
    
    if ($stmt_inscripcion->get_result()->num_rows > 0) {
        throw new Exception('Ya estás inscrito en este curso');
    }
    
    // 5. Verificar cupo disponible
    $sql_cupo = "SELECT c.cupo_maximo, COUNT(i.id_inscripcion) as inscritos 
                FROM cursos c
                LEFT JOIN inscripciones i ON c.id_curso = i.id_curso AND i.estado != 'rechazado'
                WHERE c.id_curso = ? group by c.id_curso";   ;

    $stmt_cupo = $conn->prepare($sql_cupo);
    
    if ($stmt_cupo === false) {
        throw new Exception("Error al preparar consulta de cupo: " . $conn->error);
    }
    
    if (!$stmt_cupo->bind_param("i", $id_curso)) {
        throw new Exception("Error al vincular parámetros de cupo: " . $stmt_cupo->error);
    }
    
    if (!$stmt_cupo->execute()) {
        throw new Exception("Error al ejecutar consulta de cupo: " . $stmt_cupo->error);
    }
    
    $result_cupo = $stmt_cupo->get_result();
    $data_cupo = $result_cupo->fetch_assoc();
    
    if ($data_cupo['inscritos'] >= $data_cupo['cupo_maximo']) {
        throw new Exception('El curso no tiene cupos disponibles');
    }
    
    // 6. Registrar la inscripción
    $estado = $curso['requiere_pago'] ? 'registrado' : 'pago_validado';
    $sql_insert = "INSERT INTO inscripciones (id_curso, id_participante, estado, fecha_inscripcion) 
                  VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
    $stmt_insert = $conn->prepare($sql_insert);
    
    if ($stmt_insert === false) {
        throw new Exception("Error al preparar inserción: " . $conn->error);
    }
    
    if (!$stmt_insert->bind_param("iis", $id_curso, $id_participante, $estado)) {
        throw new Exception("Error al vincular parámetros de inserción: " . $stmt_insert->error);
    }
    
    if (!$stmt_insert->execute()) {
        throw new Exception('Error al registrar la inscripción: ' . $stmt_insert->error);
    }
    
    // 7. Éxito
    $mensaje = $curso['requiere_pago'] 
        ? 'Inscripción exitosa. Por favor sube tu comprobante de pago.' 
        : '¡Inscripción exitosa!';
    
    echo json_encode([
        'success' => true,
        'message' => $mensaje
    ]);
    
} catch (Exception $e) {
    error_log("Error en agregar_curso.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Cerrar todas las conexiones
    if (isset($stmt_curso)) $stmt_curso->close();
    if (isset($stmt_inscripcion)) $stmt_inscripcion->close();
    if (isset($stmt_cupo)) $stmt_cupo->close();
    if (isset($stmt_insert)) $stmt_insert->close();
    $database->closeConnection();
}
?>