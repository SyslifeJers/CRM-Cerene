<?php
session_start();
require_once '../../DB/Conexion.php';

// Verificar autenticación
if (!isset($_SESSION['participante_id'])) {
    header("Location: ../../login.php?error=no_autorizado");
    exit();
}

// Validar método y datos básicos
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_contenido'], $_POST['id_curso'])) {
    header("Location: ../../cursos.php?error=datos_invalidos");
    exit();
}

$id_contenido = intval($_POST['id_contenido']);
$id_curso = intval($_POST['id_curso']);
$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$orden = intval($_POST['orden'] ?? 0);
$enlace_url = trim($_POST['enlace_url'] ?? '');
$pago_necesario = isset($_POST['pago_necesario']) ? floatval($_POST['pago_necesario']) : 0.0;

$errores = [];

if (empty($titulo)) {
    $errores[] = 'titulo_vacio';
}

// Obtener el tipo de contenido y archivo actual
$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->prepare("SELECT tipo_contenido, archivo_ruta FROM contenido_curso WHERE id_contenido = ?");
$stmt->bind_param("i", $id_contenido);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../curso.php?id=$id_curso&error=contenido_no_encontrado");
    exit();
}

$contenido_actual = $result->fetch_assoc();
$tipo_contenido = $contenido_actual['tipo_contenido'];
$archivo_actual = $contenido_actual['archivo_ruta'];

// Validaciones específicas
if ($tipo_contenido === 'enlace') {
    if (empty($enlace_url) || !filter_var($enlace_url, FILTER_VALIDATE_URL)) {
        $errores[] = 'url_invalida';
    }
} else {
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $nombre_tmp = $_FILES['archivo']['tmp_name'];
        $nombre_archivo = basename($_FILES['archivo']['name']);
        $ruta_destino = 'uploads/contenido/' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $nombre_archivo);

        if (!move_uploaded_file($nombre_tmp, '../../' . $ruta_destino)) {
            $errores[] = 'error_subir_archivo';
        }
    } else {
        $ruta_destino = $archivo_actual; // No cambia si no suben archivo
    }
}

// Si hay errores
if (!empty($errores)) {
    $query_string = http_build_query(['error' => $errores]);
    header("Location: editar.php?id=$id_contenido&$query_string");
    exit();
}

// Actualizar en base de datos
try {
    if ($tipo_contenido === 'enlace') {
        $stmt = $conn->prepare("UPDATE contenido_curso SET titulo = ?, descripcion = ?, enlace_url = ?, orden = ?, PagoPorce = ? WHERE id_contenido = ?");
        $stmt->bind_param("sssiii", $titulo, $descripcion, $enlace_url, $orden, $pago_necesario, $id_contenido);
    } else {
        $stmt = $conn->prepare("UPDATE contenido_curso SET titulo = ?, descripcion = ?, archivo_ruta = ?, orden = ?, PagoPorce = ? WHERE id_contenido = ?");
        $stmt->bind_param("sssiii", $titulo, $descripcion, $ruta_destino, $orden, $pago_necesario, $id_contenido);
    }

    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar: " . $conn->error);
    }

    // Si se subió archivo nuevo, borra el anterior
    if ($tipo_contenido !== 'enlace' && $ruta_destino !== $archivo_actual && !empty($archivo_actual) && file_exists('../../' . $archivo_actual)) {
        unlink('../../' . $archivo_actual);
    }

    header("Location: ../curso.php?id=$id_curso&success=contenido_actualizado");
    exit();

} catch (Exception $e) {
    error_log("Error al actualizar contenido: " . $e->getMessage());
    $mensajeError = urlencode($e->getMessage());
    header("Location: editar.php?id=$id_contenido&error=$mensajeError");
    exit();
}
