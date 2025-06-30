<?php
require_once '../DB/Conexion.php';
include '../Modulos/Head.php';

$database = new Database();
$conn = $database->getConnection();

// Validar si viene ID
if (!isset($_GET['id'])) {
    die("ID de curso no especificado.");
}

$id_curso = (int) $_GET['id'];

// Obtener datos actuales
$stmt = $conn->prepare("SELECT * FROM cursos WHERE id_curso = ?");
$stmt->bind_param("i", $id_curso);
$stmt->execute();
$result = $stmt->get_result();
$curso = $result->fetch_assoc();

if (!$curso) {
    die("Curso no encontrado.");
}

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_curso = $_POST['nombre_curso'];
    $descripcion = $_POST['descripcion'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $costo = (float)$_POST['costo'];
    $cupo_maximo = (int)$_POST['cupo_maximo'];
    $requiere_pago = isset($_POST['requiere_pago']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE cursos SET nombre_curso = ?, descripcion = ?, fecha_inicio = ?, fecha_fin = ?, costo = ?, cupo_maximo = ?, requiere_pago = ? WHERE id_curso = ?");
    $stmt->bind_param("ssssdiii", $nombre_curso, $descripcion, $fecha_inicio, $fecha_fin, $costo, $cupo_maximo, $requiere_pago, $id_curso);

    if ($stmt->execute()) {
        $_SESSION['mensaje_exito'] = "Curso actualizado correctamente.";
        header("Location: index.php");
        exit();
    } else {
        $error = "Error al actualizar: " . $conn->error;
    }
}
?>

<div class="container mt-5">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow">
            <div class="card-header bg-warning text-white">
                <h3><i class="fas fa-edit"></i> Editar Curso</h3>
            </div>
            <div class="card-body">
                <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label>Nombre del Curso*</label>
                        <input type="text" name="nombre_curso" class="form-control" value="<?= htmlspecialchars($curso['nombre_curso']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($curso['descripcion']) ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Fecha de Inicio*</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="<?= $curso['fecha_inicio'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Fecha de Fin*</label>
                            <input type="date" name="fecha_fin" class="form-control" value="<?= $curso['fecha_fin'] ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Costo ($)*</label>
                            <input type="number" step="0.01" name="costo" class="form-control" value="<?= $curso['costo'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="requiere_pago" class="form-check-input" id="requiere_pago" <?= $curso['requiere_pago'] ? 'checked' : '' ?>>
                                <label for="requiere_pago" class="form-check-label">Requiere pago</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Cupo Máximo*</label>
                        <input type="number" name="cupo_maximo" class="form-control" min="1" value="<?= $curso['cupo_maximo'] ?>" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../Modulos/Footer.php'; ?>
