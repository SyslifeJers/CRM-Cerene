<?php
require_once '../DB/Conexion.php';
$database = new Database();

$id_curso = isset($_POST['id_curso']) ? intval($_POST['id_curso']) : 0;
$prefijo = trim($_POST['prefijo'] ?? '');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="participantes_curso_'.$id_curso.'.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['id_participante', 'titulo', 'nombre', 'apellido', 'cedula', 'email']);

$query = $database->getConnection()->prepare(
    "SELECT p.id_participante, p.titulo, p.nombre, p.apellido, p.cedula, p.email
     FROM inscripciones i
     INNER JOIN participantes p ON i.id_participante = p.id_participante
     WHERE i.id_curso = ? AND i.estado = 'pago_validado'");
$query->bind_param('i', $id_curso);
$query->execute();
$result = $query->get_result();

while ($row = $result->fetch_assoc()) {
    $id = $row['id_participante'];
    if ($prefijo !== '') {
        if (strpos($prefijo, '@id') !== false) {
            $id = str_replace('@id', $id, $prefijo);
        } else {
            $id = $prefijo . $id;
        }
    }
    fputcsv($output, [$id, $row['titulo'], $row['nombre'], $row['apellido'], $row['cedula'], $row['email']]);
}

fclose($output);
exit;
?>
