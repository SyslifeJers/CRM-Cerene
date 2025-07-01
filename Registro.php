<?php
require_once 'DB/Conexion.php';
$database = new Database();

$clave_curso = $_GET['clave'] ?? null;
$curso = null;
$registro_exitoso = false;

// Verificar que la clave sea válida
if ($clave_curso) {
    $stmt = $database->getConnection()->prepare("SELECT * FROM cursos WHERE clave_curso = ? AND activo = 1");
    $stmt->bind_param("s", $clave_curso);
    $stmt->execute();
    $result = $stmt->get_result();
    $curso = $result->fetch_assoc();
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
            // Insertar inscripción
            $sql_inscripcion = "INSERT INTO inscripciones 
                            (id_curso, id_participante, estado, fecha_inscripcion) 
                            VALUES (?, ?, ?, NOW())";
            
            $stmt_inscripcion = $database->getConnection()->prepare($sql_inscripcion);
            $stmt_inscripcion->bind_param("iis", $curso['id_curso'], $id_participante, $estado);

            if (!$stmt_inscripcion->execute()) {
                throw new Exception("Error al inscribir: " . $stmt_inscripcion->error);
            }
            $_SESSION['participante_id'] = $id_participante;
            $_SESSION['email'] = $email;
            $_SESSION['nombre'] = $nombre . ' ' . $apellido;
            
            $stmt_inscripcion->close();
            $registro_exitoso = true;
        } else {
            throw new Exception("Error al registrar participante: " . $stmt_participante->error);
        }


        // Aquí podrías agregar lógica para enviar correo de confirmación
    } catch (Exception $e) {
        $error = "Error al registrar: " . $e->getMessage();
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
                                    <label for="email" class="form-label">Correo Electrónico*</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">Teléfono*</label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono" required>
                                </div>

                                <div class="mb-3">
                                    <label for="cedula" class="form-label">Cédula*</label>
                                    <input type="text" class="form-control" id="cedula" name="cedula" required>
                                </div>

                                <div class="mb-3 d-none" id="otroTituloDiv">
                                    <label for="otro_titulo" class="form-label">Otro*</label>
                                    <input type="text" class="form-control" id="otro_titulo" name="otro_titulo">
                                </div>

                                <?php if ($curso['requiere_pago']): ?>
                                    <div class="alert alert-info">
                                        <h5><i class="fas fa-info-circle"></i> Información de Pago</h5>
                                        <p>Costo del curso: <strong>$<?= number_format($curso['costo'], 2) ?></strong></p>
                                        <p>Después de registrarte, serás redirigido al proceso de pago.</p>
                                    </div>
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