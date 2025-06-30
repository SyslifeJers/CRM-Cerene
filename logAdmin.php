<?php
session_start();
require_once 'DB/Conexion.php';

$message = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';

    $database = new Database();
    $conn = $database->getConnection();

    // Validar usuario
    $stmt = $conn->prepare("SELECT id, pass, IdRol, name FROM Usuarios WHERE user = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hash, $idRol, $name);
        $stmt->fetch();

        if (strcmp($pass, $hash) == 0) {
            // Generar token

            // Guardar en sesión
            $_SESSION['idAdmin'] = $id;
            $_SESSION['user'] = $user;
            $_SESSION['rol'] = $idRol;
                $_SESSION['name'] = $name;
            header("Location: Admin/index.php");
            exit();
        } else {
            $message = "Contraseña incorrecta.";
        }
    } else {
        $message = "Usuario no encontrado.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cerene App - Login</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/css/kaiadmin.min.css" />
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="text-center mb-0">Login</h3>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="user" class="form-label">Usuario</label>
                            <input type="text" name="user" id="user" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="pass" class="form-label">Contraseña</label>
                            <input type="password" name="pass" id="pass" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Iniciar sesión</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/core/jquery-3.7.1.min.js"></script>
<script src="../assets/js/core/bootstrap.min.js"></script>
</body>
</html>
