<?php
include '../Modulos/head.php';

$id_curso = $_GET['id_curso'] ?? null;
if (!$id_curso) {
    header("Location: ../cursos.php?error=curso_no_seleccionado");
    exit();
}
?>

<div class="row">
  <div class="col-md-8 mx-auto">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">Agregar Nueva Reunión Zoom</h4>
        <a href="curso.php?id=<?= $id_curso ?>" class="btn btn-secondary float-right">Volver al Curso</a>
      </div>
      <div class="card-body">
        <form action="procesar_reunion.php" method="POST">
          <input type="hidden" name="id_curso" value="<?= $id_curso ?>">
          
          <div class="form-group">
            <label for="titulo">Título de la Reunión</label>
            <input type="text" class="form-control" id="titulo" name="titulo" required>
          </div>
          
          <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
          </div>
          
          <div class="form-group">
            <label for="fecha_hora">Fecha y Hora de la Reunión</label>
            <input type="datetime-local" class="form-control" id="fecha_hora" name="fecha_hora" required>
          </div>
          
          <div class="form-group">
            <label for="duracion_minutos">Duración (minutos)</label>
            <input type="number" class="form-control" id="duracion_minutos" name="duracion_minutos" min="15" value="60">
          </div>
          
          <div class="form-group">
            <label for="url_zoom">URL de la Reunión Zoom</label>
            <input type="url" class="form-control" id="url_zoom" name="url_zoom" required>
          </div>
          
          <div class="form-group">
            <label for="codigo_acceso">Código de Acceso (opcional)</label>
            <input type="text" class="form-control" id="codigo_acceso" name="codigo_acceso">
          </div>
          
          <button type="submit" class="btn btn-primary">Programar Reunión</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include '../Modulos/footer.php'; ?>