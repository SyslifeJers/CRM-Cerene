<?php
include '../Modulos/Head.php';
require_once '../DB/Conexion.php';

$id_curso = $_GET['id'] ?? null;
$database = new Database();
$curso = null;
if ($id_curso) {
    $curso = $database->getCursoById($id_curso);
}
if (!$curso) {
    echo "<div class='alert alert-danger'>Curso no encontrado</div>";
    include '../Modulos/Footer.php';
    exit();
}

$opciones_pago = $database->getOpcionesPagoCurso();
?>
<div class="row">
  <div class="col-md-8 mx-auto">
    <div class="card mt-4">
      <div class="card-header">
        <h4 class="card-title"><?= htmlspecialchars($curso['nombre_curso']) ?></h4>
      </div>
      <div class="card-body">
        <p><?= nl2br(htmlspecialchars($curso['descripcion'])) ?></p>
        <ul class="list-group mb-3">
          <li class="list-group-item"><strong>Clave:</strong> <?= htmlspecialchars($curso['clave_curso']) ?></li>
          <li class="list-group-item"><strong>Fecha Inicio:</strong> <?= date('d/m/Y', strtotime($curso['fecha_inicio'])) ?></li>
          <li class="list-group-item"><strong>Fecha Fin:</strong> <?= date('d/m/Y', strtotime($curso['fecha_fin'])) ?></li>
          <li class="list-group-item"><strong>Costo:</strong> $<?= number_format($curso['costo'], 2) ?></li>
          <?php if(isset($curso['cupo_maximo'])): ?>
          <li class="list-group-item"><strong>Cupo Máximo:</strong> <?= $curso['cupo_maximo'] ?></li>
          <?php endif; ?>
          <li class="list-group-item"><strong>Enlace de Inscripción:</strong> <a href="<?= htmlspecialchars($curso['link_inscripcion']) ?>" target="_blank"><?= htmlspecialchars($curso['link_inscripcion']) ?></a></li>
          <li class="list-group-item"><strong>Requiere Pago:</strong> <?= $curso['requiere_pago'] ? 'Sí' : 'No' ?></li>
          <li class="list-group-item"><strong>Estado:</strong> <?= $curso['activo'] ? 'Activo' : 'Inactivo' ?></li>
        </ul>
        <?php if (!empty($opciones_pago)): ?>
        <div class="mb-3">
          <label class="form-label">Opción de Pago</label>
          <select id="opcionPago" class="form-select" onchange="manejarCambio()">
            <?php foreach ($opciones_pago as $op): ?>
              <option 
                value="<?= $op['id_opcion'] ?>"
                data-numero="<?= $op['numero_pagos'] ?>" 
                data-adicional="<?= $op['costo_adicional'] ?>">
                <?= $op['numero_pagos'] ?> pago<?= $op['numero_pagos'] > 1 ? 's' : '' ?> de <?= $op['tipo'] ?> 
                (Adicional: $<?= number_format($op['costo_adicional'], 2) ?>) / Nota: <?= htmlspecialchars($op['nota']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>


        <div id="infoPago" class="alert alert-info d-none"></div>

        <button id="btnCompartir" class="btn btn-primary" disabled>Compartir link</button>
        <a href="index.php" class="btn btn-secondary ms-2">Volver</a>
      </div>
    </div>
  </div>
</div>
<?php include '../Modulos/Footer.php'; ?>
<script>
  const btnCompartir = document.getElementById('btnCompartir');
const formaPago = document.getElementById('opcionPago');

function manejarCambio() {
  const select = document.getElementById('opcionPago');
  const opcionSeleccionada = select.options[select.selectedIndex];
  
  const id = select.value;
  const numeroPagos = opcionSeleccionada.getAttribute('data-numero');
  const adicional = parseFloat(opcionSeleccionada.getAttribute('data-adicional'));

  actualizarInfo(id, numeroPagos, adicional);
}

function actualizarInfo(id, numeroPagos, adicional = 0) {
  const total = <?= (float)$curso['costo'] ?> + adicional;
  const porPago = total / numeroPagos;
  infoPago.textContent = `${numeroPagos} pagos de $${porPago.toFixed(2)} (total $${total.toFixed(2)})`;
  infoPago.classList.remove('d-none');
  btnCompartir.disabled = false;
}
  btnCompartir?.addEventListener('click', function() {
  const idFp = formaPago.value;
  if (!idFp) return;
  const link = 'https://cursos.clinicacerene.com/Registro.php?clave=<?= $curso['clave_curso'] ?>-' + idFp;
  navigator.clipboard.writeText(link).then(() => alert('Enlace copiado: ' + link));
});
</script>
