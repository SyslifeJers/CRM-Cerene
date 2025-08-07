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
$query = "SELECT c.id_curso, c.nombre_curso, c.costo, i.id_inscripcion, i.estado , i.monto_pagado
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

if ($curso['estado'] == 'pago_validado') {
    $total_pagado = floatval($curso['monto_pagado']);
}
else{
    // Total pagado con comprobantes validados
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
}



// Obtener contenido del curso
$contenido = $database->getContenidoCursoParticipante($id_curso);
echo '<p style="display: none;" class="">' . $total_pagado . ', ' . $curso['estado'] . '</p>';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4><?= htmlspecialchars($curso['nombre_curso']) ?></h4>
                        <div>
                            <a href="reuniones.php?clave=<?= $clave_curso ?>" class="btn btn-success btn-sm mr-2 btn-llamativo">
                            <i class="fas fa-video girar-icono"></i> Reuniones
                            </a>
                            <a href="info.php?clave=<?= $clave_curso ?>" class="btn btn-light btn-sm">
                                <i class="fas fa-info-circle"></i> Información
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-book"></i> Contenido, lo mas actual primero</h5>
                    
                    <?php if (empty($contenido)): ?>
                        <div class="alert alert-info">
                            Este curso no tiene contenido disponible aún.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="tabla-contenido">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 20px;">#</th>
                                        <th>Título / Descripción</th>
                                              <th style="width: 120px;">Links</th>
                                        <th style="width: 120px;">Fecha</th>
                                  
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
                                    <?php foreach ($contenido as $item):
                                        if($curso['estado'] != 'pago_validado'){
                                            $requiere = isset($item['PagoPorce']) ? floatval($item['PagoPorce']) : 0;
                                            $acceso = $requiere == 0 || $total_pagado >= $requiere;
                                        $requiere = isset($item['PagoPorce']) ? floatval($item['PagoPorce']) : 0;
                                        $acceso = $requiere == 0 || $total_pagado >= $requiere;
                                        if (!$acceso) continue;
                                        }
                                    ?>
                                        <tr>
                                            <td class="text-center align-middle">
                                                <?php
                                                $claseIcono = $iconosContenido[$item['tipo_contenido']] ?? 'fa-file-alt text-secondary';
                                                ?>
                                                <i class="fas <?= $claseIcono ?>"></i>
                                            </td>
                                            <td>
                                                <?php if (!empty($item['titulo'])): ?>
                                                    <strong><?= htmlspecialchars($item['titulo']) ?></strong><br>
                                                <?php endif; ?>
                                                <?php if (!empty($item['descripcion'])): ?>
                                                    <?= nl2br(htmlspecialchars($item['descripcion'])) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="align-middle">
                                                <?php if ($item['tipo_contenido'] === 'enlace' && !empty($item['enlace_url'])): ?>
                                                    <a href="<?= htmlspecialchars($item['enlace_url']) ?>"
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
                                            <td class="align-middle">
                                                <small class="text-muted">
                                                    <?= date('d/m/Y', strtotime($item['fecha_publicacion'])) ?>
                                                </small>
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

<?php include '../../Modulos/Footer.php'; ?>
<Script>
new DataTable('#tabla-contenido', {
  pageLength: 10,
  order: [[3, 'desc']], // Ordena por la primera columna (índice 0) en forma descendente
  language: {
    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
  }
});
</Script>