<?php
include '../Modulos/Head.php';
require_once '../DB/Conexion.php';

$database = new Database();
$conn = $database->getConnection();

// Consultas de resumen
$cont_usuarios = $conn->query("SELECT COUNT(*) AS total FROM Usuarios")->fetch_assoc()['total'];
$cont_participantes = $conn->query("SELECT COUNT(*) AS total FROM participantes")->fetch_assoc()['total'];
$cont_cursos = $conn->query("SELECT COUNT(*) AS total FROM cursos")->fetch_assoc()['total'];
$cont_inscripciones = $conn->query("SELECT COUNT(*) AS total FROM inscripciones")->fetch_assoc()['total'];

$query = "SELECT DATE_FORMAT(fecha_registro, '%Y-%m') AS mes, COUNT(*) AS total 
          FROM participantes 
          GROUP BY mes 
          ORDER BY mes ASC";
$resultado = $conn->query($query);
$meses = [];
$totales = [];

while ($row = $resultado->fetch_assoc()) {
  $meses[] = $row['mes'];
  $totales[] = $row['total'];
}

$queryEstados = "SELECT estado, COUNT(*) AS cantidad FROM inscripciones GROUP BY estado";
$resEstados = $conn->query($queryEstados);
$estados = [];
$cantidades = [];

while ($row = $resEstados->fetch_assoc()) {
  $estados[] = $row['estado'];
  $cantidades[] = $row['cantidad'];
}
?>

<div class="container py-4">
  <div class="row g-4">

    <!-- Usuarios -->
    <div class="col-md-3">
      <div class="card text-white bg-primary shadow">
        <div class="card-body">
          <h5 class="card-title"><i class="fas fa-users-cog"></i> Usuarios</h5>
          <p class="card-text fs-4"><?= $cont_usuarios ?></p>
        </div>
        <div class="card-footer bg-transparent border-top-0">
          <a href="../Usuarios/index.php" class="text-white">Ver usuarios <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
    </div>

    <!-- Participantes -->
    <div class="col-md-3">
      <div class="card text-white bg-success shadow">
        <div class="card-body">
          <h5 class="card-title"><i class="fas fa-user-graduate"></i> Participantes</h5>
          <p class="card-text fs-4"><?= $cont_participantes ?></p>
        </div>
        <div class="card-footer bg-transparent border-top-0">
          <a href="../Participantes/index.php" class="text-white">Ver participantes <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
    </div>

    <!-- Cursos -->
    <div class="col-md-3">
      <div class="card text-white bg-info shadow">
        <div class="card-body">
          <h5 class="card-title"><i class="fas fa-book"></i> Cursos</h5>
          <p class="card-text fs-4"><?= $cont_cursos ?></p>
        </div>
        <div class="card-footer bg-transparent border-top-0">
          <a href="../Cursos/index.php" class="text-white">Ver cursos <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
    </div>

    <!-- Inscripciones -->
    <div class="col-md-3">
      <div class="card text-white bg-warning shadow">
        <div class="card-body">
          <h5 class="card-title"><i class="fas fa-clipboard-list"></i> Inscripciones</h5>
          <p class="card-text fs-4"><?= $cont_inscripciones ?></p>
        </div>
        <div class="card-footer bg-transparent border-top-0">
          <a href="../Inscripciones/index.php" class="text-white">Ver inscripciones <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
    </div>

  </div>

  <!-- Bienvenida o información -->
  <div class="row mt-5">
    <div class="col-md-12">
      <div class="card shadow">
        <div class="card-header bg-secondary text-white">
          <h5 class="mb-0"><i class="fas fa-home"></i> Bienvenido al Panel Administrativo</h5>
        </div>
        <div class="card-body">
          <p>Desde aquí podrás gestionar:</p>
          <ul>
            <li>Usuarios y roles administrativos.</li>
            <li>Registro y control de participantes.</li>
            <li>Creación y seguimiento de cursos.</li>
            <li>Visualización de inscripciones y desempeño.</li>
            <li>Revisión de documentos y contenido multimedia.</li>
          </ul>
          <p class="mt-3"><strong>Sitio oficial:</strong> <a href="https://clinicacerene.com" target="_blank">clinicacerene.com</a></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Bienvenida o información -->
  <div class="row mt-5">
    <div class="col-md-12">
      <div class="card shadow">
        <div class="card-header bg-secondary text-white">
          <h5 class="mb-0"><i class="fas fa-home"></i> Bienvenido al Panel Administrativo</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <canvas id="graficoParticipantes"></canvas>
            </div>
            <div class="col-md-6">
              <canvas id="graficoEstados"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../Modulos/Footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx1 = document.getElementById('graficoParticipantes').getContext('2d');
    const graficoParticipantes = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: <?= json_encode($meses) ?>,
            datasets: [{
                label: 'Participantes Registrados',
                data: <?= json_encode($totales) ?>,
                borderWidth: 2,
                fill: false,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    const ctx2 = document.getElementById('graficoEstados').getContext('2d');
    const graficoEstados = new Chart(ctx2, {
        type: 'pie',
        data: {
            labels: <?= json_encode($estados) ?>,
            datasets: [{
                data: <?= json_encode($cantidades) ?>,
                backgroundColor: ['#007bff', '#28a745']
            }]
        },
        options: {
            responsive: true
        }
    });
</script>
