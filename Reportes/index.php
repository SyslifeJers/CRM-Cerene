<?php
require_once '../DB/Conexion.php';
$database = new Database();
$conn = $database->getConnection();

$inicio = $_GET['inicio'] ?? '';
$fin = $_GET['fin'] ?? '';
$curso = $_GET['curso'] ?? '';
$pagos = [];
$total = null;

// Obtener lista de cursos para el filtro
$cursos = [];
$resultCursos = $conn->query("SELECT id_curso, nombre_curso FROM cursos");
if ($resultCursos && $resultCursos->num_rows > 0) {
    while ($row = $resultCursos->fetch_assoc()) {
        $cursos[] = $row;
    }
}

if ($inicio && $fin) {
    $query = "
        SELECT c.nombre_curso, CONCAT(p.nombre, ' ', p.apellido) AS participante,
               ci.numero_pago, ci.metodo_pago, ci.monto_pagado, ci.fecha_carga, ci.comprobante_path
        FROM comprobantes_inscripcion ci
        JOIN inscripciones i ON ci.id_inscripcion = i.id_inscripcion
        JOIN cursos c ON i.id_curso = c.id_curso
        JOIN participantes p ON i.id_participante = p.id_participante
        WHERE ci.validado = 1
          AND DATE(ci.fecha_carga) BETWEEN ? AND ?
          AND p.email IS NOT NULL AND p.email <> ''
          " . ($curso !== '' ? " AND i.id_curso = ?" : "") . "
        UNION ALL
        SELECT c.nombre_curso, CONCAT(p.nombre, ' ', p.apellido) AS participante,
               1 AS numero_pago, i.metodo_pago, i.monto_pagado, i.fecha_inscripcion AS fecha_carga, i.comprobante_path
        FROM inscripciones i
        JOIN cursos c ON i.id_curso = c.id_curso
        JOIN participantes p ON i.id_participante = p.id_participante
        WHERE DATE(i.fecha_inscripcion) BETWEEN ? AND ?
          AND i.estado IN ('pago_validado','pagos programados','Revision de pago')
          AND p.email IS NOT NULL AND p.email <> ''
          AND NOT EXISTS (
                SELECT 1 FROM comprobantes_inscripcion ci2
                WHERE ci2.id_inscripcion = i.id_inscripcion AND ci2.validado = 1
          )
          " . ($curso !== '' ? " AND i.id_curso = ?" : "") . "
        ORDER BY fecha_carga";

    $stmt = $conn->prepare($query);
    if ($curso !== '') {
        $cursoInt = (int) $curso;
        $stmt->bind_param('ssissi', $inicio, $fin, $cursoInt, $inicio, $fin, $cursoInt);
    } else {
        $stmt->bind_param('ssss', $inicio, $fin, $inicio, $fin);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $pagos = $result->fetch_all(MYSQLI_ASSOC);
    $total = array_sum(array_column($pagos, 'monto_pagado'));
}
?>
<?php include '../Modulos/Head.php'; ?>

<div class="container mt-4">
    <h2>Reporte de Pagos</h2>
    <form method="get" class="row g-3 mb-4">
        <div class="col-md-3">
            <label for="inicio" class="form-label">Fecha inicio</label>
            <input type="date" id="inicio" name="inicio" class="form-control" value="<?= htmlspecialchars($inicio) ?>">
        </div>
        <div class="col-md-3">
            <label for="fin" class="form-label">Fecha fin</label>
            <input type="date" id="fin" name="fin" class="form-control" value="<?= htmlspecialchars($fin) ?>">
        </div>
        <div class="col-md-3">
            <label for="curso" class="form-label">Curso</label>
            <select id="curso" name="curso" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($cursos as $c): ?>
                    <option value="<?= $c['id_curso']; ?>" <?= $curso == $c['id_curso'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nombre_curso']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 align-self-end">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>
    <?php if ($inicio && $fin): ?>
        <?php if ($pagos): ?>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Participante</th>
                            <th>Método de Pago</th>
                            <th>Monto Pagado</th>
                            <th>Fecha</th>
                            <th>Comprobante</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagos as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['nombre_curso']); ?></td>
                            <td><?= htmlspecialchars($p['participante']); ?></td>
                            <td><?= htmlspecialchars($p['metodo_pago']); ?></td>
                            <td>$<?= number_format($p['monto_pagado'], 2); ?></td>
                            <td><?= htmlspecialchars($p['fecha_carga']); ?></td>
                            <td>
                                <?php if ($p['comprobante_path']): ?>
                                    <a href="https://cursos.clinicacerene.com/comprobantes/<?= htmlspecialchars($p['comprobante_path']); ?>" target="_blank">Ver</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="alert alert-info">
                Total recaudado: $<?= number_format($total, 2); ?>
            </div>
            <a class="btn btn-success" href="exportar_excel.php?inicio=<?= urlencode($inicio) ?>&fin=<?= urlencode($fin) ?>&curso=<?= urlencode($curso) ?>">Exportar a Excel</a>
        <?php else: ?>
            <p>No se encontraron pagos para el rango seleccionado.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php $database->closeConnection(); ?>
<?php include '../Modulos/Footer.php'; ?>
