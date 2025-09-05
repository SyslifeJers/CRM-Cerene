<?php
require_once '../DB/Conexion.php';
$database = new Database();
$conn = $database->getConnection();

$inicio = $_GET['inicio'] ?? '';
$fin = $_GET['fin'] ?? '';
$curso = $_GET['curso'] ?? '';
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
        SELECT SUM(monto_pagado) AS total FROM (
            SELECT i.id_inscripcion, i.monto_pagado
            FROM inscripciones i
            JOIN participantes p ON i.id_participante = p.id_participante
            WHERE DATE(i.fecha_inscripcion) BETWEEN ? AND ?
              AND i.estado IN ('pago_validado','pagos programados','Revision de pago')
              AND p.email IS NOT NULL AND p.email <> ''
              " . ($curso !== '' ? " AND i.id_curso = ?" : "") . "
              AND NOT EXISTS (
                    SELECT 1 FROM comprobantes_inscripcion ci
                    WHERE ci.id_inscripcion = i.id_inscripcion AND ci.validado = 1
              )
            UNION ALL
            SELECT i.id_inscripcion, SUM(ci.monto_pagado) AS monto_pagado
            FROM inscripciones i
            JOIN comprobantes_inscripcion ci ON ci.id_inscripcion = i.id_inscripcion
            JOIN participantes p ON i.id_participante = p.id_participante
            WHERE ci.validado = 1
              AND DATE(ci.fecha_carga) BETWEEN ? AND ?
              AND i.estado IN ('pago_validado','pagos programados','Revision de pago')
              AND p.email IS NOT NULL AND p.email <> ''
              " . ($curso !== '' ? " AND i.id_curso = ?" : "") . "
            GROUP BY i.id_inscripcion
        ) t";

    $stmt = $conn->prepare($query);
    if ($curso !== '') {
        $cursoInt = (int) $curso;
        $stmt->bind_param('ssissi', $inicio, $fin, $cursoInt, $inicio, $fin, $cursoInt);
    } else {
        $stmt->bind_param('ssss', $inicio, $fin, $inicio, $fin);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $total = $row['total'] ?? 0;
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
    <?php if ($total !== null): ?>
        <div class="alert alert-info">
            Total recaudado: $<?= number_format($total, 2) ?>
        </div>
        <a class="btn btn-success" href="exportar_excel.php?inicio=<?= urlencode($inicio) ?>&fin=<?= urlencode($fin) ?>&curso=<?= urlencode($curso) ?>">Exportar a Excel</a>
    <?php endif; ?>
</div>

<?php $database->closeConnection(); ?>
<?php include '../Modulos/Footer.php'; ?>
