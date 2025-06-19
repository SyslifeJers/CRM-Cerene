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

// Obtener información del curso y la inscripción
$query = "SELECT c.*, i.* 
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

$data = $result->fetch_assoc();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4><?= htmlspecialchars($data['nombre_curso']) ?></h4>
                        <div>
                            <a href="contenido.php?clave=<?= $clave_curso ?>" class="btn btn-light btn-sm mr-2">
                                <i class="fas fa-book"></i> Contenido
                            </a>
                            <a href="reuniones.php?clave=<?= $clave_curso ?>" class="btn btn-light btn-sm">
                                <i class="fas fa-video"></i> Reuniones
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <!-- Información del curso -->
                        <div class="col-md-6">
                            <h5><i class="fas fa-info-circle"></i> Información del Curso</h5>
                            
                            <div class="card mt-3">
                                <div class="card-body">
                                    <?php if (!empty($data['descripcion'])): ?>
                                        <p><?= nl2br(htmlspecialchars($data['descripcion'])) ?></p>
                                        <hr>
                                    <?php endif; ?>
                                    
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <i class="fas fa-calendar-day"></i> <strong>Inicio:</strong> 
                                            <?= date('d/m/Y', strtotime($data['fecha_inicio'])) ?>
                                        </li>
                                        <li class="list-group-item">
                                            <i class="fas fa-calendar-check"></i> <strong>Fin:</strong> 
                                            <?= date('d/m/Y', strtotime($data['fecha_fin'])) ?>
                                        </li>
                                        <li class="list-group-item">
                                            <i class="fas fa-dollar-sign"></i> <strong>Costo:</strong> 
                                            $<?= number_format($data['costo'], 2) ?>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Estado de la inscripción -->
                        <div class="col-md-6">
                            <h5><i class="fas fa-user-check"></i> Tu Inscripción</h5>
                            
                            <div class="card mt-3">
                                <div class="card-body">
                                    <div class="alert alert-<?= 
                                        $data['estado'] == 'pago_validado' ? 'success' : 
                                        ($data['estado'] == 'registrado' ? 'warning' : 'secondary') 
                                    ?>">
                                        <h5>Estado: <?= ucfirst(str_replace('_', ' ', $data['estado'])) ?></h5>
                                        <p class="mb-1">
                                            <i class="fas fa-calendar-alt"></i> 
                                            Inscrito el <?= date('d/m/Y', strtotime($data['fecha_inscripcion'])) ?>
                                        </p>
                                        
                                        <?php if ($data['estado'] == 'registrado' && $data['requiere_pago']): ?>
                                            <hr>
                                            <button class="btn btn-primary open-modal" 
                                                    data-inscripcion="<?= $data['id_inscripcion'] ?>" 
                                                    data-curso="<?= htmlspecialchars($data['nombre_curso']) ?>">
                                                <i class="fas fa-upload"></i> Subir comprobante
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($data['estado'] == 'pago_validado' && $data['requiere_pago']): ?>
                                        <div class="alert alert-success mt-3">
                                            <i class="fas fa-check-circle"></i> 
                                            Pago validado correctamente
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para subir comprobante (similar al de index.php) -->
<div id="modalComprobante" class="modal">
  <!-- ... mismo contenido del modal que en index.php ... -->
</div>

<script>
// Mismo script para manejar el modal que en index.php
</script>

<?php include '../../Modulos/footer.php'; ?>