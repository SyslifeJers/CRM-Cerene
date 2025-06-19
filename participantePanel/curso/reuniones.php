<?php
session_start();
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

// Verificar que el participante está inscrito en el curso
$query = "SELECT c.id_curso, c.nombre_curso, i.estado 
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

// Obtener reuniones del curso
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
                        <div class="alert alert-info">
                            No hay reuniones programadas para este curso.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($reuniones as $reunion): 
                                $fecha_reunion = new DateTime($reunion['fecha_hora']);
                                $fin_reunion = (clone $fecha_reunion)->add(new DateInterval('PT'.$reunion['duracion_minutos'].'M'));
                                $ahora = new DateTime();
                                
                                $estado = '';
                                $boton = '';
                                
                                if ($ahora < $fecha_reunion) {
                                    $estado = 'badge-info';
                                    $texto_estado = 'Programada';
                                } elseif ($ahora >= $fecha_reunion && $ahora <= $fin_reunion) {
                                    $estado = 'badge-success';
                                    $texto_estado = 'En vivo';
                                    $boton = '<a href="'.htmlspecialchars($reunion['url_zoom']).'" 
                                               target="_blank" 
                                               class="btn btn-sm btn-success">
                                                <i class="fas fa-video"></i> Unirse ahora
                                              </a>';
                                } else {
                                    $estado = 'badge-secondary';
                                    $texto_estado = 'Finalizada';
                                    if (!empty($reunion['grabacion_url'])) {
                                        $boton = '<a href="'.htmlspecialchars($reunion['grabacion_url']).'" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-play-circle"></i> Ver grabación
                                                  </a>';
                                    }
                                }
                            ?>
                                <div class="list-group-item mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5><?= htmlspecialchars($reunion['titulo']) ?></h5>
                                        <span class="badge <?= $estado ?>"><?= $texto_estado ?></span>
                                    </div>
                                    
                                    <div class="row mt-2">
                                        <div class="col-md-6">
                                            <p><i class="fas fa-calendar-alt"></i> <strong>Fecha:</strong> 
                                            <?= $fecha_reunion->format('d/m/Y H:i') ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><i class="fas fa-clock"></i> <strong>Duración:</strong> 
                                            <?= $reunion['duracion_minutos'] ?> minutos</p>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($reunion['descripcion'])): ?>
                                        <p class="mt-2"><?= nl2br(htmlspecialchars($reunion['descripcion'])) ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between mt-3">
                                        <button onclick="copiarEnlace('<?= htmlspecialchars($reunion['url_zoom']) ?>')" 
                                                class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-copy"></i> Copiar enlace
                                        </button>
                                        <?= $boton ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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
</script>

<?php include '../../Modulos/footer.php'; ?>