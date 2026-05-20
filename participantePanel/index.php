<?php
session_start();
if (!isset($_SESSION['participante_id'])) {
  header("Location: login.php");
  exit();
}

$clave_curso = $_GET['clave'] ?? '';
include '../Modulos/HeadP.php';

require_once '../DB/Conexion.php';
$database = new Database();
$participante_id = $_SESSION['participante_id'];

$query = "SELECT i.id_inscripcion, i.IdOpcionPago as id_opcion_pago, c.clave_curso, c.nombre_curso, i.estado, i.fecha_inscripcion,
                 COALESCE((SELECT SUM(monto_pagado)
                           FROM comprobantes_inscripcion ci
                           WHERE ci.validado = 1
                             AND ci.id_inscripcion = i.id_inscripcion), 0) AS total_pagado
          FROM inscripciones i
          JOIN cursos c ON i.id_curso = c.id_curso
          WHERE i.id_participante = ?
          ORDER BY i.fecha_inscripcion DESC";
$stmt = $database->getConnection()->prepare($query);
$stmt->bind_param("i", $participante_id);
$stmt->execute();
$result = $stmt->get_result();
$inscripciones = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmtCedula = $database->getConnection()->prepare("SELECT cedula, documento FROM participantes WHERE id_participante = ?");
$stmtCedula->bind_param("i", $participante_id);
$stmtCedula->execute();
$stmtCedula->bind_result($cedula, $documento);
$stmtCedula->fetch();
$stmtCedula->close();

$totalCursos = count($inscripciones);
$cursosActivos = 0;
$pendientesPago = 0;
foreach ($inscripciones as $inscripcion) {
  if ($inscripcion['estado'] == 'pago_validado' || (float) $inscripcion['total_pagado'] > 0) {
    $cursosActivos++;
  }
  if ($inscripcion['estado'] == 'registrado' || $inscripcion['estado'] == 'Revision de pago' || $inscripcion['estado'] == 'pagos programados') {
    $pendientesPago++;
  }
}

function estadoCursoParticipante($estado) {
  $estados = [
    'pago_validado' => ['success', 'Acceso liberado'],
    'registrado' => ['warning', 'Pago/documentación pendiente'],
    'Revision de pago' => ['info', 'Pago en revisión'],
    'pagos programados' => ['info', 'Pagos programados'],
    'comprobante_enviado' => ['info', 'Comprobante enviado'],
    'rechazado' => ['danger', 'Requiere atención']
  ];

  return $estados[$estado] ?? ['secondary', ucfirst(str_replace('_', ' ', $estado))];
}
?>

