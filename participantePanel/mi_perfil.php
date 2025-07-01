<?php
session_start();
require_once '../DB/Conexion.php';
include '../Modulos/HeadP.php';

if (!isset($_SESSION['participante_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$id_participante = $_SESSION['participante_id'];

// Obtener datos actuales
$stmt = $conn->prepare("SELECT nombre, apellido, cedula, titulo FROM participantes WHERE id_participante = ?");
$stmt->bind_param("i", $id_participante);
$stmt->execute();
$stmt->bind_result($nombre, $apellido, $cedula, $titulo);
$stmt->fetch();
$stmt->close();
?>

<div class="container mt-5">
    <h2 class="mb-4">Editar Perfil</h2>

    <!-- Nombre y Título -->
    <form method="POST" action="procesar_edicion_nombre.php">
        <input type="hidden" name="id" value="<?= $id_participante ?>">
        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($nombre) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Apellido</label>
            <input type="text" name="apellido" class="form-control" value="<?= htmlspecialchars($apellido) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Cédula</label>
            <input type="text" name="cedula" class="form-control" value="<?= htmlspecialchars($cedula) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Título / Grado</label>
            <select name="titulo" class="form-select" id="tituloSelect" required>
                <option value="">Seleccionar</option>
                <option value="Psicólogo(a)" <?= $titulo == 'Psicólogo(a)' ? 'selected' : '' ?>>Psicólogo(a)</option>
                <option value="Médico(a)" <?= $titulo == 'Médico(a)' ? 'selected' : '' ?>>Médico(a)</option>
                <option value="Licenciado(a)" <?= $titulo == 'Licenciado(a)' ? 'selected' : '' ?>>Licenciado(a)</option>
                <option value="Otro" <?= !in_array($titulo, ['Psicólogo(a)', 'Médico(a)', 'Licenciado(a)']) ? 'selected' : '' ?>>Otro</option>
            </select>
        </div>
        <div class="mb-3" id="otroTituloBox" style="display: none;">
            <label class="form-label">Especificar otro título</label>
            <input type="text" class="form-control" name="titulo_otro" id="titulo_otro" value="<?= (!in_array($titulo, ['Psicólogo(a)', 'Médico(a)', 'Licenciado(a)'])) ? htmlspecialchars($titulo) : '' ?>">
        </div>
        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
    </form>

    <hr class="my-4">

    <!-- Cambiar Contraseña -->
<h4 class="mt-5">Cambiar Contraseña</h4>
<form method="POST" action="procesar_cambio_pass.php">
    <div class="mb-3">
        <label for="pass_actual" class="form-label">Contraseña Actual*</label>
        <input type="password" class="form-control" id="pass_actual" name="pass_actual" required>
    </div>
    <div class="mb-3">
        <label for="pass_nueva" class="form-label">Nueva Contraseña*</label>
        <input type="password" class="form-control" id="pass_nueva" name="pass_nueva" minlength="6" required>
    </div>
    <div class="mb-3">
        <label for="confirmar_pass" class="form-label">Confirmar Nueva Contraseña*</label>
        <input type="password" class="form-control" id="confirmar_pass" name="confirmar_pass" required>
    </div>
    <button type="submit" class="btn btn-primary">Guardar Contraseña</button>
</form>

</div>

<script>
document.getElementById('tituloSelect').addEventListener('change', function () {
    const otroBox = document.getElementById('otroTituloBox');
    if (this.value === 'Otro') {
        otroBox.style.display = 'block';
    } else {
        otroBox.style.display = 'none';
        document.getElementById('titulo_otro').value = '';
    }
});

if (document.getElementById('tituloSelect').value === 'Otro') {
    document.getElementById('otroTituloBox').style.display = 'block';
}
</script>

<?php include '../Modulos/Footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    <?php if (isset($_GET['success'])): ?>
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '<?= $_GET['success'] === 'nombre_actualizado' ? 'Nombre actualizado correctamente.' : 'Contraseña actualizada correctamente.' ?>',
            confirmButtonText: 'OK'
        });
    <?php elseif (isset($_GET['error'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: `<?php
                switch ($_GET['error']) {
                    case 'campos_obligatorios': echo "Completa todos los campos."; break;
                    case 'pass_corta': echo "La nueva contraseña es muy corta."; break;
                    case 'confirmacion_incorrecta': echo "Las contraseñas no coinciden."; break;
                    case 'pass_actual_incorrecta': echo "La contraseña actual es incorrecta."; break;
                    default: echo "Ocurrió un error inesperado.";
                }
            ?>`,
            confirmButtonText: 'OK'
        });
    <?php endif; ?>
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const nuevaPass = document.getElementById("pass_nueva");
    const confirmarPass = document.getElementById("confirmar_pass");

    // Crear elementos visuales de retroalimentación
    const feedbackSeguridad = document.createElement("div");
    const feedbackCoinciden = document.createElement("div");

    nuevaPass.parentNode.appendChild(feedbackSeguridad);
    confirmarPass.parentNode.appendChild(feedbackCoinciden);

    // Función para evaluar seguridad
    function evaluarSeguridad(pass) {
        const tieneMayus = /[A-Z]/.test(pass);
        const tieneMinus = /[a-z]/.test(pass);
        const tieneNum = /\d/.test(pass);
        const tieneEspecial = /[^A-Za-z0-9]/.test(pass);

        if (pass.length < 6) {
            return "❌ Al menos 6 caracteres";
        }

        let nivel = tieneMayus + tieneMinus + tieneNum + tieneEspecial;

        if (nivel >= 3) return "✅ Contraseña segura";
        if (nivel === 2) return "⚠️ Contraseña débil";
        return "❌ Muy débil";
    }

    nuevaPass.addEventListener("input", function () {
        const pass = nuevaPass.value;
        feedbackSeguridad.textContent = evaluarSeguridad(pass);
        feedbackSeguridad.style.color = pass.length >= 6 ? "green" : "red";
    });

    confirmarPass.addEventListener("input", function () {
        if (confirmarPass.value === nuevaPass.value) {
            feedbackCoinciden.textContent = "✅ Las contraseñas coinciden";
            feedbackCoinciden.style.color = "green";
        } else {
            feedbackCoinciden.textContent = "❌ Las contraseñas no coinciden";
            feedbackCoinciden.style.color = "red";
        }
    });
});
</script>