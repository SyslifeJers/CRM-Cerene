<?php
require_once '../DB/Conexion.php';
include '../Modulos/Head.php';

$id_inscripcion = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_inscripcion <= 0) {
    echo '<div class="alert alert-danger">ID de inscripción inválido</div>';
    include '../Modulos/Footer.php';
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->prepare("SELECT i.id_inscripcion, i.IdOpcionPago AS id_opcion_pago,
                       i.metodo_pago, i.referencia_pago, i.monto_pagado,
                       i.comprobante_path, i.estado, i.fecha_cambio_estado,
                       c.nombre_curso, op.numero_pagos
                       FROM inscripciones i
                       JOIN cursos c ON i.id_curso = c.id_curso
                       LEFT JOIN opciones_pago op ON i.IdOpcionPago = op.id_opcion
                       WHERE i.id_inscripcion = ?");
$stmt->bind_param("i", $id_inscripcion);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo '<div class="alert alert-warning">Inscripción no encontrada</div>';
    include '../Modulos/Footer.php';
    exit();
}
$inscripcion = $result->fetch_assoc();
$stmt->close();
$numero_pagos = (int)($inscripcion['numero_pagos'] ?? 1);
$pagos = [];
$isMultiple = !empty($inscripcion['id_opcion_pago']);

if ($isMultiple) {
    $pagosStmt = $conn->prepare("SELECT id_comprobante, numero_pago, metodo_pago, referencia_pago, monto_pagado, comprobante_path, fecha_carga, validado
                                  FROM comprobantes_inscripcion
                                  WHERE id_inscripcion = ?
                                  ORDER BY numero_pago");
    $pagosStmt->bind_param("i", $id_inscripcion);
    $pagosStmt->execute();
    $resPagos = $pagosStmt->get_result();
    while ($row = $resPagos->fetch_assoc()) {
        $pagos[] = $row;
    }
    $pagosStmt->close();
} else {
    if ($inscripcion['comprobante_path']) {
        $pagos[] = [
            'numero_pago'     => 1,
            'metodo_pago'     => $inscripcion['metodo_pago'],
            'referencia_pago' => $inscripcion['referencia_pago'],
            'monto_pagado'    => $inscripcion['monto_pagado'],
            'comprobante_path'=> $inscripcion['comprobante_path'],
            'fecha_carga'     => $inscripcion['fecha_cambio_estado'],
            'validado'        => $inscripcion['estado'] === 'pago_validado' ? 1 : 0
        ];
    }
}
?>
<div class="container mt-4">
    <h3 class="mb-4">Pagos de <?= htmlspecialchars($inscripcion['nombre_curso']) ?></h3>
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th># Pago</th>
                <th>Método</th>
                <th>Referencia</th>
                <th>Monto</th>
                <th>Comprobante</th>
                <th>Fecha</th>
                <th><?= $isMultiple ? 'Validado' : 'Estado' ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($pagos) > 0): ?>
                <?php foreach ($pagos as $pago): ?>
                <tr>
                    <td><?= $pago['numero_pago'] ?></td>
                    <td><?= htmlspecialchars($pago['metodo_pago']) ?></td>
                    <td><?= htmlspecialchars($pago['referencia_pago']) ?></td>
                    <td>$<?= number_format($pago['monto_pagado'], 2) ?></td>
                    <td><a href="../comprobantes/<?= htmlspecialchars($pago['comprobante_path']) ?>" target="_blank">Ver</a></td>
                    <td><?= date('d/m/Y', strtotime($pago['fecha_carga'])) ?></td>
                    <td>
                        <?php if ($isMultiple): ?>
                            <select class="form-select form-select-sm validar-select" data-id="<?= $pago['id_comprobante'] ?>">
                                <option value="0" <?= $pago['validado']==0?'selected':'' ?>>Pendiente</option>
                                <option value="1" <?= $pago['validado']==1?'selected':'' ?>>Correcto</option>
                                <option value="3" <?= $pago['validado']==3?'selected':'' ?>>Rechazado</option>
                            </select>
                        <?php else: ?>
                            <?= htmlspecialchars($inscripcion['estado']) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center">Sin comprobantes cargados</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<script>
$(function(){
    $('.validar-select').change(function(){
        const id = $(this).data('id');
        const val = $(this).val();
        $.post('gestion_comprobante.php',{accion:'actualizar',id_comprobante:id,validado:val},function(res){
            if(res.success){
                Swal.fire('Éxito', res.message, 'success');
            }else{
                Swal.fire('Error', res.message, 'error');
            }
        },'json');
    });
});
</script>
<?php include '../Modulos/Footer.php';
$database->closeConnection();
?>
