<?php
include '../../Modulos/Head.php';
require_once '../../DB/Conexion.php';

$id_reunion = $_GET['id'] ?? null;

if (!$id_reunion || !is_numeric($id_reunion)) {
    header("Location: ../cursos.php?error=reunion_no_valida");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Obtener datos actuales de la reunión
$stmt = $conn->prepare("SELECT * FROM reuniones_zoom WHERE id_reunion = ?");
$stmt->bind_param("i", $id_reunion);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../cursos.php?error=reunion_no_encontrada");
    exit();
}

$reunion = $result->fetch_assoc();
$id_curso = $reunion['id_curso'];
?>

<div class="row">
  <div class="col-md-8 mx-auto">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">Editar Reunión Zoom</h4>
        <a href="curso.php?id=<?= $id_curso ?>" class="btn btn-secondary float-right">Volver al Curso</a>
      </div>
      <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
          <div class="alert alert-danger">
            <?= is_array($_GET['error']) ? implode(', ', $_GET['error']) : htmlspecialchars($_GET['error']) ?>
          </div>
        <?php endif; ?>

        <form action="procesar_edicion.php" method="POST">
          <input type="hidden" name="id_reunion" value="<?= $id_reunion ?>">
          <input type="hidden" name="id_curso" value="<?= $id_curso ?>">

          <div class="form-group">
            <label for="titulo">Título de la Reunión</label>
            <input type="text" class="form-control" id="titulo" name="titulo" value="<?= htmlspecialchars($reunion['titulo']) ?>" required>
          </div>

          <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($reunion['descripcion']) ?></textarea>
          </div>

          <div class="form-group">
            <label for="fecha_hora">Fecha y Hora de la Reunión</label>
            <input type="datetime-local" class="form-control" id="fecha_hora" name="fecha_hora"
                   value="<?= date('Y-m-d\TH:i', strtotime($reunion['fecha_hora'])) ?>" required>
          </div>

          <div class="form-group">
            <label for="duracion_minutos">Duración (minutos)</label>
            <input type="number" class="form-control" id="duracion_minutos" name="duracion_minutos" min="15" value="<?= $reunion['duracion_minutos'] ?>">
          </div>

          <div class="form-group">
            <label for="url_zoom">URL de la Reunión Zoom</label>
            <input type="url" class="form-control" id="url_zoom" name="url_zoom" value="<?= htmlspecialchars($reunion['url_zoom']) ?>" required>
          </div>

            <div class="form-group">
              <label for="codigo_acceso">Código de Acceso (opcional)</label>
              <input type="text" class="form-control" id="codigo_acceso" name="codigo_acceso" value="<?= htmlspecialchars($reunion['codigo_acceso']) ?>">
            </div>

            <div class="form-group">
              <label for="pago_necesario">Pago necesario ($)</label>
              <input type="number" class="form-control" id="pago_necesario" name="pago_necesario" min="0" step="0.01" value="<?= isset($reunion['PagoPorce']) ? $reunion['PagoPorce'] : 0 ?>">
            </div>

          <button type="submit" class="btn btn-primary">Actualizar Reunión</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include '../../Modulos/Footer.php'; ?>
