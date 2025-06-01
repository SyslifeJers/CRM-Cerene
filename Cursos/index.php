<?php



include '../Modulos/head.php';

?>

<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">Cursos</h4>
        <a href="alta.php" class="btn btn-primary float-right">Registrar Nuevo Curso</a>
      </div>
      <div class="card-body">
        <div class="table-responsive">

          <?php
          date_default_timezone_set('America/Mexico_City');
          $hoy = date('Y-m-d');
          echo $hoy;
            require_once '../DB/Conexion.php';
            $database = new Database();
            echo $database->getCursosTable();
            $database->closeConnection();


          ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../Modulos/footer.php'; ?>

    <!-- Inicializar DataTable -->
    <script>
        function copiarEnlace(enlace) {
            navigator.clipboard.writeText(enlace);
            alert("Enlace copiado: " + enlace);
        };
    </script>