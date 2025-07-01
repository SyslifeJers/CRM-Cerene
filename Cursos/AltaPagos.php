<?php
require_once '../DB/Conexion.php';
$database = new Database();
$conn = $database->getConnection();

$id_curso = $_GET['id_curso'] ?? null;
if (!$id_curso || !is_numeric($id_curso)) {
    die("Curso no válido.");
}

// Guardar opción de pago
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_pagos = (int) $_POST['numero_pagos'];
    $id_frecuencia = (int) $_POST['id_frecuencia'];
    $formas_pago = $_POST['formas_pago'] ?? [];

    // Evitar duplicados
    $stmt = $conn->prepare("SELECT COUNT(*) FROM opciones_pago WHERE id_curso = ? AND numero_pagos = ? AND id_frecuencia = ?");
    $stmt->bind_param("iii", $id_curso, $numero_pagos, $id_frecuencia);
    $stmt->execute();
    $stmt->bind_result($existe);
    $stmt->fetch();
    $stmt->close();

    if ($existe == 0) {
        // Insertar opción de pago
        $stmt = $conn->prepare("INSERT INTO opciones_pago (id_curso, numero_pagos, id_frecuencia) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $id_curso, $numero_pagos, $id_frecuencia);
        $stmt->execute();
        $stmt->close();

        // Relacionar formas de pago
        foreach ($formas_pago as $fp) {
            $stmt = $conn->prepare("INSERT IGNORE INTO curso_forma_pago (id_curso, id_forma_pago) VALUES (?, ?)");
            $stmt->bind_param("ii", $id_curso, $fp);
            $stmt->execute();
        }

        $success = "Opción de pago registrada correctamente.";
    } else {
        $error = "Esta opción de pago ya existe para este curso.";
    }
}

// Obtener formas de pago y frecuencias
$formas = $conn->query("SELECT id_forma_pago, nombre FROM formas_pago ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$frecuencias = $conn->query("SELECT id_frecuencia, tipo, dias FROM frecuencia_pago ORDER BY tipo")->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../Modulos/Head.php'; ?>
<div class="container mt-4">
    <h3>Registrar Opciones de Pago para Curso #<?= $id_curso ?></h3>

    <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <form method="POST">
        <div class="mb-3">
            <label>Número de pagos</label>
            <input type="number" name="numero_pagos" class="form-control" min="1" required>
        </div>

        <div class="mb-3">
            <label>Frecuencia</label>
            <select name="id_frecuencia" class="form-select" required>
                <option value="">Selecciona</option>
                <?php foreach ($frecuencias as $f): ?>
                    <option value="<?= $f['id_frecuencia'] ?>"><?= ucfirst($f['tipo']) ?> (cada <?= $f['dias'] ?> días)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Formas de pago</label>
            <?php foreach ($formas as $fp): ?>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="formas_pago[]" value="<?= $fp['id_forma_pago'] ?>" id="fp<?= $fp['id_forma_pago'] ?>">
                    <label class="form-check-label" for="fp<?= $fp['id_forma_pago'] ?>"><?= $fp['nombre'] ?></label>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="btn btn-primary">Registrar opción</button>
        <a href="index.php" class="btn btn-secondary">Regresar</a>
    </form>
</div>
<?php include '../Modulos/Footer.php'; ?>
