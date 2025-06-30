
<?php
session_start();
require_once '../DB/Conexion.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['participante_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_POST['id'] ?? null;
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$titulo = trim($_POST['titulo'] ?? '');
$titulo_otro = trim($_POST['titulo_otro'] ?? '');

if (!$id || !$nombre || !$apellido || !$titulo) {
    header("Location: mi_perfil.php?error=campos_obligatorios");
    exit();
}

// Si seleccionó "Otro", usar el valor de título_otro
if ($titulo === 'Otro' && !empty($titulo_otro)) {
    $titulo = $titulo_otro;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->prepare("UPDATE participantes SET nombre = ?, apellido = ?, titulo = ? WHERE id_participante = ?");
    $stmt->bind_param("sssi", $nombre, $apellido, $titulo, $id);

    if ($stmt->execute()) {
        header("Location: mi_perfil.php?success=perfil_actualizado");
    } else {
        throw new Exception("Error al actualizar perfil");
    }
    $_SESSION['nombre'] = $nombre . ' ' . $apellido;
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Error en actualización de perfil: " . $e->getMessage());
    header("Location: mi_perfil.php?error=actualizacion_fallida");
    exit();
}
