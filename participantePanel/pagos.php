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

// Verificar inscripción y obtener datos
$stmt = $conn->prepare("SELECT i.id_inscripcion, i.IdOpcionPago as id_opcion_pago, c.nombre_curso, op.numero_pagos, c.costo, c.id_curso, op.costo_adicional
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

$numero_pagos = (int)($inscripcion['numero_pagos'] ?? 1);
$total_validado = 0;

$pagos = [];
$stmt = $conn->prepare("SELECT id_comprobante, numero_pago, metodo_pago, referencia_pago, monto_pagado, comprobante_path, fecha_carga, validado, nota
                        FROM comprobantes_inscripcion
                        WHERE id_inscripcion = ?
                        ORDER BY numero_pago");
$stmt->bind_param("i", $id_inscripcion);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    if ($row['validado'] == 1) $total_validado++;
    $pagos[] = $row;
}
$stmt->close();
?>
<div class="container mt-4">
    <h3>Pagos de <?= htmlspecialchars($inscripcion['nombre_curso']) ?> (Total: $<?= number_format($inscripcion['costo'] + $inscripcion['costo_adicional'], 2) ?>)</h3>

    <table class="table table-bordered mt-4">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Método</th>
                <th>Monto</th>
                <th>Comprobante</th>
                <th>Fecha</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagos as $pago): ?>
                <tr style="<?= $pago['validado'] == 3 ? 'background-color:#f8d7da' : '' ?>">
                    <td><?= $pago['numero_pago'] ?></td>
                    <td><?= htmlspecialchars($pago['metodo_pago']) ?></td>
                    <td>$<?= number_format($pago['monto_pagado'], 2) ?></td>
                    <td><a href="../comprobantes/<?= htmlspecialchars($pago['comprobante_path']) ?>" target="_blank">Ver</a></td>
                    <td><?= date('d/m/Y', strtotime($pago['fecha_carga'])) ?></td>
                    <td>
                        <?php if ($pago['validado'] == 1): ?>
                            ✅ Validado
                        <?php elseif ($pago['validado'] == 3): ?>
                            ❌ Rechazado<br><small><strong>Motivo:</strong> <?= htmlspecialchars($pago['nota']) ?></small><br>
                            <form id="reemplazo<?= $pago['id_comprobante'] ?>" enctype="multipart/form-data" class="mt-2 reemplazo-form">
                                <input type="hidden" name="id_inscripcion" value="<?= $id_inscripcion ?>">
                                <input type="file" name="comprobante" class="form-control form-control-sm mb-1" accept=".pdf,.jpg,.jpeg,.png" required>
                                <input type="hidden" name="reemplazo" value="<?= $pago['id_comprobante'] ?>">
                                <button class="btn btn-sm btn-warning">Reemplazar</button>
                            </form>
                        <?php else: ?>
                            🕒 En revisión
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total_validado < $numero_pagos): ?>
    <h5 class="mt-4">Subir nuevo comprobante (<?= count($pagos)+1 ?> de <?= $numero_pagos ?>)</h5>
    <form id="formComprobante" enctype="multipart/form-data">
        <input type="hidden" name="id_inscripcion" value="<?= $id_inscripcion ?>">
        <div class="mb-3">
            <label>Método de Pago</label>
            <select name="metodo_pago" class="form-control" required>
                <option value="">Seleccionar...</option>
                <option value="Transferencia">Transferencia</option>
                <option value="Oxxo">Oxxo</option>
                <option value="Depósito">Depósito</option>
                <option value="Paypal">PayPal</option>
                <option value="Tarjeta">Tarjeta</option>
            </select>
        </div>
        <input type="hidden" name="referencia_pago" value="">
        <div class="mb-3">
            <label>Monto Pagado</label>
            <input type="number" step="0.01" name="monto_pagado" value="<?= ($inscripcion['costo'] + $inscripcion['costo_adicional']) / $numero_pagos ?>" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Comprobante (PDF/Imagen)</label>
            <input type="file" name="comprobante" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>
        <button type="submit" class="btn btn-primary">Enviar Comprobante</button>
    </form>
    <?php else: ?>
        <div class="alert alert-info mt-4">Se enviaron todos los comprobantes requeridos.</div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.reemplazo-form').forEach(form => {
    form.addEventListener('submit', e => {
        e.preventDefault();
        const fd = new FormData(form);
        fetch('subir_comprobante.php', {
            method: 'POST',
            body: fd
        }).then(r => r.json())
          .then(res => {
              alert(res.message);
              if (res.success) location.reload();
          });
    });
});

document.getElementById('formComprobante')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const btn = this.querySelector('button');
    btn.disabled = true;
    btn.textContent = "Enviando...";

    fetch('subir_comprobante.php', {
        method: 'POST',
        body: fd
    }).then(r => r.json())
      .then(res => {
          alert(res.message);
          if (res.success) location.reload();
          else {
              btn.disabled = false;
              btn.textContent = "Enviar Comprobante";
          }
      }).catch(err => {
          console.error(err);
          alert("Error de red.");
          btn.disabled = false;
          btn.textContent = "Enviar Comprobante";
      });
});
</script>
<?php include '../Modulos/FooterP.php';
$database->closeConnection();
?>
