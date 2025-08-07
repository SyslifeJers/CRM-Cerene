<?php
include '../Modulos/Head.php';
require_once '../DB/Conexion.php';
$database = new Database();

// Función para enviar WhatsApp (simulada - requiere implementación real con API)
function enviarWhatsApp($telefono, $mensaje) {
    $telefono = preg_replace('/[^0-9]/', '', $telefono);
    
    if (strlen($telefono) == 10) {
        $telefono = '52' . $telefono; // Prefijo para México
        error_log("WhatsApp enviado a $telefono: $mensaje");
        return true;
    }
    return false;
}

// Función para enviar Email
function enviarEmail($email, $asunto, $contenido) {
    $headers = "From: cursos@tudominio.com\r\n";
    $headers .= "Reply-To: no-reply@tudominio.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    if (mail($email, $asunto, $contenido, $headers)) {
        error_log("Email enviado a $email: $asunto");
        return true;
    }
    return false;
}

// Procesar envío de boletines
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_boletin'])) {
    $participantes_seleccionados = $_POST['participantes'] ?? [];
    $mensaje_whatsapp = $_POST['mensaje_whatsapp'] ?? '';
    $asunto_email = $_POST['asunto_email'] ?? '';
    $contenido_email = $_POST['contenido_email'] ?? '';
    
    $resultados = [
        'whatsapp' => ['exitosos' => 0, 'fallidos' => 0],
        'email' => ['exitosos' => 0, 'fallidos' => 0]
    ];
    
    if (!empty($participantes_seleccionados)) {
        $participantes_ids = array_map('intval', $participantes_seleccionados);
        $placeholders = implode(',', array_fill(0, count($participantes_ids), '?'));
        
        $query = $database->getConnection()->prepare("
            SELECT DISTINCT id_participante, nombre, apellido, email, telefono 
            FROM participantes 
            WHERE id_participante IN ($placeholders)
        ");
        $query->bind_param(str_repeat('i', count($participantes_ids)), ...$participantes_ids);
        $query->execute();
        $participantes = $query->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($participantes as $participante) {
            $nombre_completo = $participante['nombre'] . ' ' . $participante['apellido'];
            
            // Enviar WhatsApp
            if (!empty($mensaje_whatsapp) && !empty($participante['telefono'])) {
                $mensaje_personalizado = str_replace(
                    ['{nombre}', '{apellido}', '{nombre_completo}'],
                    [$participante['nombre'], $participante['apellido'], $nombre_completo],
                    $mensaje_whatsapp
                );
                
                if (enviarWhatsApp($participante['telefono'], $mensaje_personalizado)) {
                    $resultados['whatsapp']['exitosos']++;
                } else {
                    $resultados['whatsapp']['fallidos']++;
                }
            }
            
            // Enviar Email
            if (!empty($contenido_email) && !empty($participante['email'])) {
                $contenido_personalizado = str_replace(
                    ['{nombre}', '{apellido}', '{nombre_completo}'],
                    [$participante['nombre'], $participante['apellido'], $nombre_completo],
                    $contenido_email
                );
                
                if (enviarEmail($participante['email'], $asunto_email, $contenido_personalizado)) {
                    $resultados['email']['exitosos']++;
                } else {
                    $resultados['email']['fallidos']++;
                }
            }
        }
        
        // Mostrar resultados
        $mensaje_resultado = '<div class="alert alert-success">';
        $mensaje_resultado .= '<h5>Resultados del envío:</h5>';
        $mensaje_resultado .= '<p>WhatsApp: ' . $resultados['whatsapp']['exitosos'] . ' exitosos, ' . $resultados['whatsapp']['fallidos'] . ' fallidos</p>';
        $mensaje_resultado .= '<p>Emails: ' . $resultados['email']['exitosos'] . ' exitosos, ' . $resultados['email']['fallidos'] . ' fallidos</p>';
        $mensaje_resultado .= '</div>';
        
        echo $mensaje_resultado;
    } else {
        echo '<div class="alert alert-warning">No se seleccionaron participantes</div>';
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Todos los participantes</h4>
<button class="btn btn-primary float-right" id="abrirModal" hidden>
    <i class="fas fa-paper-plane"></i> Enviar boletín masivo
</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <?php
                    date_default_timezone_set('America/Mexico_City');
                    $hoy = date('Y-m-d');
                    echo "<p class='text-muted'>Fecha actual: $hoy</p>";
                       ?>
                        <table id="participantesTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                        </table>
                 
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para enviar boletines -->
<div class="modal fade" id="boletinModal" tabindex="-1" role="dialog" aria-labelledby="boletinModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="boletinModalLabel">Enviar boletín masivo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Seleccionar participantes:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="seleccionarTodos">
                            <label class="form-check-label" for="seleccionarTodos">Seleccionar todos</label>
                        </div>
                        <div class="participantes-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-top: 10px;">
                            <?php
                            $query = $database->getConnection()->query("
                                SELECT DISTINCT id_participante, nombre, apellido, email, telefono
                                FROM participantes
                                ORDER BY nombre ASC
                            ");
                            
                            while ($participante = $query->fetch_assoc()) {
                                $nombre_completo = htmlspecialchars($participante['nombre'].' '.$participante['apellido']);
                                echo '<div class="form-check">
                                    <input class="form-check-input participante-checkbox" type="checkbox" 
                                        name="participantes[]" value="'.$participante['id_participante'].'" 
                                        id="part-'.$participante['id_participante'].'">
                                    <label class="form-check-label" for="part-'.$participante['id_participante'].'" style="display: inline-block; width: 100%;">
                                        <strong>'.$nombre_completo.'</strong><br>
                                        <small class="text-muted">Email: '.htmlspecialchars($participante['email']).' | Tel: '.htmlspecialchars($participante['telefono']).'</small>
                                    </label>
                                </div><hr style="margin: 5px 0;">';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="mensaje_whatsapp">Mensaje para WhatsApp:</label>
                        <textarea class="form-control" id="mensaje_whatsapp" name="mensaje_whatsapp" rows="3" 
                            placeholder="¡Hola {nombre}! 🎉 Tenemos nuevas actualizaciones para ti..."></textarea>
                        <small class="form-text text-muted">
                            Variables: <code>{nombre}</code>, <code>{apellido}</code>, <code>{nombre_completo}</code>
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="asunto_email">Asunto para Email:</label>
                        <input type="text" class="form-control" id="asunto_email" name="asunto_email" 
                            placeholder="¡Novedades importantes!">
                    </div>
                    
                    <div class="form-group">
                        <label for="contenido_email">Contenido para Email (HTML):</label>
                        <textarea class="form-control" id="contenido_email" name="contenido_email" rows="5" 
                            placeholder="&lt;h1&gt;¡Hola {nombre}!&lt;/h1&gt;&lt;p&gt;Tenemos novedades para ti...&lt;/p&gt;"></textarea>
                        <small class="form-text text-muted">
                            Usa HTML básico. Variables: <code>{nombre}</code>, <code>{apellido}</code>, <code>{nombre_completo}</code>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" name="enviar_boletin" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Enviar boletines
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include '../Modulos/Footer.php'; ?>
<script>
// Mostrar modal al hacer clic en el botón
document.getElementById('abrirModal').addEventListener('click', function() {
    $('#boletinModal').modal('show');
});

// Seleccionar/deseleccionar todos
document.getElementById('seleccionarTodos').addEventListener('change', function() {
    var checkboxes = document.querySelectorAll('.participante-checkbox');
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = event.target.checked;
    });
});
new DataTable('#participantesTable', {
    serverSide: true,
    ajax: {
        url: 'participantes_data.php',
        type: 'POST'
    },
    pageLength: 50,
    language: {
        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
    }
});
$(document).on('click', '.reset-pass-btn', function() {
    var id = $(this).data('id');
    Swal.fire({
        title: '¿Restablecer contraseña?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, restablecer',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.post('restablecer_pass.php', {id: id}, function(res) {
                if (res.success) {
                    Swal.fire('Nueva contraseña', 'La nueva contraseña es: <strong>' + res.pass + '</strong>', 'success');
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json').fail(function() {
                Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
            });
        }
    });
});


</script>

