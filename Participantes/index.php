<?php



include '../Modulos/head.php';
require_once '../DB/Conexion.php';
$database = new Database();

// Obtener ID del curso desde GET
$id_curso = isset($_GET['id_curso']) ? intval($_GET['id_curso']) : null;
echo '<h1>'.$id_curso.'</h1>';
// Verificar si se proporcionó un ID de curso válido
if ($id_curso === null) {
    die('<div class="alert alert-danger">Debe especificar un ID de curso válido</div>');
}

// Obtener nombre del curso para el título
$nombre_curso = 'Curso';
$query_curso = $database->getConnection()->prepare("SELECT nombre_curso FROM cursos WHERE id_curso = ?");

$query_curso->bind_param("i", $id_curso);
$query_curso->execute();
$result_curso = $query_curso->get_result();

if ($result_curso->num_rows > 0) {
    $row_curso = $result_curso->fetch_assoc();
    $nombre_curso = htmlspecialchars($row_curso['nombre_curso']);
}
?>

<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title"> Inscripciones: <?php echo $nombre_curso; ?></h4>
      </div>
      <div class="card-body">
        <div class="table-responsive">

          <?php
          date_default_timezone_set('America/Mexico_City');
          $hoy = date('Y-m-d');
          echo $hoy;
            

            echo $database->getInscripcionesTable($id_curso);



          ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../Modulos/footer.php'; ?>

