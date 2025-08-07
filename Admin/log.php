<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../DB/Conexion.php';

    $id_usuario = intval($_POST['id_usuario'] ?? 0);
    $clave = $_POST['clave'] ?? '';

    $clave_fija = 'Acceso2024!'; // Cambia aquí la clave si lo deseas

    if ($clave !== $clave_fija) {
        $error = "Clave incorrecta";
    } else {
        $database = new Database();
        $stmt = $database->getConnection()->prepare("SELECT id_participante, nombre, apellido FROM participantes WHERE id_participante = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $participante = $result->fetch_assoc();
            $_SESSION['participante_id'] = $participante['id_participante'];
            $_SESSION['nombre'] = $participante['nombre'] . ' ' . $participante['apellido'];

            $clave_curso = $_GET['clave'] ?? null;
            if ($clave_curso) {
                header("Location: ../participantePanel/index.php?clave=" . urlencode($clave_curso));
            } else {
                header("Location: ../participantePanel/index.php");
            }
            exit();
        } else {
            $error = "Usuario no encontrado";
        }
    }
}
?>
<label><?php echo $error ?? ''; ?></label>
<!-- HTML sin cambios mayores, salvo los campos del formulario -->
<form method="POST">
    <div class="form-group">
        <label>ID del Usuario</label>
        <input type="number" name="id_usuario" class="form-control" required>
    </div>

    <div class="form-group">
        <label>Clave Fija</label>
        <input type="password" name="clave" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
</form>