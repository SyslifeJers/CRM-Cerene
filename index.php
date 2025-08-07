<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cursos Clínica Cerene</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .logo {
            max-width: 400px;
            height: auto;
        }
        .hero {
            padding: 60px 20px;
            text-align: center;
        }
        .btn-custom {
            width: 200px;
            margin: 10px;
        }
    </style>
</head>
<body>
    <div class="container hero">
        <img src="/assets/logoCerene.png" alt="Clínica Cerene" class="logo mb-4">
        <h1 class="mb-3">¡Bienvenido al servicio de cursos de Instituto del Neurodesarrollo Cerene A.C.!</h1>
        <p class="lead mb-4">
            En este portal podrá registrarse y acceder a los cursos ofrecidos por nuestra clínica,
            orientados al desarrollo y formación en el área de la salud mental y terapias especializadas.
        </p>
        <div>
            <a href="https://clinicacerene.com" target="_blank" class="btn btn-primary btn-custom">
                Ir a clinicacerene.com
            </a>
            <a href="participantePanel/login.php" class="btn btn-success btn-custom">
                Ingresar al portal
            </a>
        </div>
    </div>

    <script src="../assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
</body>
</html>
