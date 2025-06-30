<?php
session_start();
require_once '../DB/Conexion.php';

// Verificar autenticación y permisos
if (!isset($_SESSION['idAdmin'])) {
    header("Location: ../logAdmin.php?error=no_autorizado");
    exit();
}

// Validar método y datos del formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_curso'])) {
    header("Location: curso.php?error=datos_invalidos");
    exit();
}

// Recoger y sanitizar datos
$id_curso = $_POST['id_curso'];
$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$fecha_hora = $_POST['fecha_hora'] ?? '';
$duracion = intval($_POST['duracion_minutos'] ?? 60);
$url_zoom = trim($_POST['url_zoom'] ?? '');
$codigo_acceso = trim($_POST['codigo_acceso'] ?? '');

// Validaciones básicas
$errores = [];

if (empty($titulo)) {
    $errores[] = 'titulo_vacio';
}

if (empty($fecha_hora)) {
    $errores[] = 'fecha_hora_vacia';
} else {
    // Validar formato de fecha y que sea futura
    $fecha_reunion = DateTime::createFromFormat('Y-m-d\TH:i', $fecha_hora);
    $ahora = new DateTime();
    
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

// Si hay errores, redirigir con ellos
if (!empty($errores)) {
    $query_string = http_build_query(['error' => $errores]);
     header("Location: agregar.php?id_curso=$id_curso&$query_string");

    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Insertar en la base de datos
    $stmt = $conn->prepare("INSERT INTO reuniones_zoom 
                          (id_curso, titulo, descripcion, fecha_hora, duracion_minutos, url_zoom, codigo_acceso) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("isssiss", 
                     $id_curso,
                     $titulo,
                     $descripcion,
                     $fecha_hora,
                     $duracion,
                     $url_zoom,
                     $codigo_acceso);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al guardar en la base de datos: " . $conn->error);
    }

    // Redirigir con éxito
   header("Location: curso.php?id=$id_curso&success=reunion_agregada");
    exit();

} catch (Exception $e) {
    // Manejar errores
    error_log("Error al procesar reunión: " . $e->getMessage());
    $mensajeError = urlencode($e->getMessage());
   header("Location: agregar.php?id_curso=$id_curso&error=$mensajeError");
    exit();
}