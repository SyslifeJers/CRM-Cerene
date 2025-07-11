<?php
require_once '../DB/Conexion.php';
$database = new Database();

$id_curso = isset($_POST['id_curso']) ? intval($_POST['id_curso']) : (isset($_GET['id_curso']) ? intval($_GET['id_curso']) : 0);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="reporte_curso_'.$id_curso.'.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['titulo', 'nombre_completo', 'monto_validado', 'fecha_inscripcion']);

$conn = $database->getConnection();
$query = $conn->prepare(
    "SELECT i.id_inscripcion, i.IdOpcionPago, i.monto_pagado, i.estado, i.fecha_inscripcion, p.titulo,
            CONCAT(p.nombre, ' ', p.apellido) AS nombre_completo
     FROM inscripciones i
     JOIN participantes p ON i.id_participante = p.id_participante
     WHERE i.id_curso = ? AND (i.estado = 'pago_validado' OR i.estado = 'pagos programados')"
);
$query->bind_param('i', $id_curso);
$query->execute();
$result = $query->get_result();

while ($row = $result->fetch_assoc()) {
    $monto_validado = 0;
    if ($row['IdOpcionPago']) {
        $sum = $conn->prepare("SELECT SUM(monto_pagado) AS total FROM comprobantes_inscripcion WHERE id_inscripcion = ? AND validado = 1");
        $sum->bind_param('i', $row['id_inscripcion']);
        $sum->execute();
        $res = $sum->get_result();
        $monto_validado = $res->fetch_assoc()['total'] ?? 0;
        $sum->close();
    } elseif ($row['estado'] === 'pago_validado') {
        $monto_validado = $row['monto_pagado'];
    }

    $fecha = date('d/m/Y', strtotime($row['fecha_inscripcion']));
    fputcsv($output, [$row['titulo'], $row['nombre_completo'], $monto_validado, $fecha]);
}

fclose($output);
exit;
?>