<style>
  .panel-hero {
    background: linear-gradient(135deg, #12386b 0%, #177dff 100%);
    border-radius: 18px;
    color: #fff;
    padding: 28px;
    margin-bottom: 24px;
  }

  .panel-hero h2 {
    color: #fff;
    font-weight: 700;
    margin-bottom: 8px;
  }

  .quick-stat {
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.22);
    border-radius: 14px;
    padding: 14px 16px;
    min-height: 92px;
  }

  .quick-stat strong {
    display: block;
    color: #fff;
    font-size: 1.8rem;
    line-height: 1;
  }

  .section-card {
    border: 0;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(17, 38, 60, 0.08);
  }

  .section-card .card-header {
    background: #fff;
    border-bottom: 1px solid #eef1f6;
    border-radius: 16px 16px 0 0;
    padding: 18px 20px;
  }

  .course-card {
    background: transparent;
    border: 0;
    border-bottom: 1px solid #eef1f6;
    border-radius: 0;
    margin: 0;
    padding: 16px 0;
  }

  .course-card:last-child {
    border-bottom: 0;
  }

  .course-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .document-callout {
    border: 1px solid #d8e7ff;
    border-left: 5px solid #177dff;
    border-radius: 14px;
    background: #f4f9ff;
  }

  .document-callout.document-missing {
    border-color: #ffe0a3;
    border-left-color: #ffa534;
    background: #fff8eb;
  }

  .document-upload-box {
    border: 1px dashed #b7c7dc;
    border-radius: 12px;
    padding: 16px;
    background: #fbfdff;
  }

  .course-key-card {
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  }

  .course-key-icon {
    align-items: center;
    background: #eaf3ff;
    border-radius: 14px;
    color: #177dff;
    display: inline-flex;
    font-size: 1.35rem;
    height: 44px;
    justify-content: center;
    margin-bottom: 12px;
    width: 44px;
  }

  .course-key-help {
    background: #fff;
    border: 1px solid #eef1f6;
    border-radius: 12px;
    padding: 12px;
  }

  .helper-text {
    color: #6c757d;
    font-size: 0.9rem;
  }

  .modal-comprobante {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.55);
    padding: 20px;
    overflow-y: auto;
  }

  .modal-comprobante-content {
    background-color: #fff;
    margin: 6% auto;
    padding: 24px;
    border-radius: 16px;
    width: 100%;
    max-width: 560px;
    box-shadow: 0 18px 45px rgba(0, 0, 0, 0.2);
  }

  .modal-comprobante-content > .close-modal {
    color: #6c757d;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
  }

  .modal-comprobante-content > .close-modal:hover {
    color: #111;
  }

  .modal-comprobante-actions {
    align-items: center;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 22px;
  }

  .modal-comprobante-actions .btn-primary {
    border-radius: 10px;
    font-weight: 600;
    padding: 10px 18px;
  }

  .modal-comprobante-actions .btn-cancel {
    border-radius: 999px;
    font-size: 0.85rem;
    padding: 6px 12px;
  }

  @media (max-width: 767px) {
    .panel-hero {
      padding: 20px;
    }

    .course-card {
      padding: 14px;
    }

    .course-actions .btn {
      width: 100%;
    }

    .modal-comprobante-actions {
      align-items: stretch;
      flex-direction: column;
    }

    .modal-comprobante-actions .btn-primary {
      order: 1;
      width: 100%;
    }

    .modal-comprobante-actions .btn-cancel {
      align-self: center;
      order: 2;
    }
  }
</style>

<div class="panel-hero">
  <div class="row align-items-center">
    <div class="col-lg-7 mb-3 mb-lg-0">
      <h2>Mi Panel</h2>
      <p class="mb-0">Hola, <?= htmlspecialchars($_SESSION['nombre']) ?>. Aquí puedes revisar tus cursos, pagos y subir tu documento de estudios.</p>
    </div>
    <div class="col-lg-5">
      <div class="row">
        <div class="col-4">
          <div class="quick-stat">
            <strong><?= $totalCursos ?></strong>
            <span>Cursos inscritos</span>
          </div>
        </div>
        <div class="col-4">
          <div class="quick-stat">
            <strong><?= $cursosActivos ?></strong>
            <span>Con acceso</span>
          </div>
        </div>
        <div class="col-4">
          <div class="quick-stat">
            <strong><?= $documento ? 'Sí' : 'No' ?></strong>
            <span>Documento</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if (!$documento): ?>
