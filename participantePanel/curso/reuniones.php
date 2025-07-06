<?php
session_start();
date_default_timezone_set('America/Mexico_City');
if (!isset($_SESSION['participante_id'])) {
    header("Location: ../login.php");
    exit();
}

$clave_curso = $_GET['clave'] ?? null;
if (!$clave_curso) {
    header("Location: ../index.php");
    exit();
}

include '../../Modulos/HeadP.php';
require_once '../../DB/Conexion.php';

$database = new Database();
$participante_id = $_SESSION['participante_id'];

$query = "SELECT c.id_curso, c.nombre_curso, c.costo, i.id_inscripcion, i.estado
          FROM cursos c
          JOIN inscripciones i ON c.id_curso = i.id_curso
          WHERE c.clave_curso = ? AND i.id_participante = ?";
$stmt = $database->getConnection()->prepare($query);
$stmt->bind_param("si", $clave_curso, $participante_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../index.php?error=curso_no_encontrado");
    exit();
}

$curso = $result->fetch_assoc();
$id_curso = $curso['id_curso'];
$id_inscripcion = $curso['id_inscripcion'];
$costo_curso = $curso['costo'];

// Total pagado validado
$stmtPago = $database->getConnection()->prepare(
    "SELECT SUM(monto_pagado) AS total_pagado
       FROM comprobantes_inscripcion
      WHERE validado = 1 AND id_inscripcion = ?"
);
$stmtPago->bind_param("i", $id_inscripcion);
$stmtPago->execute();
$resPago = $stmtPago->get_result();
$rowPago = $resPago->fetch_assoc();
$total_pagado = $rowPago ? floatval($rowPago['total_pagado']) : 0.0;
$stmtPago->close();

$reuniones = $database->getReunionesZoomParticipante($id_curso);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4><?= htmlspecialchars($curso['nombre_curso']) ?></h4>
                        <div>
                            <a href="contenido.php?clave=<?= $clave_curso ?>" class="btn btn-light btn-sm mr-2">
                                <i class="fas fa-book"></i> Contenido
                            </a>
                            <a href="info.php?clave=<?= $clave_curso ?>" class="btn btn-light btn-sm">
                                <i class="fas fa-info-circle"></i> Información
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-video"></i> Reuniones Programadas</h5>

                    <?php if (empty($reuniones)): ?>
                        <div class="alert alert-info">No hay reuniones programadas para este curso.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Título</th>
                                        <th>Fecha y Hora</th>
                                        <th>Duración</th>
                                        <th>Estado</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reuniones as $reunion):
                                        $requiere = isset($reunion['PagoPorce']) ? floatval($reunion['PagoPorce']) : 0;
                                        $acceso = $requiere == 0 || $total_pagado >= ($costo_curso * $requiere / 100);
                                        if (!$acceso) continue;
                                        $fecha_reunion = new DateTime($reunion['fecha_hora']);
                                        $fin_reunion = (clone $fecha_reunion)->add(new DateInterval('PT'.$reunion['duracion_minutos'].'M'));
                                        $inicio_visible = (clone $fecha_reunion)->sub(new DateInterval('PT15M'));
                                        $ahora = new DateTime();

                                        $estado = '';
                                        $texto_estado = '';
                                        $boton = '';

                                        if ($ahora < $inicio_visible) {
                                            $estado = 'info';
                                            $texto_estado = 'Programada';
                                        } elseif ($ahora >= $inicio_visible && $ahora <= $fin_reunion) {
                                            $estado = 'success';
                                            $texto_estado = 'En vivo';
                                            $boton = '<a href="'.htmlspecialchars($reunion['url_zoom']).'" target="_blank" class="btn btn-sm btn-success"><i class="fas fa-video"></i> Unirse</a>';
                                        } else {
                                            $estado = 'secondary';
                                            $texto_estado = 'Finalizada';
                                            if (!empty($reunion['grabacion_url'])) {
                                                $boton = '<a href="'.htmlspecialchars($reunion['grabacion_url']).'" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-play-circle"></i> Ver grabación</a>';
                                            }
                                        }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($reunion['titulo']) ?></td>
                                        <td><?= $fecha_reunion->format('d/m/Y H:i') ?></td>
                                        <td><?= $reunion['duracion_minutos'] ?> min</td>
                                        <td><span class="badge bg-<?= $estado ?>"><?= $texto_estado ?></span></td>
                                        <td>
                                            <?php if ($ahora >= $inicio_visible && $ahora <= $fin_reunion): ?>
                                                <button onclick="copiarEnlace('<?= htmlspecialchars($reunion['url_zoom']) ?>')" class="btn btn-outline-secondary btn-sm mb-1">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                                <div id="contador_<?= $reunion['id_reunion'] ?>" class="text-muted small"></div>
                                            <?php endif; ?>
                                            <?= $boton ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copiarEnlace(enlace) {
    navigator.clipboard.writeText(enlace);
    alert('Enlace copiado al portapapeles: ' + enlace);
}

function iniciarContador(id, fechaInicioStr) {
    const contador = document.getElementById("contador_" + id);
    const inicio = new Date(fechaInicioStr).getTime();

    function actualizar() {
        const ahora = new Date().getTime();
        const diferencia = inicio - ahora;

        if (diferencia <= 0) {
            contador.innerHTML = "¡Ya inició!";
            return;
        }

        const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
        const segundos = Math.floor((diferencia % (1000 * 60)) / 1000);
        contador.innerHTML = `Inicia en ${minutos}m ${segundos}s`;
        setTimeout(actualizar, 1000);
    }

    actualizar();
}

window.addEventListener('load', function () {
    <?php foreach ($reuniones as $reunion):
        $requiere = isset($reunion['PagoPorce']) ? floatval($reunion['PagoPorce']) : 0;
        $acceso = $requiere == 0 || $total_pagado >= ($costo_curso * $requiere / 100);
        if (!$acceso) continue;
        $fecha_reunion = new DateTime($reunion['fecha_hora']);
        $fin_reunion = (clone $fecha_reunion)->add(new DateInterval('PT' . $reunion['duracion_minutos'] . 'M'));
        $inicio_visible = (clone $fecha_reunion)->sub(new DateInterval('PT15M'));
        $ahora = new DateTime();
        if ($ahora >= $inicio_visible && $ahora <= $fin_reunion): ?>
            iniciarContador("<?= $reunion['id_reunion'] ?>", "<?= $fecha_reunion->format('Y-m-d H:i:s') ?>");
    <?php endif; endforeach; ?>
});
</script>

<?php include '../../Modulos/Footer.php'; ?>
