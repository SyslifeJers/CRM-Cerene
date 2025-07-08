<?php
session_start();
require_once '../../DB/Conexion.php';

// Verificar autenticación
if (!isset($_SESSION['participante_id'])) {
    header("Location: ../../login.php?error=no_autorizado");
    exit();
}

// Verificar método y datos
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_reunion'], $_POST['id_curso'])) {
    header("Location: ../cursos.php?error=datos_invalidos");
    exit();
}

// Recoger datos
$id_reunion = intval($_POST['id_reunion']);
$id_curso = intval($_POST['id_curso']);
$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$fecha_hora = $_POST['fecha_hora'] ?? '';
$duracion = intval($_POST['duracion_minutos'] ?? 60);
$url_zoom = trim($_POST['url_zoom'] ?? '');
$codigo_acceso = trim($_POST['codigo_acceso'] ?? '');
$pago_necesario = isset($_POST['pago_necesario']) ? floatval($_POST['pago_necesario']) : 0.0;

// Validar datos
$errores = [];

if (empty($titulo)) {
    $errores[] = 'titulo_vacio';
}

if (empty($fecha_hora)) {
    $errores[] = 'fecha_hora_vacia';
} else {
    $fecha_reunion = DateTime::createFromFormat('Y-m-d\TH:i', $fecha_hora);
    if (!$fecha_reunion) {
        $errores[] = 'formato_fecha_invalido';
    }
}

if (empty($url_zoom)) {
    $errores[] = 'url_zoom_vacia';
} elseif (!filter_var($url_zoom, FILTER_VALIDATE_URL)) {
    $errores[] = 'url_zoom_invalida';
}

if ($duracion < 15 || $duracion > 600) {
    $errores[] = 'duracion_invalida';
}

if (!empty($errores)) {
    $query_string = http_build_query(['error' => $errores]);
    header("Location: ../editar.php?id=$id_reunion&$query_string");
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->prepare("UPDATE reuniones_zoom SET
        titulo = ?,
        descripcion = ?,
        fecha_hora = ?,
        duracion_minutos = ?,
        url_zoom = ?,
        codigo_acceso = ?,
        PagoPorce = ?
        WHERE id_reunion = ?");

    $stmt->bind_param("sssissii",
        $titulo,
        $descripcion,
        $fecha_hora,
        $duracion,
        $url_zoom,
        $codigo_acceso,
        $pago_necesario,
        $id_reunion
    );

    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar: " . $conn->error);
    }

    header("Location: ../curso.php?id=$id_curso&success=reunion_actualizada");
    exit();

} catch (Exception $e) {
    error_log("Error al actualizar reunión: " . $e->getMessage());
    $mensajeError = urlencode($e->getMessage());
    header("Location: ../editar.php?id=$id_reunion&error=$mensajeError");
    exit();
}