<div class="row mb-4">
  <div class="col-lg-8 mb-4 mb-lg-0">
    <div class="card section-card document-callout <?= $documento ? '' : 'document-missing' ?>">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-7 mb-3 mb-md-0">
            <h4 class="mb-2"><i class="fas fa-file-upload me-2"></i> Documento que acredita tus estudios</h4>
            <p class="mb-2">Sube tu título, constancia, certificado o documento académico. Este archivo ayuda a validar tu perfil dentro de los cursos.</p>
            <?php if ($documento): ?>
              <a href="../documentos/<?= htmlspecialchars($documento) ?>" target="_blank" class="btn btn-info btn-sm">
                <i class="fas fa-file"></i> Ver documento actual
              </a>
              <span class="helper-text ms-2">Puedes reemplazarlo cuando lo necesites.</span>
            <?php else: ?>
              <div class="alert alert-warning mb-0 py-2">Aún no has subido este documento.</div>
            <?php endif; ?>
          </div>
          <div class="col-md-5">
            <form id="formDocumento" class="document-upload-box" enctype="multipart/form-data">
              <label class="fw-bold">Seleccionar archivo</label>
              <input type="file" name="documento" class="form-control-file mb-2" accept=".pdf,.jpg,.jpeg,.png" required>
              <small class="form-text text-muted mb-3">Formatos aceptados: PDF, JPG, PNG. Máximo 2MB.</small>
              <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-cloud-upload-alt"></i> Subir documento
              </button>
            </form>
            <div id="msgDocumento" class="mt-2"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card section-card course-key-card h-100">
      <div class="card-header">
        <h5 class="mb-0">Agregar un curso con clave</h5>
      </div>
      <div class="card-body">
        <div class="course-key-icon">
          <i class="fas fa-key"></i>
        </div>
        <p class="mb-2">¿Te compartieron una clave de inscripción?</p>
        <p class="helper-text">Escríbela aquí para que el curso aparezca en tu lista y puedas continuar con el pago o acceso correspondiente.</p>
        <div class="course-key-help mb-3">
          <div class="helper-text mb-1"><strong>1.</strong> Copia la clave que recibiste.</div>
          <div class="helper-text mb-1"><strong>2.</strong> Pégala en el campo de abajo.</div>
          <div class="helper-text"><strong>3.</strong> Presiona <strong>Agregar Curso</strong>.</div>
        </div>
        <form id="formAgregarCurso">
          <div class="form-group">
            <label for="claveCurso" class="fw-bold">Clave de inscripción</label>
            <input type="text" class="form-control form-control-lg" value="<?= htmlspecialchars($clave_curso) ?>" id="claveCurso" placeholder="Pega aquí tu clave" required>
            <small class="form-text text-muted">Ejemplo: CERENE-2026 o ABC123.</small>
          </div>
          <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="fas fa-plus-circle"></i> Agregar curso a mi panel
          </button>
        </form>
        <div id="mensajeClave" class="mt-2"></div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="row">
  <div class="col-lg-8 mb-4">
    <div class="card section-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h4 class="card-title mb-1">Mis cursos</h4>
          <p class="helper-text mb-0">Cada renglón muestra qué hacer: pagar, revisar pagos o entrar al curso.</p>
        </div>
        <?php if ($pendientesPago > 0): ?>
          <span class="badge bg-warning"><?= $pendientesPago ?> pendiente(s)</span>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if (empty($inscripciones)): ?>
          <div class="alert alert-info mb-0">Aún no tienes cursos inscritos. Agrega uno usando la clave del curso.</div>
        <?php else: ?>
          <?php foreach ($inscripciones as $row): ?>
            <?php [$badge, $estadoLabel] = estadoCursoParticipante($row['estado']); ?>
            <div class="course-card">
              <div class="row align-items-center">
                <div class="col-md-7 mb-3 mb-md-0">
                  <h5 class="mb-2"><?= htmlspecialchars($row['nombre_curso']) ?></h5>
                  <div class="helper-text mb-2">
                    <i class="far fa-calendar-alt"></i> Inscripción: <?= date('d/m/Y', strtotime($row['fecha_inscripcion'])) ?>
                  </div>
                  <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($estadoLabel) ?></span>
                  <?php if ((float) $row['total_pagado'] > 0): ?>
                    <span class="badge bg-success ms-1">Pago registrado</span>
                  <?php endif; ?>
                </div>
                <div class="col-md-5">
                  <div class="course-actions justify-content-md-end">
                    <?php if ($row['id_opcion_pago'] && ($row['estado'] == 'registrado' || $row['estado'] == 'pagos programados' || $row['estado'] == 'Revision de pago')): ?>
                      <a href="pagos.php?id=<?= (int) $row['id_inscripcion'] ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-receipt"></i> Ver pagos
                      </a>
                    <?php endif; ?>

                    <?php if ($row['estado'] == 'registrado' && empty($row['id_opcion_pago'])): ?>
                      <button class="btn btn-sm btn-primary open-modal"
                        data-inscripcion="<?= (int) $row['id_inscripcion'] ?>"
                        data-curso="<?= htmlspecialchars($row['nombre_curso'], ENT_QUOTES) ?>">
                        <i class="fas fa-upload"></i> Subir comprobante
                      </button>
                    <?php endif; ?>

                    <?php if ($row['estado'] == 'pago_validado' || (float) $row['total_pagado'] > 0): ?>
                      <a href="curso/contenido.php?clave=<?= urlencode($row['clave_curso']) ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-play-circle"></i> Ir al curso
                      </a>
                    <?php endif; ?>

                    <?php if ($row['estado'] == 'Revision de pago' || $row['estado'] == 'comprobante_enviado'): ?>
                      <span class="helper-text">Tu comprobante está en revisión.</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($documento): ?>
      <div class="card section-card document-callout mt-4">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col-md-7 mb-3 mb-md-0">
              <h4 class="mb-2"><i class="fas fa-file-alt me-2"></i> Documento de estudios</h4>
              <p class="mb-2">Tu documento ya está cargado. Si necesitas actualizarlo, puedes subir uno nuevo aquí.</p>
              <a href="../documentos/<?= htmlspecialchars($documento) ?>" target="_blank" class="btn btn-info btn-sm">
                <i class="fas fa-file"></i> Ver documento actual
              </a>
            </div>
            <div class="col-md-5">
              <form id="formDocumento" class="document-upload-box" enctype="multipart/form-data">
                <label class="fw-bold">Reemplazar documento</label>
                <input type="file" name="documento" class="form-control-file mb-2" accept=".pdf,.jpg,.jpeg,.png" required>
                <small class="form-text text-muted mb-3">Formatos aceptados: PDF, JPG, PNG. Máximo 2MB.</small>
                <button type="submit" class="btn btn-outline-primary w-100">
                  <i class="fas fa-sync-alt"></i> Actualizar documento
                </button>
              </form>
              <div id="msgDocumento" class="mt-2"></div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-4 mb-4">
    <?php if ($documento): ?>
      <div class="card section-card course-key-card mb-4">
        <div class="card-header">
          <h5 class="mb-0">Agregar un curso con clave</h5>
        </div>
        <div class="card-body">
          <div class="course-key-icon">
            <i class="fas fa-key"></i>
          </div>
          <p class="mb-2">¿Te compartieron una clave de inscripción?</p>
          <p class="helper-text">Pégala aquí para agregar el curso a tu panel.</p>
          <form id="formAgregarCurso">
            <div class="form-group">
              <label for="claveCurso" class="fw-bold">Clave de inscripción</label>
              <input type="text" class="form-control form-control-lg" value="<?= htmlspecialchars($clave_curso) ?>" id="claveCurso" placeholder="Pega aquí tu clave" required>
              <small class="form-text text-muted">Ejemplo: CERENE-2026 o ABC123.</small>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100">
              <i class="fas fa-plus-circle"></i> Agregar curso
            </button>
          </form>
          <div id="mensajeClave" class="mt-2"></div>
        </div>
      </div>
    <?php endif; ?>

    <div class="card section-card">
      <div class="card-header">
        <h5 class="mb-0">Mi Perfil</h5>
      </div>
      <div class="card-body">
        <p><strong>Nombre:</strong><br><?= htmlspecialchars($_SESSION['nombre']) ?></p>
        <p><strong>Email:</strong><br><?= htmlspecialchars($_SESSION['email']) ?></p>
        <div class="mb-3">
          <strong>Cédula profesional:</strong>
          <div class="d-flex align-items-center mt-1">
            <input type="password" id="cedulaInput" class="form-control-plaintext me-2" value="<?= htmlspecialchars($cedula) ?>" readonly>
            <button type="button" id="toggleCedula" class="btn btn-link p-0" title="Mostrar/ocultar cédula"><i class="fas fa-eye"></i></button>
          </div>
        </div>
        <a href="mi_perfil.php" class="btn btn-primary w-100 mb-2">Mi perfil</a>
        <a href="logout.php" class="btn btn-outline-danger w-100">Cerrar sesión</a>
      </div>
    </div>
  </div>
