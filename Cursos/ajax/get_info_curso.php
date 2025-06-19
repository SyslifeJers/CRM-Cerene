
<?php
require_once '../../DB/Conexion.php';
$id_curso = $_GET['id_curso'] ?? null;

if (!$id_curso) {
    echo '<div class="alert alert-danger">ID de curso no especificado</div>';
    exit();
}

$database = new Database();
$curso = $database->getCursoById($id_curso);

if (!$curso) {
    echo '<div class="alert alert-danger">Curso no encontrado</div>';
    exit();
}
?>

<div class="mt-4">
    <h5>Descripción del Curso</h5>
    <p><?= nl2br(htmlspecialchars($curso['descripcion'])) ?></p>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <h5>Detalles</h5>
            <ul class="list-group">
                <li class="list-group-item">
                    <strong>Fecha Inicio:</strong> <?= date('d/m/Y', strtotime($curso['fecha_inicio'])) ?>
                </li>
                <li class="list-group-item">
                    <strong>Fecha Fin:</strong> <?= date('d/m/Y', strtotime($curso['fecha_fin'])) ?>
                </li>
                <li class="list-group-item">
                    <strong>Costo:</strong> $<?= number_format($curso['costo'], 2) ?>
                </li>
            </ul>
        </div>
        <div class="col-md-6">
            <h5>Acceso</h5>
            <div class="input-group mb-3">
                <input type="text" class="form-control" id="enlaceInscripcion" 
                       value="<?= htmlspecialchars($curso['link_inscripcion']) ?>" readonly>
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" 
                            onclick="copiarEnlace('<?= htmlspecialchars($curso['link_inscripcion']) ?>')">
                        Copiar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>