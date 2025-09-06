<?php
session_start();
require_once '../DB/Conexion.php';
require_once '../config/env_loader.php';
$database = new Database();

function enviarCorreoBienvenida($destino, $nombreCompleto) {
    cargarEnv();
    $apiKey = getenv('MAILGUN_API_KEY');
    $domain = getenv('MAILGUN_DOMAIN');
    $from   = getenv('MAILGUN_FROM');

    if (!$apiKey || !$domain || !$from) {
        return;
    }

    $asunto = 'Bienvenido a Clínica Cerene';
    $html = "<p>Hola {$nombreCompleto},</p>".
            "<p>Gracias por registrarte en nuestra plataforma.</p>".
            "<p>Puedes acceder a la plataforma desde <a href='https://cursos.clinicacerene.com/index.php'>este enlace</a>.</p>";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api.mailgun.net/v3/{$domain}/messages",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_USERPWD => $apiKey,
        CURLOPT_POSTFIELDS => [
            'from'    => $from,
            'to'      => $destino,
            'subject' => $asunto,
            'html'    => $html
        ]
    ]);
    curl_exec($ch);
    curl_close($ch);
}

$registro_exitoso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = htmlspecialchars($_POST['nombre']);
    $apellido = htmlspecialchars($_POST['apellido']);
    $cedula   = htmlspecialchars($_POST['cedula']);
    $email    = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $telefono = htmlspecialchars($_POST['telefono']);
    $titulo   = $_POST['titulo'];
    if ($titulo === 'Otro') {
        $titulo = trim($_POST['otro_titulo'] ?? '');
    }

    $pass          = $_POST['pass'] ?? '';
    $pass_confirm  = $_POST['confirmar_pass'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Error: Formato de correo electrónico inválido";
    } elseif (!preg_match('/@gmail\.com$/i', $email)) {
        $error = "Error: Solo se permiten correos de Gmail";
    } elseif (strlen($pass) < 6) {
        $error = "Error: La contraseña debe tener al menos 6 caracteres";
    } elseif ($pass !== $pass_confirm) {
        $error = "Error: Las contraseñas no coinciden";
    } else {
        try {
            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);

            $sql = "INSERT INTO participantes (nombre, apellido, cedula, email, telefono, pass, titulo) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $database->getConnection()->prepare($sql);
            $stmt->bind_param("sssssss", $nombre, $apellido, $cedula, $email, $telefono, $pass_hash, $titulo);

            if ($stmt->execute()) {
                $id_participante = $stmt->insert_id;
                $stmt->close();

                if ($id_participante <= 0) {
                    throw new Exception("Error: No se generó ID de participante");
                }

                $_SESSION['participante_id'] = $id_participante;
                $_SESSION['email'] = $email;
                $_SESSION['nombre'] = $nombre . ' ' . $apellido;
                $registro_exitoso = true;
                enviarCorreoBienvenida($email, $nombre . ' ' . $apellido);
            } else {
                throw new Exception("Error al registrar participante: " . $stmt->error);
            }
        } catch (Exception $e) {
            $error = "Error al registrar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="icon" href="/assets/img/kaiadmin/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="/assets/css/plugins.min.css" />
    <link rel="stylesheet" href="/assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="/assets/css/demo.css" />
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if ($registro_exitoso): ?>
                <div class="card">
                    <div class="card-header bg-success text-white text-center">
                        <h4>¡Registro exitoso!</h4>
                    </div>
                    <div class="card-body text-center">
                        <p class="mb-3">Serás redirigido al panel en 10 segundos</p>
                        <a href="index.php" class="btn btn-primary">Ir al panel</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <h4>Registro</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título*</label>
                                <select class="form-select" id="titulo" name="titulo" required onchange="mostrarInputOtro(this)">
                                    <option value="" selected disabled>Selecciona una opción</option>
                                    <option value="Lic.">Lic.</option>
                                    <option value="Mtra.">Mtra.</option>
                                    <option value="Mtro.">Mtro.</option>
                                    <option value="Dra.">Dra.</option>
                                    <option value="Dr.">Dr.</option>
                                    <option value="Psic.">Psic.</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombre" class="form-label">Nombre*</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="apellido" class="form-label">Apellido*</label>
                                    <input type="text" class="form-control" id="apellido" name="apellido" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico (solo Gmail)*</label>
                                <input type="email" class="form-control" id="email" name="email" required pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$" title="Solo se permiten correos de Gmail">
                                <div class="invalid-feedback">
                                    Solo se permiten correos de Gmail(Para compartir contenido de los cursos).
                                </div>
                            </div>
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var emailInput = document.getElementById('email');
                                emailInput.addEventListener('input', function() {
                                    if (emailInput.validity.patternMismatch) {
                                        emailInput.classList.add('is-invalid');
                                    } else {
                                        emailInput.classList.remove('is-invalid');
                                    }
                                });
                            });
                            </script>
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono*</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" required>
                            </div>
                            <div class="mb-3">
                                <label for="cedula" class="form-label">Cédula profesional</label>
                                <input type="text" class="form-control" id="cedula" name="cedula" >
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="pass" class="form-label">Contraseña*</label>
                                    <input type="password" class="form-control" id="pass" name="pass" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirmar_pass" class="form-label">Confirmar Contraseña*</label>
                                    <input type="password" class="form-control" id="confirmar_pass" name="confirmar_pass" required>
                                </div>
                            </div>
                            <div class="mb-3 d-none" id="otroTituloDiv">
                                <label for="otro_titulo" class="form-label">Otro*</label>
                                <input type="text" class="form-control" id="otro_titulo" name="otro_titulo">
                            </div>
                            <button type="submit" class="btn btn-primary">Registrarme</button>
                            <a href="login.php" class="btn btn-secondary ms-2">Iniciar Sesión</a>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="/assets/js/core/jquery-3.7.1.min.js"></script>
<script src="/assets/js/core/popper.min.js"></script>
<script src="/assets/js/core/bootstrap.min.js"></script>
<script>
function mostrarInputOtro(select) {
    const otroDiv = document.getElementById('otroTituloDiv');
    const otroInput = document.getElementById('otro_titulo');
    if (select.value === 'Otro') {
        otroDiv.classList.remove('d-none');
    } else {
        otroDiv.classList.add('d-none');
        otroInput.removeAttribute('required');
        otroInput.value = '';
    }
}
</script>
</body>
</html>
