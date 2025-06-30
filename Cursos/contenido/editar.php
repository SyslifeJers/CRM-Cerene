<?php
include '../../Modulos/head.php';
require_once '../../DB/Conexion.php';

$id_contenido = $_GET['id'] ?? null;

if (!$id_contenido || !is_numeric($id_contenido)) {
    header("Location: ../../cursos.php?error=contenido_no_valido");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->prepare("SELECT * FROM contenido_curso WHERE id_contenido = ?");
$stmt->bind_param("i", $id_contenido);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../../cursos.php?error=contenido_no_encontrado");
    exit();
}

$contenido = $result->fetch_assoc();
$id_curso = $contenido['id_curso'];
?>

<div class="row">
  <div class="col-md-8 mx-auto">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">Editar Contenido</h4>
        <a href="../curso.php?id=<?= $id_curso ?>" class="btn btn-secondary float-right">Volver al Curso</a>
      </div>
      <div class="card-body">
        <form action="procesar_edicion.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="id_contenido" value="<?= $id_contenido ?>">
          <input type="hidden" name="id_curso" value="<?= $id_curso ?>">

          <div class="form-group">
            <label for="titulo">Título del Contenido</label>
            <input type="text" class="form-control" id="titulo" name="titulo" value="<?= htmlspecialchars($contenido['titulo']) ?>" required>
          </div>

          <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($contenido['descripcion']) ?></textarea>
          </div>

          <div class="form-group">
            <label for="tipo_contenido">Tipo de Contenido</label>
            <select class="form-control" id="tipo_contenido" name="tipo_contenido" required disabled>
              <option value="documento" <?= $contenido['tipo_contenido'] == 'documento' ? 'selected' : '' ?>>Documento</option>
              <option value="video" <?= $contenido['tipo_contenido'] == 'video' ? 'selected' : '' ?>>Video</option>
              <option value="enlace" <?= $contenido['tipo_contenido'] == 'enlace' ? 'selected' : '' ?>>Enlace</option>
              <option value="presentacion" <?= $contenido['tipo_contenido'] == 'presentacion' ? 'selected' : '' ?>>Presentación</option>
              <option value="tarea" <?= $contenido['tipo_contenido'] == 'tarea' ? 'selected' : '' ?>>Tarea</option>
            </select>
            <small class="text-muted">El tipo no se puede cambiar.</small>
          </div>

          <div id="camposDinamicos">
            <?php if ($contenido['tipo_contenido'] === 'enlace'): ?>
              <div class="form-group">
                <label for="enlace_url">URL del Enlace</label>
                <input type="url" class="form-control" id="enlace_url" name="enlace_url" value="<?= htmlspecialchars($contenido['enlace_url']) ?>" required>
              </div>
            <?php else: ?>
              <div class="form-group">
                <label>Archivo Actual</label><br>
                <?php if ($contenido['archivo_ruta']): ?>
                  <a href="/<?= htmlspecialchars($contenido['archivo_ruta']) ?>" target="_blank" class="btn btn-sm btn-primary">Ver archivo</a>
                <?php else: ?>
                  <span class="text-muted">Sin archivo</span>
                <?php endif; ?>
              </div>
              <div class="form-group">
                <label for="archivo">Reemplazar archivo (opcional)</label>
                <input type="file" class="form-control-file" id="archivo" name="archivo">
              </div>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="orden">Orden de visualización</label>
            <input type="number" class="form-control" id="orden" name="orden" min="0" value="<?= $contenido['orden'] ?>">
          </div>

          <button type="submit" class="btn btn-primary">Actualizar Contenido</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include '../../Modulos/Footer.php'; ?>
