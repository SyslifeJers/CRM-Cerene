<?php
require_once '../DB/Conexion.php';
require_once '../config/env_loader.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT nombre, apellido, email FROM participantes WHERE id_participante = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$participante = $result->fetch_assoc();
$stmt->close();

if (!$participante) {
    echo json_encode(['success' => false, 'message' => 'Participante no encontrado']);
    exit;
}

$pass_plain = sprintf('%06d', mt_rand(0, 999999));
$pass_hash = password_hash($pass_plain, PASSWORD_DEFAULT);

$upd = $conn->prepare("UPDATE participantes SET pass = ? WHERE id_participante = ?");
$upd->bind_param('si', $pass_hash, $id);
if (!$upd->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar contraseña']);
    exit;
}
$upd->close();

function enviarCorreoRestablecimiento($destino, $nombreCompleto, $password) {
    cargarEnv();
    $apiKey = getenv('MAILGUN_API_KEY');
    $domain = getenv('MAILGUN_DOMAIN');
    $from   = getenv('MAILGUN_FROM');
    if (!$apiKey || !$domain || !$from) {
        return;
    }
    $asunto = 'Restablecimiento de contraseña';
    $html = "<p>Hola {$nombreCompleto},</p>".
            "<p>Tu contraseña ha sido restablecida. La nueva contraseña temporal es:</p>".
            "<p style='font-size:18px'><strong>{$password}</strong></p>".
            "<p>Puedes acceder a la plataforma desde <a href='https://cursos.clinicacerene.com/index.php'>este enlace</a>.</p>".
            "<p>Te recomendamos cambiarla después de iniciar sesión.</p>";

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

enviarCorreoRestablecimiento($participante['email'], $participante['nombre'].' '.$participante['apellido'], $pass_plain);

$db->closeConnection();

echo json_encode(['success' => true, 'pass' => $pass_plain]);
