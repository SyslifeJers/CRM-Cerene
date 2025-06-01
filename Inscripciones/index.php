<?php



include '../Modulos/head.php';

?>

<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">Citas</h4>
      </div>
      <div class="card-body">
        <div class="table-responsive">

          <?php
          date_default_timezone_set('America/Mexico_City');
          $hoy = date('Y-m-d');
          echo $hoy;
            require_once '../DB/Conexion.php';
            $database = new Database();
            echo $database->getInscripcionesTable();
            $database->closeConnection();


          ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../Modulos/footer.php'; ?>

