<?php
session_start();
require_once '../DB/Conexion.php';

// Verificar autenticación
if (!isset($_SESSION['participante_id'])) {
    header("Location: ../login.php");
    exit();
}

$participante_id = $_SESSION['participante_id'];
$pass_actual = $_POST['pass_actual'] ?? '';
$nueva_pass = $_POST['pass_nueva'] ?? '';
$confirmar_pass = $_POST['confirmar_pass'] ?? '';

// Validaciones básicas
if (empty($pass_actual) || empty($nueva_pass) || empty($confirmar_pass)) {
    header("Location: mi_perfil.php?error=campos_obligatorios");
    exit();
}

if (strlen($nueva_pass) < 6) {
    header("Location: mi_perfil.php?error=pass_corta");
    exit();
}

if ($nueva_pass !== $confirmar_pass) {
    header("Location: mi_perfil.php?error=confirmacion_incorrecta");
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Obtener contraseña actual en base de datos
    $stmt = $conn->prepare("SELECT pass FROM participantes WHERE id_participante = ?");
    $stmt->bind_param("i", $participante_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($pass_actual, $user['pass'])) {
        header("Location: mi_perfil.php?error=pass_actual_incorrecta");
        exit();
    }

    // Guardar nueva contraseña hasheada
    $nueva_pass_hash = password_hash($nueva_pass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE participantes SET pass = ? WHERE id_participante = ?");
    $stmt->bind_param("si", $nueva_pass_hash, $participante_id);

    if ($stmt->execute()) {
        header("Location: mi_perfil.php?success=pass_actualizada");
    } else {
        throw new Exception("Error al actualizar contraseña.");
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Error en cambio de contraseña: " . $e->getMessage());
    header("Location: mi_perfil.php?error=error_interno");
    exit();
}
