<?php
session_start();
require_once '../DB/Conexion.php';

// Verificar autenticación y permisos
if (!isset($_SESSION['idAdmin'])) {
    header("Location: ../logAdmin.php?error=no_autorizado");
    exit();
}

// Validar datos del formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_curso'])) {
    header("Location: curso.php?error=datos_invalidos");
    exit();
}

$id_curso = $_POST['id_curso'];
$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$tipo_contenido = $_POST['tipo_contenido'] ?? '';
$orden = intval($_POST['orden'] ?? 0);
$pago_necesario = isset($_POST['pago_necesario']) ? floatval($_POST['pago_necesario']) : 0.0;
// Validaciones básicas
if (empty($titulo) || empty($tipo_contenido)) {
    header("Location:agregar.php?id_curso=$id_curso&error=campos_requeridos");
    exit();
}

// Procesar según el tipo de contenido
$enlace_url = null;
$archivo_ruta = null;

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Procesar enlace si es ese tipo
    if ($tipo_contenido === 'enlace') {
        $enlace_url = filter_var(trim($_POST['enlace_url'] ?? ''), FILTER_VALIDATE_URL);
        if (!$enlace_url) {
            throw new Exception("URL no válida");
        }
    } 
    // Procesar archivo para otros tipos
    else if (isset($_FILES['archivo'])) 
    {
        $archivo = $_FILES['archivo'];

        // Validar archivo
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error al subir el archivo: " . $archivo['error']);
        }

        // Validar tipo de archivo según el tipo de contenido
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $extensionesPermitidas = [];
        $maxSize = 10 * 1024 * 1024; // 10MB

        switch ($tipo_contenido) {
            case 'documento':
                $extensionesPermitidas = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'];
                break;
            case 'video':
                $extensionesPermitidas = ['mp4', 'mov', 'avi', 'mkv'];
                $maxSize = 100 * 1024 * 1024; // 100MB para videos
                break;
            case 'presentacion':
                $extensionesPermitidas = ['ppt', 'pptx', 'pdf'];
                break;
            case 'tarea':
                $extensionesPermitidas = ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar'];
                break;
            default:
                throw new Exception("Tipo de contenido no válido");
        }

        if (!in_array($extension, $extensionesPermitidas)) {
            throw new Exception("Tipo de archivo no permitido para este contenido. Formatos aceptados: " . implode(', ', $extensionesPermitidas));
        }

        if ($archivo['size'] > $maxSize) {
            throw new Exception("El archivo excede el tamaño máximo permitido (" . ($maxSize / (1024 * 1024)) . "MB)");
        }

        // Crear directorio si no existe
        $directorio = "../uploads/contenido/$id_curso/";
        if (!is_dir($directorio)) {
            mkdir($directorio, 0777, true);
        }

        // Generar nombre único para el archivo
        $nombreArchivo = uniqid() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $archivo['name']);
        $archivo_ruta = $directorio . $nombreArchivo;

        // Mover archivo
        if (!move_uploaded_file($archivo['tmp_name'], $archivo_ruta)) {
            throw new Exception("Error al guardar el archivo");
        }

        // Para que la ruta sea relativa en la base de datos
        $archivo_ruta = "uploads/contenido/$id_curso/" . $nombreArchivo;
    }

    // Insertar en la base de datos
    $stmt = $conn->prepare("INSERT INTO contenido_curso 
                          (id_curso, titulo, descripcion, tipo_contenido, archivo_ruta, enlace_url, orden, PagoPorce) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("issssssi", 
                     $id_curso,
                     $titulo,
                     $descripcion,
                     $tipo_contenido,
                     $archivo_ruta,
                     $enlace_url,
                     $orden,
                     $pago_necesario);

    if (!$stmt->execute()) {
        // Si falla la inserción, eliminar archivo subido si existe
        if ($archivo_ruta && file_exists("../$archivo_ruta")) {
            unlink("../$archivo_ruta");
        }
        throw new Exception("Error al guardar en la base de datos: " . $conn->error);
    }

    // Redirigir con éxito
    header("Location: curso.php?id=$id_curso&success=contenido_agregado");
    exit();

} catch (Exception $e) {
    // Manejar errores
    error_log("Error al procesar contenido: " . $e->getMessage());
    $mensajeError = urlencode($e->getMessage());
    header("Location: agregar.php?id_curso=$id_curso&error=$mensajeError");
    exit();
}