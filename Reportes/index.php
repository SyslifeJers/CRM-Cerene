<?php
require_once '../DB/Conexion.php';
$database = new Database();
$conn = $database->getConnection();

$inicio = $_GET['inicio'] ?? '';
$fin = $_GET['fin'] ?? '';
$total = null;

if ($inicio && $fin) {
    $stmt = $conn->prepare("SELECT SUM(monto_pagado) AS total FROM comprobantes_inscripcion WHERE validado = 1 AND DATE(fecha_carga) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $inicio, $fin);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $total = $row['total'] ?? 0;
}
?>
<?php include '../Modulos/Head.php'; ?>

<div class="container mt-4">
    <h2>Reporte de Pagos</h2>
    <form method="get" class="row g-3 mb-4">
        <div class="col-md-4">
            <label for="inicio" class="form-label">Fecha inicio</label>
            <input type="date" id="inicio" name="inicio" class="form-control" value="<?= htmlspecialchars($inicio) ?>">
        </div>
        <div class="col-md-4">
            <label for="fin" class="form-label">Fecha fin</label>
            <input type="date" id="fin" name="fin" class="form-control" value="<?= htmlspecialchars($fin) ?>">
        </div>
        <div class="col-md-4 align-self-end">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>
    <?php if ($total !== null): ?>
        <div class="alert alert-info">
            Total recaudado: $<?= number_format($total, 2) ?>
        </div>
        <a class="btn btn-success" href="exportar_excel.php?inicio=<?= urlencode($inicio) ?>&fin=<?= urlencode($fin) ?>">Exportar a Excel</a>
    <?php endif; ?>
</div>

<?php $database->closeConnection(); ?>
<?php include '../Modulos/Footer.php'; ?>
