<?php
include '../Modulos/head.php';

$id_curso = $_GET['id_curso'] ?? null;
if (!$id_curso) {
    header("Location: ../../cursos.php?error=curso_no_seleccionado");
    exit();
}
?>

<div class="row">
  <div class="col-md-8 mx-auto">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">Agregar Nuevo Contenido</h4>
        <a href="../curso.php?id=<?= $id_curso ?>" class="btn btn-secondary float-right">Volver al Curso</a>
      </div>
      <div class="card-body">
        <form action="procesar_contenido.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="id_curso" value="<?= $id_curso ?>">
          
          <div class="form-group">
            <label for="titulo">Título del Contenido</label>
            <input type="text" class="form-control" id="titulo" name="titulo" required>
          </div>
          
          <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
          </div>
          
          <div class="form-group">
            <label for="tipo_contenido">Tipo de Contenido</label>
            <select class="form-control" id="tipo_contenido" name="tipo_contenido" required>
              <option value="">Seleccione un tipo</option>
              <option value="documento">Documento (PDF, Word, etc.)</option>
              <option value="video">Video</option>
              <option value="enlace">Enlace externo</option>
              <option value="presentacion">Presentación</option>
              <option value="tarea">Tarea/Actividad</option>
            </select>
          </div>
          
          <!-- Campos dinámicos según el tipo de contenido -->
          <div id="camposDinamicos">
            <!-- Se llenará con JavaScript según el tipo seleccionado -->
          </div>
          
          <div class="form-group">
            <label for="orden">Orden de visualización</label>
            <input type="number" class="form-control" id="orden" name="orden" min="0" value="0">
          </div>
          
          <button type="submit" class="btn btn-primary">Guardar Contenido</button>
        </form>
      </div>
    </div>
  </div>
</div>



<?php include '../Modulos/footer.php'; ?>

<script>
// Manejar cambios en el tipo de contenido
$(document).ready(function() {
    $('#tipo_contenido').change(function() {
        const tipo = $(this).val();
        let html = '';
        
        if (tipo === 'enlace') {
            html = `
                <div class="form-group">
                    <label for="enlace_url">URL del Enlace</label>
                    <input type="url" class="form-control" id="enlace_url" name="enlace_url" required>
                </div>
            `;
        } else if (tipo !== '') {
            html = `
                <div class="form-group">
                    <label for="archivo">Archivo</label>
                    <input type="file" class="form-control-file" id="archivo" name="archivo" ${tipo === 'documento' ? 'accept=".pdf,.doc,.docx,.ppt,.pptx"' : ''}>
                </div>
            `;
        }
        
        $('#camposDinamicos').html(html);
    });
});
</script>