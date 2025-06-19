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

// Obtener contenido del curso
$contenido = $database->getContenidoCursoParticipante($id_curso);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4><?= htmlspecialchars($curso['nombre_curso']) ?></h4>
                        <div>
                            <a href="reuniones.php?clave=<?= $clave_curso ?>" class="btn btn-light btn-sm mr-2">
                                <i class="fas fa-video"></i> Reuniones
                            </a>
                            <a href="info.php?clave=<?= $clave_curso ?>" class="btn btn-light btn-sm">
                                <i class="fas fa-info-circle"></i> Información
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-book"></i> Contenido del Curso</h5>
                    
                    <?php if (empty($contenido)): ?>
                        <div class="alert alert-info">
                            Este curso no tiene contenido disponible aún.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 40px;">Tipo</th>
                                        <th>Título / Descripción</th>
                                        <th style="width: 120px;">Fecha</th>
                                        <th style="width: 120px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $iconosContenido = [
                                        'documento' => 'fa-file-pdf text-danger',
                                        'video' => 'fa-video text-primary',
                                        'enlace' => 'fa-link text-success',
                                        'presentacion' => 'fa-presentation text-warning',
                                        'tarea' => 'fa-tasks text-info'
                                    ];
                                    ?>
                                    <?php foreach ($contenido as $item): ?>
                                        <tr>
                                            <td class="text-center align-middle">
                                                <?php
                                                $claseIcono = $iconosContenido[$item['tipo_contenido']] ?? 'fa-file-alt text-secondary';
                                                ?>
                                                <i class="fas <?= $claseIcono ?>"></i>
                                            </td>
                                            <td>
                                                <?php if (!empty($item['descripcion'])): ?>
                                                    <?= nl2br(htmlspecialchars($item['descripcion'])) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="align-middle">
                                                <small class="text-muted">
                                                    <?= date('d/m/Y', strtotime($item['fecha_publicacion'])) ?>
                                                </small>
                                            </td>
                                            <td class="align-middle">
                                                <?php if ($item['tipo_contenido'] === 'enlace' && !empty($item['enlace_url'])): ?>
                                                    <a href="/<?= htmlspecialchars($item['enlace_url']) ?>"
                                                       target="_blank"
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-external-link-alt"></i> Enlace
                                                    </a>
                                                <?php elseif (!empty($item['archivo_ruta'])): ?>
                                                    <a href="/<?= htmlspecialchars($item['archivo_ruta']) ?>"
                                                       download
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-download"></i> Descargar
                                                    </a>
                                                <?php endif; ?>
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

<?php include '../../Modulos/footer.php'; ?>