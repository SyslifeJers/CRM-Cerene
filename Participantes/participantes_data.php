<?php
require_once '../DB/Conexion.php';

header('Content-Type: application/json');

$db = new Database();
$conn = $db->getConnection();

$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = $_POST['search']['value'] ?? '';

$columns = [
    0 => 'id_participante',
    1 => 'nombre',
    2 => 'email',
    3 => 'telefono',
    4 => 'fecha_registro'
];

$orderIdx = $_POST['order'][0]['column'] ?? 4;
$orderDir = $_POST['order'][0]['dir'] ?? 'desc';
$orderColumn = $columns[$orderIdx] ?? 'fecha_registro';

$totalResult = $conn->query('SELECT COUNT(*) AS cnt FROM participantes');
$totalRecords = $totalResult->fetch_assoc()['cnt'];

$where = '';
$params = [];
$types = '';
if ($search !== '') {
    $where = 'WHERE nombre LIKE ? OR apellido LIKE ? OR email LIKE ? OR telefono LIKE ?';
    $searchLike = "%{$search}%";
    $params = [$searchLike, $searchLike, $searchLike, $searchLike];
    $types = 'ssss';
}

$recordsFiltered = $totalRecords;
if ($where) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM participantes $where");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $recordsFiltered = $res->fetch_assoc()['cnt'];
    $stmt->close();
}

$sql = "SELECT id_participante, nombre, apellido, email, telefono, fecha_registro FROM participantes";
if ($where) {
    $sql .= " $where";
}
$sql .= " ORDER BY $orderColumn $orderDir LIMIT ?, ?";

$params[] = $start;
$params[] = $length;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        $row['id_participante'],
        htmlspecialchars($row['nombre'].' '.$row['apellido']),
        htmlspecialchars($row['email']),
        htmlspecialchars($row['telefono']),
        $row['fecha_registro'],
        "<button class='btn btn-sm btn-warning reset-pass-btn' data-id='{$row['id_participante']}'><i class='fas fa-key'></i> Restablecer</button>"
    ];
}
$stmt->close();
$db->closeConnection();

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
]);
