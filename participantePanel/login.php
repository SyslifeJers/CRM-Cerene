<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../DB/Conexion.php';
    $email = $_POST['email'];
    $pass = $_POST['pass'];
    $database = new Database();

    $stmt = $database->getConnection()->prepare("SELECT id_participante, nombre, apellido, pass FROM participantes WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $participante = $result->fetch_assoc();
        if (password_verify($pass, $participante['pass'])) {
            $_SESSION['participante_id'] = $participante['id_participante'];
            $_SESSION['email'] = $email;
            $_SESSION['nombre'] = $participante['nombre'] . ' ' . $participante['apellido'];
            $clave_curso = $_GET['clave'] ?? null;

            if ($clave_curso) {
            header("Location: index.php?clave=" . urlencode($clave_curso));
            }
            else {
                header("Location: index.php");
            }

            exit();
        } else {
            $error = "Contraseña incorrecta";
        }
    } else {
        $error = "Usuario no encontrado";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Iniciar Sesión</title>
        <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link

        rel="icon"

        href="/assets/img/kaiadmin/favicon.ico"

        type="image/x-icon" />



    <!-- Fonts and icons -->

    <script src="/assets/js/plugin/webfont/webfont.min.js"></script>

    <script>
        WebFont.load({

            google: {
                families: ["Public Sans:300,400,500,600,700"]
            },

            custom: {

                families: [

                    "Font Awesome 5 Solid",

                    "Font Awesome 5 Regular",

                    "Font Awesome 5 Brands",

                    "simple-line-icons",

                ],

                urls: ["/assets/css/fonts.min.css"],

            },

            active: function() {

                sessionStorage.fonts = true;

            },

        });
    </script>



    <!-- CSS Files -->

    <link rel="stylesheet" href="/assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="/assets/css/plugins.min.css" />
    <link rel="stylesheet" href="/assets/css/kaiadmin.min.css" />



    <!-- CSS Just for demo purpose, don't include it in your project -->

    <link rel="stylesheet" href="/assets/css/demo.css" />
    <link href="https://cdn.datatables.net/v/dt/dt-2.0.8/datatables.min.css" rel="stylesheet">

</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Iniciar Sesión</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="form-group">
                                <label>Correo Electrónico</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label>Contraseña</label>
                                <input type="password" name="pass" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
                        </form>

                        <div class="mt-3">
                            <p>¿No tienes cuenta? <a href="Registro.php">Regístrate aquí</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--   Core JS Files   -->
    <script src="/assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="/assets/js/core/popper.min.js"></script>
    <script src="/assets/js/core/bootstrap.min.js"></script>
    <!-- jQuery Scrollbar -->
    <script src="/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <!-- Chart JS -->
    <script src="/assets/js/plugin/chart.js/chart.min.js"></script>
    <!-- jQuery Sparkline -->
    <script src="/assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>
    <!-- Chart Circle -->
    <script src="/assets/js/plugin/chart-circle/circles.min.js"></script>
    <!-- Datatables -->
    <script src="/assets/js/plugin/datatables/datatables.min.js"></script>
    <!-- Bootstrap Notify -->
    <script src="/assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
    <!-- jQuery Vector Maps -->
    <script src="/assets/js/plugin/jsvectormap/jsvectormap.min.js"></script>
    <script src="/assets/js/plugin/jsvectormap/world.js"></script>
    <!-- Sweet Alert -->
    <script src="/assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    <!-- Kaiadmin JS -->
    <script src="/assets/js/kaiadmin.min.js"></script>
</body>

</html>