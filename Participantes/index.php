<?php



include '../Modulos/head.php';
require_once '../DB/Conexion.php';
$database = new Database();

// Obtener ID del curso desde GET
$id_curso = isset($_GET['id_curso']) ? intval($_GET['id_curso']) : null;
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
    <form method="POST" action="enviar_correo_masivo.php" target="_blank">
    <input type="hidden" name="id_curso" value="<?php echo $id_curso; ?>">
    <button type="submit" class="btn btn-primary mb-3">
        <i class="fas fa-envelope"></i> Enviar correo a todos los inscritos
    </button>
</form>
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


    <!-- Modal Comprobante -->
    <div class="modal fade" id="modalComprobante" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Revisión de Comprobante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="visorDocumento" class="text-center mb-3" style="min-height: 500px;">
                        <!-- Contenido dinámico -->
                    </div>
                    
                    <form id="formRechazo" class="d-none">
                        <input type="hidden" name="id_inscripcion" id="idInscripcionRechazo">
                        <div class="mb-3">
                            <label class="form-label">Motivo de Rechazo</label>
                            <select class="form-select" name="motivo" required>
                                <option value="">Seleccione un motivo...</option>
                                <option value="documento_no_legible">Documento no legible</option>
                                <option value="informacion_incompleta">Información incompleta</option>
                                <option value="monto_incorrecto">Monto incorrecto</option>
                                <option value="documento_falsificado">Documento falsificado</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Detalles Adicionales</label>
                            <textarea class="form-control" name="detalle" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="btnAprobar">
                        <i class="fas fa-check-circle"></i> Aprobar
                    </button>
                    <button type="button" class="btn btn-danger" id="btnRechazar">
                        <i class="fas fa-times-circle"></i> Rechazar
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Ver comprobante
        $(".ver-comprobante").click(function() {
            const idInscripcion = $(this).data("id");
            const archivo = $(this).data("archivo");
            const extension = archivo.split(".").pop().toLowerCase();
            
            $("#idInscripcionRechazo").val(idInscripcion);
            $("#formRechazo").addClass("d-none");
            $("#btnAprobar, #btnRechazar").show();
            
            // Mostrar el documento según su tipo
            if (["jpg", "jpeg", "png"].includes(extension)) {
                $("#visorDocumento").html(`<img src="../comprobantes/${archivo}" class="img-fluid" alt="Comprobante">`);
            } else if (extension === "pdf") {
                $("#visorDocumento").html(`
                    <embed src="../comprobantes/${archivo}" type="application/pdf" width="100%" height="500px">
                    <div class="mt-2">
                        <a href="../comprobantes/${archivo}" target="_blank" class="btn btn-sm btn-info">
                            <i class="fas fa-download"></i> Descargar PDF
                        </a>
                    </div>
                `);
            } else {
                $("#visorDocumento").html(`
                    <div class="alert alert-warning">No hay vista previa disponible para este tipo de archivo</div>
                    <a href="../comprobantes/${archivo}" class="btn btn-info" download>
                        <i class="fas fa-download"></i> Descargar Comprobante
                    </a>
                `);
            }
            
            $("#modalComprobante").modal("show");
        });
        
        // Aprobar comprobante
        $("#btnAprobar").click(function() {
            const idInscripcion = $("#idInscripcionRechazo").val();
            
            Swal.fire({
                title: "¿Aprobar este comprobante?",
                text: "El estado cambiará a \'Pago Validado\'",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Sí, aprobar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "gestion_inscripcion.php",
                        type: "POST",
                        data: {
                            accion: "aprobar",
                            id_inscripcion: idInscripcion
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire("Éxito", response.message, "success");
                                $("#modalComprobante").modal("hide");
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                Swal.fire("Error", response.message, "error");
                            }
                        },
                        dataType: "json"
                    });
                }
            });
        });
        
        // Rechazar comprobante
        $("#btnRechazar").click(function() {
            $("#formRechazo").removeClass("d-none");
            $(this).hide();
            $("#btnAprobar").hide();
        });
        
        // Enviar formulario de rechazo
        $("#formRechazo").submit(function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize();
            
            Swal.fire({
                title: "¿Rechazar este comprobante?",
                text: "El estado cambiará a \'Rechazado\'",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, rechazar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "gestion_inscripcion.php",
                        type: "POST",
                        data: formData + "&accion=rechazar",
                        success: function(response) {
                            if (response.success) {
                                Swal.fire("Éxito", response.message, "success");
                                $("#modalComprobante").modal("hide");
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                Swal.fire("Error", response.message, "error");
                            }
                        },
                        dataType: "json"
                    });
                }
            });
        });
        
        // Cambiar estado desde el dropdown
        $(".cambiar-estado").click(function(e) {
            e.preventDefault();
            const id = $(this).data("id");
            const estado = $(this).data("estado");
            
            cambiarEstadoInscripcion(id, estado);
        });
        
        // Rechazar desde el dropdown
        $(".rechazar-inscripcion").click(function(e) {
            e.preventDefault();
            const id = $(this).data("id");
            
            $("#idInscripcionRechazo").val(id);
            $("#formRechazo").removeClass("d-none");
            $("#visorDocumento").html("<div class=\"alert alert-info\">Seleccione motivo de rechazo</div>");
            $("#btnAprobar, #btnRechazar").hide();
            $("#modalComprobante").modal("show");
        });
    });
    
    function cambiarEstadoInscripcion(id, estado) {
        Swal.fire({
            title: "¿Cambiar estado?",
            text: `El estado cambiará a \"${estado.replace("_", " ")}\"`,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Sí, cambiar",
            cancelButtonText: "Cancelar"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "gestion_inscripcion.php",
                    type: "POST",
                    data: {
                        accion: "cambiar_estado",
                        id_inscripcion: id,
                        estado: estado
                        
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire("Éxito", response.message, "success");
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            Swal.fire("Error", response.message, "error");
                        }
                    },
                    dataType: "json"
                });
            }
        });
    }
    </script>