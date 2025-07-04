<?php
session_start();
if (!isset($_SESSION['participante_id'])) {
  header("Location: login.php");
  exit();
}
$clave_curso = $_GET['clave'] ?? null;
include '../Modulos/HeadP.php';

// Obtener información del participante y sus inscripciones
require_once '../DB/Conexion.php';
$database = new Database();

$participante_id = $_SESSION['participante_id'];
$query = "SELECT i.id_inscripcion, i.IdOpcionPago as id_opcion_pago, c.clave_curso, c.nombre_curso, i.estado, i.fecha_inscripcion
          FROM inscripciones i
          JOIN cursos c ON i.id_curso = c.id_curso
          WHERE i.id_participante = ?";
$stmt = $database->getConnection()->prepare($query);
$stmt->bind_param("i", $participante_id);
$stmt->execute();
$result = $stmt->get_result();

// Obtener cédula y documento del participante
$stmtCedula = $database->getConnection()->prepare("SELECT cedula, documento FROM participantes WHERE id_participante = ?");
$stmtCedula->bind_param("i", $participante_id);
$stmtCedula->execute();
$stmtCedula->bind_result($cedula, $documento);
$stmtCedula->fetch();
$stmtCedula->close();
?>

<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">Mi Panel - <?= htmlspecialchars($_SESSION['nombre']) ?></h4>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-8">
            <h5>Mis Inscripciones</h5>
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Curso</th>
                    <th>Fecha Inscripción</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                      <td><?= htmlspecialchars($row['nombre_curso']) ?></td>
                      <td><?= date('d/m/Y', strtotime($row['fecha_inscripcion'])) ?></td>
                      <td>
                        <span class="badge badge-<?=
                                                  $row['estado'] == 'pago_validado' ? 'success' : ($row['estado'] == 'registrado' ? 'warning' : 'secondary')
                                                  ?>">
                          <?= ucfirst(str_replace('_', ' ', $row['estado'])) ?>
                        </span>
                      </td>
                      <td>
                        <?php if ($row['id_opcion_pago'] && ($row['estado'] == 'registrado' || $row['estado'] == 'Revision de pago')): ?>
                          <a href="pagos.php?id=<?= $row['id_inscripcion'] ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-receipt"></i> Ver pagos
                          </a>
                        <?php endif; ?>
                        <?php
                        if ($row['estado'] == 'registrado' && empty($row['id_opcion_pago'])): ?>
                          <button class="btn btn-sm btn-primary open-modal"
                            data-inscripcion="<?= $row['id_inscripcion'] ?>"
                            data-curso="<?= htmlspecialchars($row['nombre_curso']) ?>">
                            <i class="fas fa-upload"></i> Subir comprobante
                          </button>

                        <?php endif; ?>
                        <?php if ($row['estado'] == 'pago_validado'): ?>
                          <a href="curso/contenido.php?clave=<?= $row['clave_curso'] ?>" class="btn btn-sm btn-primary">

                            Ir al Curso
                          </a>

                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card">
              <div class="card-header">
                <h5>Mi Perfil</h5>
              </div>
              <div class="card-body">
                <p><strong>Nombre:</strong> <?= htmlspecialchars($_SESSION['nombre']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($_SESSION['email']) ?></p>
                <p class="d-flex align-items-center"><strong class="me-2">Cédula profesional:</strong>
                  <input type="password" id="cedulaInput" class="form-control-plaintext me-2" value="<?= htmlspecialchars($cedula) ?>" readonly style="width:auto;">
                  <button type="button" id="toggleCedula" class="btn btn-link p-0"><i class="fas fa-eye"></i></button>
                </p>
                <a href="mi_perfil.php" class="btn btn-primary">Mi perfil</a>
                <hR>
                <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
              </div>
            </div>
          </div>
          <div class="card col-lg-4 mb-4">
            <div class="card-header">
              <h5>Agregar Nuevo Curso</h5>
            </div>
            <div class="card-body">
              <form id="formAgregarCurso" class="form-inline">
                <div class="form-group mx-sm-3 mb-2">
                  <label for="claveCurso" class="sr-only">Clave del Curso</label>
                  <input type="text" class="form-control" value="<?php echo $clave_curso; ?>" id="claveCurso" placeholder="Ingresa la clave del curso" required>
                </div>
                <button type="submit" class="btn btn-primary mb-2">
                  <i class="fas fa-plus"></i> Agregar Curso
                </button>
              </form>
          <div id="mensajeClave" class="mt-2"></div>
        </div>
      </div>
      <div class="card col-lg-4 mb-4">
        <div class="card-header">
          <h5>Documento de Estudios</h5>
        </div>
        <div class="card-body">
          <?php if ($documento): ?>
            <a href="../documentos/<?= htmlspecialchars($documento) ?>" target="_blank" class="btn btn-info mb-2">
              <i class="fas fa-file"></i> Ver Documento
            </a>
            <p>Si deseas reemplazarlo, sube uno nuevo.</p>
          <?php else: ?>
            <div class="alert alert-warning">Es importante subir tu documento.</div>
          <?php endif; ?>
          <form id="formDocumento" enctype="multipart/form-data">
            <input type="file" name="documento" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png" required>
            <button type="submit" class="btn btn-primary mt-2">Subir documento</button>
          </form>
          <div id="msgDocumento" class="mt-2"></div>
        </div>
      </div>
    </div>
  </div>
</div>
  </div>
</div>

<!-- Modal para subir comprobante -->
<?php
$result->data_seek(0); // Reiniciar el puntero del resultado
while ($row = $result->fetch_assoc()):
  if ($row['estado'] == 'registrado'):
?>
    <!-- Elimina los modales PHP y reemplaza con este código al final del archivo, antes del footer -->
    <div id="modalComprobante" class="modal">
      <div class="modal-content" style="width:90%;max-width:500px;margin:10% auto;">
      <span class="close-modal">&times;</span>
      <h3 id="modalTitle"></h3>
      <form id="formComprobante" enctype="multipart/form-data">
        <input type="hidden" id="idInscripcion" name="id_inscripcion">

        <div class="form-group">
        <label>Método de Pago</label>
        <select name="metodo_pago" class="form-control" required>
          <option value="">Seleccionar...</option>
          <option value="Transferencia">Transferencia Bancaria</option>
          <option value="Oxxo">Oxxo</option>
          <option value="Deposito">Depósito</option>
          <option value="Paypal">PayPal</option>
          <option value="Tarjeta">Tarjeta de Crédito/Débito</option>
        </select>
        </div>

        <div class="form-group">
        <label>Referencia de Pago</label>
        <input type="text" name="referencia_pago" class="form-control" required>
        </div>

        <div class="form-group">
        <label>Monto Pagado</label>
        <input type="number" step="0.01" name="monto_pagado" class="form-control" required>
        </div>

        <div class="form-group">
        <label>Comprobante (PDF/Imagen)</label>
        <input type="file" name="comprobante" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png" required>
        <small class="form-text text-muted">Formatos aceptados: PDF, JPG, PNG (Máx. 2MB)</small>
        </div>

        <div class="form-group text-right">
        <button type="button" class="btn btn-sm btn-secondary close-modal">Cancelar</button>
        <button type="submit" class="btn btn-sm btn-primary">Enviar Comprobante</button>
        </div>
      </form>
      </div>
    </div>
    <style>
      @media (max-width: 600px) {
      .modal-content {
        width: 98% !important;
        max-width: 98vw !important;
        margin: 20% auto !important;
        padding: 10px !important;
      }
      }
    </style>

    <style>
      .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
      }

      .modal-content {
        background-color: #fefefe;
        margin: 10% auto;
        padding: 20px;
        border-radius: 5px;
        width: 50%;
        max-width: 600px;
      }

      .close-modal {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
      }

      .close-modal:hover {
        color: black;
      }
    </style>

    <script>
      // Manejo del modal con JavaScript
      document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalComprobante');
        const modalTitle = document.getElementById('modalTitle');
        const form = document.getElementById('formComprobante');

        // Botones para abrir modal (deben tener clase 'open-modal' y data-inscripcion y data-curso)
        document.querySelectorAll('.open-modal').forEach(button => {
          button.addEventListener('click', function() {
            const cursoNombre = this.getAttribute('data-curso');
            const inscripcionId = this.getAttribute('data-inscripcion');

            modalTitle.textContent = `Subir comprobante para ${cursoNombre}`;
            document.getElementById('idInscripcion').value = inscripcionId;
            modal.style.display = 'block';
          });
        });

        // Cerrar modal
        document.querySelectorAll('.close-modal').forEach(button => {
          button.addEventListener('click', function() {
            modal.style.display = 'none';
          });
        });

        // Cerrar al hacer clic fuera del modal
        window.addEventListener('click', function(event) {
          if (event.target === modal) {
            modal.style.display = 'none';
          }
        });

        // Envío del formulario con Fetch API
        form.addEventListener('submit', function(e) {
          e.preventDefault();

          const formData = new FormData(this);
          const submitBtn = this.querySelector('button[type="submit"]');
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

          fetch('subir_comprobante.php', {
              method: 'POST',
              body: formData
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                alert(data.message);
                window.location.reload();
              } else {
                alert('Error: ' + data.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Enviar Comprobante';
              }
            })
            .catch(error => {
              console.error('Error:', error);
              alert('Error al enviar el formulario');
              submitBtn.disabled = false;
              submitBtn.innerHTML = 'Enviar Comprobante';
            });
        });
      });
    </script>
