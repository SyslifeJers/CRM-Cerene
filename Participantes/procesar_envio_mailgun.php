<?php
include '../Modulos/Head.php';
require_once '../config/env_loader.php';
// Validar que sea un POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit("<div class='alert alert-warning text-center mt-5'>Acceso no permitido</div>");
}

// Función para mostrar mensajes
function mostrarMensaje($tipo, $titulo, $mensaje) {
    $iconos = [
        'success' => '✅',
        'danger' => '❌',
        'warning' => '⚠️',
        'info' => 'ℹ️'
    ];
    $icono = $iconos[$tipo] ?? '';

    echo "
    <div class='container mt-5' style='max-width:600px;'>
        <div class='alert alert-$tipo text-center' role='alert' style='font-size:1.2em;'>
            <div>$icono <strong>$titulo</strong></div>
            <div class='mt-2'>$mensaje</div>
        </div>
        <div class='text-center'>
            <a href='https://cursos.clinicacerene.com' class='btn btn-primary'>Regresar</a>
        </div>
    </div>
    ";
}

// Obtener datos del formulario
$asunto = trim($_POST['asunto'] ?? '');
$contenido = $_POST['contenido'] ?? '';
$estadosSeleccionados = $_POST['estados'] ?? [];
$correosPorEstado = json_decode($_POST['correos_json'] ?? '{}', true);

// Validaciones
if (empty($asunto) || empty($contenido)) {
    mostrarMensaje('danger', 'Error', 'Asunto y contenido son obligatorios.');
    include '../Modulos/Footer.php';
    exit;
}

if (empty($estadosSeleccionados)) {
    mostrarMensaje('warning', 'Advertencia', 'Debes seleccionar al menos un estado.');
    include '../Modulos/Footer.php';
    exit;
}

if (empty($correosPorEstado)) {
    mostrarMensaje('warning', 'Sin destinatarios', 'No hay correos válidos para los estados seleccionados.');
    include '../Modulos/Footer.php';
    exit;
}

cargarEnv();

// Configuración Mailgun API desde el entorno
$apiKey = getenv('MAILGUN_API_KEY');
$domain = getenv('MAILGUN_DOMAIN');
$from = getenv('MAILGUN_FROM');

// Preparar destinatarios
$destinatarios = implode(',', array_map('trim', $correosPorEstado));

// Enviar correo con cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.mailgun.net/v3/$domain/messages",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_USERPWD => $apiKey,
    CURLOPT_POSTFIELDS => [
        'from' => $from,
        'to' => $destinatarios,
        'subject' => $asunto,
        'html' => $contenido
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Manejo de respuesta
if ($httpCode === 200) {
    mostrarMensaje('success', 'Éxito', 'Correo enviado a ' . count($correosPorEstado) . ' destinatario(s) exitosamente.');
} else {
    mostrarMensaje('danger', 'Error al enviar', 'No se pudo enviar el correo. Código HTTP: ' . $httpCode . '. Detalles: ' . htmlspecialchars($response ?: $error));
}

include '../Modulos/Footer.php';
?>
