<?php
require_once '../DB/Conexion.php';
$database = new Database();
$conn = $database->getConnection();

// Listar opciones ya registradas (solo las activas)
$sql_listado = "SELECT op.id_opcion, op.numero_pagos, f.tipo AS nombre_frecuencia, op.activo, op.costo_adicional, op.nota
                FROM opciones_pago op 
                JOIN frecuencia_pago f ON f.id_frecuencia = op.id_frecuencia";
$stmt = $conn->prepare($sql_listado);
$stmt->execute();
$resultado = $stmt->get_result();
$opciones_existentes = $resultado->fetch_all(MYSQLI_ASSOC);

// Alternar estado de activo (activar/desactivar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && isset($_POST['id_opcion_toggle'])) {
    $id_opcion_toggle = intval($_POST['id_opcion_toggle']);
    $accion = $_POST['accion'];

    $nuevo_estado = ($accion === 'desactivar') ? 0 : 1;

    $update = $conn->prepare("UPDATE opciones_pago SET activo = ? WHERE id_opcion = ?");
    $update->bind_param("ii", $nuevo_estado, $id_opcion_toggle);
    $update->execute();
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=" . ($nuevo_estado === 1 ? 'activado' : 'desactivado'));
    exit();
}
// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_pagos = intval($_POST['numero_pagos'] ?? 0);
    $id_frecuencia = intval($_POST['id_frecuencia'] ?? 0);
    $costo_adicional = floatval($_POST['costo_adicional'] ?? 0);
    $nota = $_POST['nota'] ?? '';

    // Verificar duplicado
    $check = $conn->prepare("SELECT COUNT(*) FROM opciones_pago WHERE numero_pagos = ? AND id_frecuencia = ?");
    $check->bind_param("ii", $numero_pagos, $id_frecuencia);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();

    if ($count > 0) {
        $mensaje = "Esta combinación ya existe.";
    } else {
        // Insertar nueva opción
        $insert = $conn->prepare("INSERT INTO opciones_pago (numero_pagos, id_frecuencia, activo, costo_adicional, nota) VALUES (?, ?, 1, ?, ?)");
        $insert->bind_param("iids", $numero_pagos, $id_frecuencia, $costo_adicional, $nota);
        if ($insert->execute()) {

            header("Location: Index.php?success=1");
            exit();
        } else {
            $mensaje = "Error al guardar la opción.";
        }
    }
}

// Obtener frecuencias disponibles
$frecuencias = $conn->query("SELECT * FROM frecuencia_pago")->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../Modulos/Head.php'; ?>

<div class="container mt-4">
    <h2>Opciones de Pago</h2>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'activado'): ?>
        <div class="alert alert-success">Opción activada correctamente</div>
    <?php elseif (isset($_GET['success']) && $_GET['success'] === 'desactivado'): ?>
        <div class="alert alert-warning">Opción desactivada correctamente</div>
    <?php elseif (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="alert alert-success">Opción registrada correctamente</div>
    <?php endif; ?>
    <div class="card mb-4">
        <div class="card-header bg-info text-white">Opciones Existentes</div>
        <div class="card-body">
            <?php if (empty($opciones_existentes)): ?>
                <p>No hay opciones registradas aún.</p>
            <?php else: ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>N° Pagos</th>
                            <th>Frecuencia</th>
                            <th>Acción</th>
                            <th>Costo Adicional</th>
                            <th>Nota</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($opciones_existentes as $op): ?>
                            <tr>
                                <td><?= $op['numero_pagos'] ?></td>
                                <td><?= $op['nombre_frecuencia'] ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id_opcion_toggle" value="<?= $op['id_opcion'] ?>">
                                        <input type="hidden" name="accion" value="<?= $op['activo'] ? 'desactivar' : 'activar' ?>">
                                        <button type="submit" class="btn btn-sm <?= $op['activo'] ? 'btn-danger' : 'btn-success' ?>"
                                            onclick="return confirm('¿Seguro que deseas <?= $op['activo'] ? 'desactivar' : 'activar' ?> esta opción?')">
                                            <?= $op['activo'] ? 'Desactivar' : 'Activar' ?>
                                        </button>
                                        
                                    </form>
                                </td>
                                <td><?= number_format($op['costo_adicional'], 2) ?> $</td>
                                <td><?= htmlspecialchars($op['nota']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">Registrar Nueva Opción de Pago</div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="numero_pagos" class="form-label">Número de Pagos*</label>
                        <input type="number" class="form-control" name="numero_pagos" min="1" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="id_frecuencia" class="form-label">Frecuencia*</label>
                        <select class="form-control" name="id_frecuencia" required>
                            <option value="">Selecciona</option>
                            <?php foreach ($frecuencias as $f): ?>
                                <option value="<?= $f['id_frecuencia'] ?>"><?= $f['tipo'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="costo_adicional" class="form-label">Costo adicional*</label>
                        <input type="number" class="form-control" name="costo_adicional" min="0" required>
                    </div>
                                        <div class="col-md-3 mb-3">
                        <label for="nota" class="form-label">Nota</label>
                        <input type="text" class="form-control" name="nota">
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Guardar Opción</button>
                <a href="index.php" class="btn btn-secondary">Volver</a>
            </form>
        </div>
    </div>
</div>

<?php include '../Modulos/Footer.php'; ?>