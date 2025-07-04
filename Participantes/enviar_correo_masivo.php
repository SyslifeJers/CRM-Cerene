<?php
include '../Modulos/Head.php';
require_once '../DB/Conexion.php';
$database = new Database();

$id_curso = $_POST['id_curso'] ?? null;

$correo_estado = [];
$query = $database->getConnection()->prepare("
    SELECT participantes.email, inscripciones.estado 
    FROM inscripciones
    INNER JOIN participantes ON inscripciones.id_participante = participantes.id_participante
    WHERE inscripciones.id_curso = ? 
      AND participantes.email IS NOT NULL 
      AND participantes.email <> ''
");
$query->bind_param("i", $id_curso);
$query->execute();
$result = $query->get_result();
while ($row = $result->fetch_assoc()) {
    $correo_estado[] = $row;
}
?>

<div class="container mt-4">
    <h3>Redactar correo en HTML</h3>
    <form method="POST" action="procesar_envio_mailgun.php">
        <input type="hidden" name="id_curso" value="<?php echo $id_curso; ?>">
        <input type="hidden" name="correos" value="<?php echo htmlspecialchars(json_encode($correos)); ?>">

        <div class="mb-3">
            <label>Asunto:</label>
            <input type="text" name="asunto" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Selecciona a qué estados enviar:</label><br>
            <?php
            
            $estados_disponibles = ['registrado', 'comprobante_enviado', 'pago_validado', 'rechazado'];
            foreach ($estados_disponibles as $estado) {
                echo '<div class="form-check form-check-inline">';
                echo '<input class="form-check-input" type="checkbox" name="estados[]" value="' . $estado . '" id="estado_' . $estado . '">';
                echo '<label class="form-check-label" for="estado_' . $estado . '">' . ucfirst(str_replace('_', ' ', $estado)) . '</label>';
                echo '</div>';
            }
            ?>
        </div>
        <div class="mb-3">
            <label>Contenido HTML:</label>
            <textarea name="contenido" id="editor" rows="10" class="form-control"></textarea>
        </div>
        <div class="mb-3 mt-3">
            <label><strong>Correos seleccionados:</strong></label>
            <div id="correos-seleccionados" style="max-height: 150px; overflow-y: auto;" class="border p-2 bg-light"></div>
        </div>

       <input type="hidden" name="correos_json" id="correos-input">
        <button type="submit" class="btn btn-success">Enviar correo</button>
    </form>
</div>
<hr>
<h4>Resumen de inscritos</h4>
<table class="table table-sm table-bordered">
    <thead class="table-light">
        <tr>
            <th>#</th>
            <th>Email</th>
            <th>Estatus</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $query = $database->getConnection()->prepare("
            SELECT participantes.email, inscripciones.estado 
            FROM inscripciones
            INNER JOIN participantes ON inscripciones.id_participante = participantes.id_participante
            WHERE inscripciones.id_curso = ? 
              AND participantes.email IS NOT NULL 
              AND participantes.email <> ''
        ");
        $query->bind_param("i", $id_curso);
        $query->execute();
        $result = $query->get_result();

        $num = 1;
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$num}</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            $estado = $row['estado'];
            $colores = [
                'registrado' => 'secondary',
                'comprobante_enviado' => 'info',
                'pago_validado' => 'success',
                'rechazado' => 'danger'
            ];
            $badgeColor = $colores[$estado] ?? 'dark';
            $estadoTexto = ucfirst(str_replace('_', ' ', $estado));

            echo "<td><span class='badge bg-{$badgeColor}'>{$estadoTexto}</span></td>";
            echo "</tr>";
            $num++;
        }
        ?>
    </tbody>
</table>
<!-- TinyMCE para editor WYSIWYG -->
<script src="https://cdn.tiny.cloud/1/r6qaw8zabkmxkgod9f7v3ou4kjrj20mzo0jr1dl9snt8qsqm/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
        document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('input[name="estados[]"]');
        const contenedor = document.getElementById('correos-seleccionados');
        const inputHidden = document.getElementById('correos-input');

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', actualizarCorreos);
        });

        function actualizarCorreos() {
            const seleccionados = Array.from(checkboxes)
                .filter(chk => chk.checked)
                .map(chk => chk.value);

            const filtrados = correosPorEstado
                .filter(item => seleccionados.includes(item.estado))
                .map(item => item.email);

            // Mostrar en el contenedor
            contenedor.innerHTML = filtrados.length > 0 
                ? filtrados.map(c => `<div>${c}</div>`).join('') 
                : '<em>No hay correos seleccionados</em>';

            // Actualizar el input hidden
            inputHidden.value = JSON.stringify(filtrados);
        }
    });
    tinymce.init({
        selector: '#editor',
        height: 400,
        plugins: 'image link lists code',
        toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | image link | code'
    });
</script>
<script>
    const correosPorEstado = <?php echo json_encode($correo_estado); ?>;
</script>
<?php include '../Modulos/Footer.php'; ?>