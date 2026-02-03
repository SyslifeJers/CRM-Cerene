<?php



include '../Modulos/Head.php';
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

// Obtener opciones de pago disponibles para este curso
$opciones_pago = [];
$stmtOpc = $database->getConnection()->prepare("SELECT op.id_opcion, op.numero_pagos, f.tipo, f.dias
                        FROM opciones_pago op
                        JOIN frecuencia_pago f ON op.id_frecuencia = f.id_frecuencia
                        WHERE op.activo = 1");
$stmtOpc->execute();
$opciones_pago = $stmtOpc->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtOpc->close();
?>

<div class="row">
    <form method="POST" action="enviar_correo_masivo.php" target="_blank" class="mb-3 me-3">
    <input type="hidden" name="id_curso" value="<?php echo $id_curso; ?>">
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-envelope"></i> Enviar correo a todos los inscritos
    </button>
    </form>
    <form method="POST" action="exportar_csv.php" class="mb-3 me-3">
        <input type="hidden" name="id_curso" value="<?php echo $id_curso; ?>">
        <div class="input-group">
            <input type="text" name="prefijo" id="prefijo" class="form-control" placeholder="Prefijo">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-file-csv"></i> Pasar a CSV
            </button>
        </div>
        <label for="prefijo" class="form-label mt-1">Ejemplo: 1-002-@id</label>
    </form>
    <a href="exportar_csv.php?id_curso=<?php echo $id_curso; ?>" class="btn btn-secondary mb-3">
        <i class="fas fa-download"></i> Descargar reporte CSV
    </a>
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

<?php include '../Modulos/Footer.php'; ?>


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

                    <div class="mb-3">
                        <label for="montoDeclarado" class="form-label">Monto declarado</label>
                        <input type="number" step="0.01" class="form-control" id="montoDeclarado">
                    </div>

                    <div class="mb-3">
                        <label for="fechaPago" class="form-label">Fecha de pago</label>
                        <input type="date" class="form-control" id="fechaPago" value="<?php echo date('Y-m-d'); ?>">
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
                                  <button type="submit" class="btn btn-danger">Confirmar Rechazo</button>
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

    <!-- Modal Asignar Opción de Pago -->
    <div class="modal fade" id="modalOpcionPago" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Asignar opción de pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formOpcionPago">
                        <input type="hidden" name="id_inscripcion" id="idInscripcionPago">
                        <div class="mb-3">
                            <label class="form-label">Opción</label>
                            <select name="id_opcion" class="form-select" required>
                                <?php foreach ($opciones_pago as $op): ?>
                                    <option value="<?= $op['id_opcion'] ?>">
                                        <?= $op['numero_pagos'] ?> pagos - <?= ucfirst($op['tipo']) ?> (cada <?= $op['dias'] ?> días)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Asignar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nota -->
    <div class="modal fade" id="modalNota" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Nota</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="notaId">
                    <div class="mb-3">
                        <label for="notaInput" class="form-label">Nota</label>
                        <input type="text" class="form-control" id="notaInput">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="guardarNota">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('.asignar-opcion').click(function() {
            $('#idInscripcionPago').val($(this).data('id'));
            $('#modalOpcionPago').modal('show');
        });

        $('#formOpcionPago').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'gestion_inscripcion.php',
                type: 'POST',
                data: $(this).serialize() + '&accion=asignar_opcion_pago',
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Éxito', res.message, 'success');
                        $('#modalOpcionPago').modal('hide');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
            });
        });
        // Ver comprobante
        window.verComprobante = function(idInscripcion, archivo, monto, fecha) {
            const today = new Date().toISOString().split('T')[0];

            $("#idInscripcionRechazo").val(idInscripcion);
            $("#montoDeclarado").val(monto ?? "");
            $("#fechaPago").val(fecha || today);
            $("#formRechazo").addClass("d-none");
            $("#btnAprobar, #btnRechazar").show();

            if (!archivo) {
                $("#visorDocumento").html('<div class="alert alert-danger">No hay archivo de comprobante asociado a esta inscripción.</div>');
                $("#modalComprobante").modal("show");
                return;
            }

            const extension = archivo.split(".").pop().toLowerCase();
            const archivoUrl = `../comprobantes/${encodeURI(archivo)}`;

            $("#visorDocumento").html('<div class="text-muted">Cargando comprobante...</div>');
            $("#modalComprobante").modal("show");

            fetch(archivoUrl, { method: "HEAD" })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`No se pudo acceder al archivo (${response.status} ${response.statusText}).`);
                    }

                    // Mostrar el documento según su tipo
                    if (["jpg", "jpeg", "png"].includes(extension)) {
                        $("#visorDocumento").html(`<img src="${archivoUrl}" class="img-fluid" alt="Comprobante">`);
                    } else if (extension === "pdf") {
                        $("#visorDocumento").html(`
                            <embed src="${archivoUrl}" type="application/pdf" width="100%" height="500px">
                            <div class="mt-2">
                                <a href="${archivoUrl}" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fas fa-download"></i> Descargar PDF
                                </a>
                            </div>
                        `);
                    } else {
                        $("#visorDocumento").html(`
                            <div class="alert alert-warning">No hay vista previa disponible para este tipo de archivo</div>
                            <a href="${archivoUrl}" class="btn btn-info" download>
                                <i class="fas fa-download"></i> Descargar Comprobante
                            </a>
                        `);
                    }
                })
                .catch((error) => {
                    $("#visorDocumento").html(`
                        <div class="alert alert-danger">
                            No se pudo abrir el comprobante. ${error.message}
                        </div>
                    `);
                });
        };
        
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
                            id_inscripcion: idInscripcion,
                            monto_pagado: $("#montoDeclarado").val(),
                            fecha_pago: $("#fechaPago").val()
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

        $('.nota-btn').click(function () {
            $('#notaId').val($(this).data('id'));
            $('#notaInput').val($(this).data('nota'));
            $('#modalNota').modal('show');
        });

        $('#guardarNota').click(function () {
            $.ajax({
                url: 'gestion_inscripcion.php',
                type: 'POST',
                data: {
                    accion: 'guardar_nota',
                    id_inscripcion: $('#notaId').val(),
                    nota: $('#notaInput').val()
                },
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        Swal.fire('Éxito', res.message, 'success');
                        $('#modalNota').modal('hide');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
            });
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
new DataTable('#inscripcionesTable', {
  pageLength: 50,
  order: [[0, 'desc']], // Ordena por la primera columna (índice 0) en forma descendente
  language: {
    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
  }
});
  $('#modalComprobante, #modalOpcionPago, #modalNota').on('hide.bs.modal', function () {
    if (this.contains(document.activeElement)) {
        document.activeElement.blur();
    }
    this.setAttribute('inert', '');
  }).on('show.bs.modal', function () {
    this.removeAttribute('inert');
  });
    </script>
