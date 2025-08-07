<?php
session_start();
require_once 'DB/Conexion.php';
require_once 'config/env_loader.php';
$database = new Database();

function enviarCorreoPass($destino, $nombreCompleto, $password) {
    cargarEnv();
    $apiKey = getenv('MAILGUN_API_KEY');
    $domain = getenv('MAILGUN_DOMAIN');
    $from   = getenv('MAILGUN_FROM');

    if (!$apiKey || !$domain || !$from) {
        return;
    }

    $asunto = 'Bienvenido a Clínica Cerene';
    $html = "<p>Hola {$nombreCompleto},</p>".
            "<p>Gracias por tu suscripción. A continuación encontrarás tu contraseña temporal:</p>".
            "<p style='font-size:18px'><strong>{$password}</strong></p>".
            "<p>Puedes acceder a la plataforma desde <a href='/index.php'>este enlace</a>.</p>".
            "<p>Te recomendamos cambiar la contraseña una vez inicies sesión.</p>";

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

// La clave puede venir con la opción de pago: CLAVE-IdOpcion
$clave_param = $_GET['clave'] ?? null;
$opcion_pago_id = null;
$clave_curso = null;
$opcion_pago_info = null;
$curso = null;
$registro_exitoso = false;

if ($clave_param) {
    $partes = explode('-', $clave_param);
    $clave_curso = $partes[0] ?? null;
    if (isset($partes[1]) && ctype_digit($partes[1])) {
        $opcion_pago_id = (int)$partes[1];
    }
}

// Verificar que la clave sea válida
if ($clave_curso) {
    $stmt = $database->getConnection()->prepare("SELECT * FROM cursos WHERE clave_curso = ? AND activo = 1");
    $stmt->bind_param("s", $clave_curso);
    $stmt->execute();
    $result = $stmt->get_result();
    $curso = $result->fetch_assoc();
    $stmt->close();
    // Si existe opción de pago válida, obtener información
    if ($curso && $opcion_pago_id) {
        $opStmt = $database->getConnection()->prepare(
            "SELECT op.numero_pagos, f.tipo AS frecuencia, op.costo_adicional, op.nota FROM opciones_pago op JOIN frecuencia_pago f ON f.id_frecuencia = op.id_frecuencia WHERE op.id_opcion = ? AND op.activo = 1"
        );
        $opStmt->bind_param("i", $opcion_pago_id);
        $opStmt->execute();
        $resOp = $opStmt->get_result();
        $opcion_pago_info = $resOp->fetch_assoc();
        $opStmt->close();
        if (!$opcion_pago_info) {
            $opcion_pago_id = null; // Si no se encontró opción válida
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $curso) {
    $nombre = htmlspecialchars($_POST['nombre']);
    $apellido = htmlspecialchars($_POST['apellido']);
    $cedula = htmlspecialchars($_POST['cedula']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $telefono = htmlspecialchars($_POST['telefono']);
    $titulo = $_POST['titulo'];
    if ($titulo === 'Otro') {
        $titulo = trim($_POST['otro_titulo'] ?? '');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Error: Formato de correo electrónico inválido";
    } else {
        try {
        // Generar contraseña de 6 dígitos
$pass_plain = sprintf("%06d", mt_rand(0, 999999));
$pass_hash = password_hash($pass_plain, PASSWORD_DEFAULT);
        $_SESSION['pass_temporal'] = $pass_plain;
        
        // Registrar participante
       $sql_participante = "INSERT INTO participantes (nombre, apellido, cedula, email, telefono, pass, titulo) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_participante = $database->getConnection()->prepare($sql_participante);
        $stmt_participante->bind_param("sssssss", $nombre, $apellido, $cedula, $email, $telefono, $pass_hash, $titulo);

        if ($stmt_participante->execute()) {
            $id_participante = $stmt_participante->insert_id;
            $stmt_participante->close();
            
            if ($id_participante <= 0) {
                throw new Exception("Error: No se generó ID de participante");
            }
            $estado = $curso['requiere_pago'] ? 'registrado' : 'pago_validado';
            // Insertar inscripción con posible opción de pago
            $sql_inscripcion = "INSERT INTO inscripciones
                            (id_curso, id_participante, estado, fecha_inscripcion, IdOpcionPago)
                            VALUES (?, ?, ?, NOW(), ?)";

            $stmt_inscripcion = $database->getConnection()->prepare($sql_inscripcion);
            $stmt_inscripcion->bind_param("iisi", $curso['id_curso'], $id_participante, $estado, $opcion_pago_id);

            if (!$stmt_inscripcion->execute()) {
                throw new Exception("Error al inscribir: " . $stmt_inscripcion->error);
            }
            $_SESSION['participante_id'] = $id_participante;
            $_SESSION['email'] = $email;
            $_SESSION['nombre'] = $nombre . ' ' . $apellido;
            
            $stmt_inscripcion->close();
            $registro_exitoso = true;
            enviarCorreoPass($email, $nombre . ' ' . $apellido, $pass_plain);
        } else {
            throw new Exception("Error al registrar participante: " . $stmt_participante->error);
        }


        // Aquí podrías agregar lógica para enviar correo de confirmación
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
    <title>Registro al Curso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .course-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 2rem;
            border-radius: 8px 8px 0 0;
            margin-bottom: 2rem;
        }
        .registration-form {
            padding: 2rem;
            border: 1px solid #dee2e6;
            border-radius: 0 0 8px 8px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if (!$curso): ?>
                    <div class="alert alert-danger text-center">
                        <h4><i class="fas fa-exclamation-triangle"></i> Enlace no válido</h4>
                        <p>El enlace de registro es incorrecto o el curso ya no está disponible.</p>
                        <a href="/" class="btn btn-primary">Volver al inicio</a>
                    </div>
                <?php elseif ($registro_exitoso): ?>
                    <div class="card shadow">
                        <div class="course-header text-center">
                            <h2><i class="fas fa-check-circle"></i> ¡Registro exitoso!</h2>
                        </div>
                        <div class="card-body text-center">
                            <h4 class="mb-4"><?= htmlspecialchars($curso['nombre_curso']) ?></h4>
                            
                            <?php if ($curso['requiere_pago']): ?>
                                <div class="alert alert-warning">
                                    <h5><i class="fas fa-money-bill-wave"></i> Gracias por tu registro</h5>
                                    <p>Para completar tu inscripción, por favor realiza el pago de $<?= number_format($curso['costo'], 2) ?></p>
                                    <p>Tu contraseña temporal es <?= $_SESSION['pass_temporal'] ?></p>
                                    <p>Pasaremos al panel en 10 segundos</p>
                                    <a class="btn btn-primary" href="participantePanel/index.php">Ir al panel</a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success">
                                    <h5><i class="fas fa-envelope"></i> Confirmación</h5>
                                    <p>Pasaremos al panel en 10 segundos</p>
                                     <p>Tu contraseña temporal es <?= $_SESSION['pass_temporal'] ?></p>
                                    <a class="btn btn-primary" href="participantePanel/index.php">Ir al panel</a>
                                </div>
                            <?php endif; ?>
                            
                            <a href="/" class="btn btn-primary mt-3">Volver al inicio</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card shadow">
                        <div class="course-header">
                            <h2 class="text-center"><?= htmlspecialchars($curso['nombre_curso']) ?></h2>
                            <p class="text-center mb-0"><?= htmlspecialchars($curso['descripcion']) ?></p>
                        </div>
                        
                        <div class="registration-form">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                            <?php endif; ?>
                            
                            <h4 class="mb-4"><i class="fas fa-user-plus"></i> Completa tu registro</h4>
                            
                            <form method="POST">
                                <div class="row">
                                                                    <div class="mb-3">
                                <label for="titulo" class="form-label">Titulo*</label>
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

                                <div class="mb-3 d-none" id="otroTituloDiv">
                                    <label for="otro_titulo" class="form-label">Otro* (Se mostrara en el cerfitcado del curso)</label>
                                    <input type="text" class="form-control" id="otro_titulo" name="otro_titulo">
                                </div>

                                <?php if ($curso['requiere_pago']): ?>
                                    <?php if ($opcion_pago_info): ?>
                                        <div class="alert alert-info">
                                            <h5><i class="fas fa-info-circle"></i> Plan de Pago Seleccionado</h5>
                                            <p>Costo del curso: <strong>$<?= number_format(($curso['costo'] + $opcion_pago_info['costo_adicional']), 2) ?></strong></p>
                                            <p>Número de pagos: <strong><?= $opcion_pago_info['numero_pagos'] ?></strong></p>
                                            <p>Frecuencia: <strong><?= htmlspecialchars($opcion_pago_info['frecuencia']) ?></strong></p>
                                            <?php $total = $curso['costo'] + $opcion_pago_info['costo_adicional']; $aprox = $total / $opcion_pago_info['numero_pagos']; ?>
                                            <p>Pago aproximado: <strong>$<?= number_format($aprox, 2) ?></strong></p>
                                            <?php if (!empty($opcion_pago_info['nota'])): ?>
                                                <p>Nota: <strong><?= htmlspecialchars($opcion_pago_info['nota']) ?></strong></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <h5><i class="fas fa-info-circle"></i> Información de Pago</h5>
                                            <p>Costo del curso: <strong>$<?= number_format($curso['costo'], 2) ?></strong></p>
                                            <p>Después de registrarte, serás redirigido al proceso de pago.</p>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-check"></i> Confirmar Registro
                                    </button>
                                    <a href="participantePanel/login.php?clave=<?php echo $_GET['clave']; ?>" class="btn btn-secondary btn-lg mt-2">
                                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                                    </a>
                                </div>

                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
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