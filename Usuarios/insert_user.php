<?php
session_start();
require_once '../DB/Conexion.php';

// Verificar si es administrador
if (!isset($_SESSION['idAdmin']) || $_SESSION['rol'] != 3) {
    header("Location: ../logAdmin.php?error=no_autorizado");
    exit();
}

// Validar que se recibió por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php?error=metodo_no_valido");
    exit();
}

// Obtener y limpiar los datos
$name = trim($_POST['name'] ?? '');
$user = trim($_POST['user'] ?? '');
$pass = trim($_POST['pass'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$IdRol = intval($_POST['IdRol'] ?? 0);

// Validaciones básicas
if (empty($name) || empty($user) || empty($pass) || $IdRol <= 0) {
    header("Location: index.php?error=campos_requeridos");
    exit();
}

if (strlen($pass) < 6) {
    header("Location: index.php?error=contrasena_corta");
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Preparar y ejecutar inserción
    $stmt = $conn->prepare("INSERT INTO Usuarios 
        (name, user, pass, token, activo, registro, telefono, correo, IdRol) 
        VALUES (?, ?, ?, '', 1, NOW(), ?, ?, ?)");

    $stmt->bind_param("sssssi", $name, $user, $pass, $telefono, $correo, $IdRol);

    if (!$stmt->execute()) {
        throw new Exception("Error al guardar en la base de datos: " . $stmt->error);
    }

    // Redirigir al listado con éxito
    header("Location: index.php?success=usuario_agregado");
    exit();

} catch (Exception $e) {
    error_log("Error al insertar usuario: " . $e->getMessage());
    $msg = urlencode("Ocurrió un error al guardar el usuario.");
    header("Location: index.php?error=$msg");
    exit();
}
?>