</div>

<div id="modalComprobante" class="modal-comprobante">
  <div class="modal-comprobante-content">
    <span class="close-modal">&times;</span>
    <h3 id="modalTitle" class="mb-3"></h3>
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
        <small class="form-text text-muted">Formatos aceptados: PDF, JPG, PNG. Máximo 2MB.</small>
      </div>

      <div class="form-group modal-comprobante-actions mb-0">

        <button type="button" class="btn btn-sm btn-outline-secondary btn-cancel close-modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">
          <i class="fas fa-paper-plane"></i> Enviar comprobante
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalComprobante');
    const modalTitle = document.getElementById('modalTitle');
    const formComprobante = document.getElementById('formComprobante');
    const formAgregarCurso = document.getElementById('formAgregarCurso');
    const formDocumento = document.getElementById('formDocumento');
    const toggleCedula = document.getElementById('toggleCedula');

    document.querySelectorAll('.open-modal').forEach(button => {
      button.addEventListener('click', function() {
        const cursoNombre = this.getAttribute('data-curso');
        const inscripcionId = this.getAttribute('data-inscripcion');

        modalTitle.textContent = `Subir comprobante para ${cursoNombre}`;
        document.getElementById('idInscripcion').value = inscripcionId;
        modal.style.display = 'block';
      });
    });

    document.querySelectorAll('.close-modal').forEach(button => {
      button.addEventListener('click', function() {
        modal.style.display = 'none';
      });
    });

    window.addEventListener('click', function(event) {
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    });

    formComprobante.addEventListener('submit', function(e) {
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
            Swal.fire('Éxito', data.message, 'success').then(() => window.location.reload());
          } else {
            Swal.fire('Error', data.message, 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Enviar Comprobante';
          }
        })
        .catch(() => {
          Swal.fire('Error', 'Error al enviar el formulario', 'error');
          submitBtn.disabled = false;
          submitBtn.innerHTML = 'Enviar Comprobante';
        });
    });

    formAgregarCurso.addEventListener('submit', function(e) {
      e.preventDefault();

      const clave = document.getElementById('claveCurso').value.trim();
      const mensajeDiv = document.getElementById('mensajeClave');
      const boton = this.querySelector('button[type="submit"]');

      if (!clave) {
        mensajeDiv.innerHTML = '<div class="alert alert-warning">Por favor ingresa una clave</div>';
        return;
      }

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
            setTimeout(() => {
              window.history.replaceState({}, document.title, window.location.pathname);
              window.location.reload();
            }, 2000);
          } else {
            mensajeDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
          }
        })
        .catch(() => {
          mensajeDiv.innerHTML = '<div class="alert alert-danger">Error en la conexión</div>';
        })
        .finally(() => {
          boton.disabled = false;
          boton.innerHTML = '<i class="fas fa-plus"></i> Agregar Curso';
        });
    });

    formDocumento.addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      const btn = this.querySelector('button[type="submit"]');
      const div = document.getElementById('msgDocumento');
      const textoOriginal = btn.innerHTML;

      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';

      fetch('subir_documento.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => {
          div.innerHTML = `<div class="alert alert-${d.success ? 'success' : 'danger'}">${d.message}</div>`;
          if (d.success) setTimeout(() => location.reload(), 1000);
        })
        .catch(() => {
          div.innerHTML = '<div class="alert alert-danger">Error en la conexión</div>';
        })
        .finally(() => {
          btn.disabled = false;
          btn.innerHTML = textoOriginal;
        });
    });

    toggleCedula.addEventListener('click', function() {
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
  });
</script>

<?php include '../Modulos/Footer.php'; ?>