<?php
  endif;
endwhile;
?>
<script>
  document.getElementById('formAgregarCurso').addEventListener('submit', function(e) {
    e.preventDefault();

    const clave = document.getElementById('claveCurso').value.trim();
    const mensajeDiv = document.getElementById('mensajeClave');
    const boton = this.querySelector('button[type="submit"]');

    if (!clave) {
      mensajeDiv.innerHTML = '<div class="alert alert-warning">Por favor ingresa una clave</div>';
      return;
    }

    // Deshabilitar botón durante la solicitud
    boton.disabled = true;
    boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

    fetch('agregar_curso.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          clave_curso: clave
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          mensajeDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
          // Recargar la página después de 2 segundos
          setTimeout(() => {
            // Obtiene la URL actual sin parámetros GET
            const nuevaURL = window.location.pathname;
            // Redirige sin recargar (reemplaza el estado del historial)
            window.history.replaceState({}, document.title, nuevaURL);
            // Luego recarga la página
            window.location.reload();
          }, 2000);
        } else {
          mensajeDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }
      })
      .catch(error => {
        mensajeDiv.innerHTML = '<div class="alert alert-danger">Error en la conexión</div>';
      })
      .finally(() => {
        boton.disabled = false;
        boton.innerHTML = '<i class="fas fa-plus"></i> Agregar Curso';
      });
  });

  // Subir documento de estudios
  document.getElementById('formDocumento').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';
    fetch('subir_documento.php', { method: 'POST', body: formData })
      .then(r => r.json())
      .then(d => {
        const div = document.getElementById('msgDocumento');
        div.innerHTML = `<div class="alert alert-${d.success ? 'success' : 'danger'}">${d.message}</div>`;
        if (d.success) setTimeout(() => location.reload(), 1000);
      })
      .catch(() => {
        document.getElementById('msgDocumento').innerHTML = '<div class="alert alert-danger">Error en la conexión</div>';
      })
      .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Subir documento';
      });
  });

  // Mostrar/ocultar cédula
  document.getElementById('toggleCedula').addEventListener('click', function() {
    const input = document.getElementById('cedulaInput');
    const icon = this.querySelector('i');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
    }
  });
</script>
<?php include '../Modulos/Footer.php'; ?>