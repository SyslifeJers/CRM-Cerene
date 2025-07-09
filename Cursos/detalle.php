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
        <h5 class="mt-4">Links por forma de pago</h5>
        <div class="table-responsive">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>Forma de Pago</th>
                <th>Enlace</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($opciones_pago as $op): ?>
                <?php
                  $linkPago = 'https://cursos.clinicacerene.com/Registro.php?clave=' . $curso['clave_curso'] . '-' . $op['id_opcion'];
                  $desc = $op['numero_pagos'] . ' pago' . ($op['numero_pagos'] > 1 ? 's' : '') .
                          ' de ' . $op['tipo'] . ' (Adicional: $' . number_format($op['costo_adicional'], 2) . ')';
                ?>
                <tr>
                  <td><?= $desc ?></td>
                  <td><a href="<?= $linkPago ?>" target="_blank"><?= htmlspecialchars($linkPago) ?></a></td>
                  <td><button type="button" class="btn btn-sm btn-outline-primary" onclick="copiarLink('<?= $linkPago ?>')">Copiar</button></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <a href="index.php" class="btn btn-secondary mt-3">Volver</a>
      </div>
    </div>
  </div>
</div>
<?php include '../Modulos/Footer.php'; ?>
<script>
  function copiarLink(link) {
    navigator.clipboard.writeText(link).then(() => alert('Enlace copiado: ' + link));
  }
</script>
