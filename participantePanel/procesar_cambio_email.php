<?php
session_start();
require_once '../DB/Conexion.php';

if (!isset($_SESSION['participante_id'])) {
    header("Location: login.php");
    exit();
}

$participante_id = $_SESSION['participante_id'];
$email_nuevo = trim($_POST['email_nuevo'] ?? '');
$email_confirmar = trim($_POST['email_confirmar'] ?? '');

if (empty($email_nuevo) || empty($email_confirmar)) {
    header("Location: mi_perfil.php?error=campos_obligatorios");
    exit();
}

if ($email_nuevo !== $email_confirmar) {
    header("Location: mi_perfil.php?error=email_confirmacion_incorrecta");
    exit();
}

if (!filter_var($email_nuevo, FILTER_VALIDATE_EMAIL)) {
    header("Location: mi_perfil.php?error=email_invalido");
    exit();
}

if (!preg_match('/@gmail\.com$/i', $email_nuevo)) {
    header("Location: mi_perfil.php?error=email_no_gmail");
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->prepare("SELECT id_participante FROM participantes WHERE email = ? AND id_participante <> ?");
    $stmt->bind_param("si", $email_nuevo, $participante_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        $conn->close();
        header("Location: mi_perfil.php?error=email_en_uso");
        exit();
    }
    $stmt->close();

    $update = $conn->prepare("UPDATE participantes SET email = ? WHERE id_participante = ?");
    $update->bind_param("si", $email_nuevo, $participante_id);

    if ($update->execute()) {
        $_SESSION['email'] = $email_nuevo;
        header("Location: mi_perfil.php?success=email_actualizado");
    } else {
        header("Location: mi_perfil.php?error=actualizacion_fallida");
    }

    $update->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Error en cambio de correo: " . $e->getMessage());
    header("Location: mi_perfil.php?error=error_interno");
}
