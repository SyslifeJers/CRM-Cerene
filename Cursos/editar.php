<?php
require_once '../DB/Conexion.php';
$database = new Database();

$id_curso = $_GET['id'] ?? null;
if (!$id_curso || !is_numeric($id_curso)) {
    header("Location: index.php?error=curso_no_valido");
    exit();
}

// Obtener datos actuales
$stmt = $database->getConnection()->prepare("SELECT * FROM cursos WHERE id_curso = ?");
$stmt->bind_param("i", $id_curso);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=curso_no_encontrado");
    exit();
}

$curso = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_curso = $_POST['nombre_curso'];
    $descripcion = $_POST['descripcion'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $costo = (float)$_POST['costo'];
    $cupo_maximo = (int)$_POST['cupo_maximo'];
    $requiere_pago = isset($_POST['requiere_pago']) ? 1 : 0;

    try {
        $stmt = $database->getConnection()->prepare("UPDATE cursos SET 
            nombre_curso = ?, 
            descripcion = ?, 
            fecha_inicio = ?, 
            fecha_fin = ?, 
            costo = ?, 
            cupo_maximo = ?, 
            requiere_pago = ?
            WHERE id_curso = ?");

        $stmt->bind_param("ssssdiii",
            $nombre_curso,
            $descripcion,
            $fecha_inicio,
            $fecha_fin,
            $costo,
            $cupo_maximo,
            $requiere_pago,
            $id_curso
        );

        if ($stmt->execute()) {
            $_SESSION['mensaje_exito'] = "Curso actualizado correctamente.";
            header("Location: index.php");
            exit();
        } else {
            $error = "Error al actualizar el curso.";
        }

    } catch (Exception $e) {
        $error = "Error al actualizar el curso: " . $e->getMessage();
    }
}
?>
<?php include '../Modulos/Head.php'; ?>

<div class="col-lg-8">
    <div class="card shadow">
        <div class="card-header bg-warning text-dark">
            <h3 class="mb-0"><i class="fas fa-edit"></i> Editar Curso</h3>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-section">
                    <h4><i class="fas fa-info-circle"></i> Información Básica</h4>
                    <div class="mb-3">
                        <label for="nombre_curso" class="form-label">Nombre del Curso*</label>
                        <input type="text" class="form-control" id="nombre_curso" name="nombre_curso" value="<?= htmlspecialchars($curso['nombre_curso']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($curso['descripcion']) ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-calendar-alt"></i> Fechas</h4>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha_inicio" class="form-label">Fecha de Inicio*</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= $curso['fecha_inicio'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fecha_fin" class="form-label">Fecha de Fin*</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?= $curso['fecha_fin'] ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-money-bill-wave"></i> Costo</h4>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="costo" class="form-label">Costo ($)*</label>
                            <input type="number" step="0.01" class="form-control" id="costo" name="costo" min="0" value="<?= $curso['costo'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="requiere_pago" name="requiere_pago" value="1" <?= $curso['requiere_pago'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="requiere_pago">Requiere pago</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><i class="fas fa-users"></i> Cupo</h4>
                    <div class="mb-3">
                        <label for="cupo_maximo" class="form-label">Número máximo de participantes*</label>
                        <input type="number" class="form-control" id="cupo_maximo" name="cupo_maximo" min="1" value="<?= $curso['cupo_maximo'] ?>" required>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-warning btn-lg">
                        <i class="fas fa-save"></i> Actualizar Curso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../Modulos/Footer.php'; ?>
