<?php



include '../Modulos/Head.php';

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

<?php include '../Modulos/Footer.php'; ?>

    <!-- Inicializar DataTable -->
    <script>
        function copiarEnlace(enlace) {
            navigator.clipboard.writeText(enlace);
            alert("Enlace copiado: " + enlace);
        };

        function cambiarEstadoCurso(id_curso, nuevo_estado) {
    const accion = nuevo_estado === 1 ? "activar" : "desactivar";
    Swal.fire({
        title: `¿Estás seguro de que deseas ${accion} este curso?`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: `Sí, ${accion}`,
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "cambiar_estado_curso.php",
                type: "POST",
                data: { id_curso: id_curso, activo: nuevo_estado },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        Swal.fire("Éxito", response.message, "success");
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        Swal.fire("Error", response.message, "error");
                    }
                },
                error: function() {
                    Swal.fire("Error", "No se pudo conectar con el servidor.", "error");
                }
            });
        }
    });
}
new DataTable('#cursosTable');
    </script>
    