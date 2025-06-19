<?php
require_once '../../DB/Conexion.php';
$id_curso = $_GET['id_curso'] ?? null;

if (!$id_curso) {
    echo '<div class="alert alert-danger">ID de curso no especificado</div>';
    exit();
}

$database = new Database();
echo $database->getContenidoCursoTable($id_curso);
$database->closeConnection();
?>