<?php
session_start();
if (!isset($_SESSION['participante_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../DB/Conexion.php';
include '../Modulos/HeadP.php';

$id_inscripcion = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_inscripcion <= 0) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Verificar que la inscripción pertenece al participante y obtener datos del curso y opción de pago
$stmt = $conn->prepare("SELECT i.id_inscripcion, i.IdOpcionPago  as id_opcion_pago, c.nombre_curso, op.numero_pagos
                       FROM inscripciones i
                       JOIN cursos c ON i.id_curso = c.id_curso
                       LEFT JOIN opciones_pago op ON i.IdOpcionPago = op.id_opcion
                       WHERE i.id_inscripcion = ? AND i.id_participante = ?");
$stmt->bind_param("ii", $id_inscripcion, $_SESSION['participante_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $database->closeConnection();
    header("Location: index.php");
    exit();
}

$inscripcion = $result->fetch_assoc();
$stmt->close();

// Si no tiene opción de pago (es null) regresar a index para usar el modal habitual
if (!$inscripcion['id_opcion_pago']) {
    $database->closeConnection();
    header("Location: index.php");
    exit();
}

$numero_pagos = (int)($inscripcion['numero_pagos'] ?? 1);

$pagos = [];
$pagosStmt = $conn->prepare("SELECT numero_pago, metodo_pago, referencia_pago, monto_pagado, comprobante_path, fecha_carga
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
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center">Sin comprobantes cargados</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

<?php if (count($pagos) < $numero_pagos): ?>
    <h5 class="mt-4">Subir comprobante (<?= count($pagos)+1 ?> de <?= $numero_pagos ?>)</h5>
    <form id="formComprobante" enctype="multipart/form-data" class="mt-3">
        <input type="hidden" name="id_inscripcion" value="<?= $id_inscripcion ?>">
        <div class="mb-3">
            <label class="form-label">Método de Pago</label>
            <select name="metodo_pago" class="form-control" required>
                <option value="">Seleccionar...</option>
                <option value="Transferencia">Transferencia Bancaria</option>
                <option value="Deposito">Depósito</option>
                <option value="Paypal">PayPal</option>
                <option value="Tarjeta">Tarjeta de Crédito/Débito</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Referencia de Pago</label>
            <input type="text" name="referencia_pago" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Monto Pagado</label>
            <input type="number" step="0.01" name="monto_pagado" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Comprobante (PDF/Imagen)</label>
            <input type="file" name="comprobante" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>
        <button type="submit" class="btn btn-primary">Enviar Comprobante</button>
    </form>
<?php else: ?>
    <div class="alert alert-info mt-4">Se enviaron todos los comprobantes requeridos.</div>
<?php endif; ?>
</div>

<script>
document.getElementById('formComprobante')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Enviando...';

    fetch('subir_comprobante.php', {
        method: 'POST',
        body: formData
    }).then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Enviar Comprobante';
        }
    }).catch(err => {
        console.error(err);
        alert('Error al enviar el formulario');
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Enviar Comprobante';
    });
});
</script>
<?php include '../Modulos/FooterP.php';
$database->closeConnection();
?>
