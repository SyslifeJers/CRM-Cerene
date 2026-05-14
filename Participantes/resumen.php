<?php
require_once '../DB/Conexion.php';

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $id_inscripcion = isset($_POST['id_inscripcion']) ? intval($_POST['id_inscripcion']) : 0;
    $id_participante = isset($_POST['id_participante']) ? intval($_POST['id_participante']) : 0;

    if ($id_inscripcion <= 0 || $id_participante <= 0) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit();
    }

    $stmt = $conn->prepare("SELECT estado FROM inscripciones WHERE id_inscripcion = ? AND id_participante = ?");
    $stmt->bind_param("ii", $id_inscripcion, $id_participante);
    $stmt->execute();
    $inscripcion = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$inscripcion) {
        echo json_encode(['success' => false, 'message' => 'Inscripción no encontrada']);
        exit();
    }

    if ($inscripcion['estado'] !== 'registrado') {
        echo json_encode(['success' => false, 'message' => 'Solo se pueden quitar cursos en estado registrado']);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM inscripciones WHERE id_inscripcion = ? AND id_participante = ? AND estado = 'registrado'");
    $stmt->bind_param("ii", $id_inscripcion, $id_participante);
    $stmt->execute();
    $eliminado = $stmt->affected_rows > 0;
    $stmt->close();

    echo json_encode([
        'success' => $eliminado,
        'message' => $eliminado ? 'Curso quitado del participante' : 'No se pudo quitar el curso'
    ]);
    exit();
}

include '../Modulos/Head.php';

$id_participante = isset($_GET['id_participante']) ? intval($_GET['id_participante']) : 0;
$id_curso_origen = isset($_GET['id_curso']) ? intval($_GET['id_curso']) : 0;

if ($id_participante <= 0) {
    die('<div class="alert alert-danger">Debe especificar un participante válido</div>');
}

$stmt = $conn->prepare("SELECT id_participante, titulo, nombre, apellido, email, telefono, fecha_registro FROM participantes WHERE id_participante = ?");
$stmt->bind_param("i", $id_participante);
$stmt->execute();
$participante = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$participante) {
    die('<div class="alert alert-danger">Participante no encontrado</div>');
}

$stmt = $conn->prepare("SELECT i.id_inscripcion, i.id_curso, i.estado, i.metodo_pago, i.monto_pagado, i.fecha_inscripcion, i.fecha_cambio_estado, c.nombre_curso, c.fecha_inicio, c.fecha_fin, c.activo
    FROM inscripciones i
    INNER JOIN cursos c ON c.id_curso = i.id_curso
    WHERE i.id_participante = ? AND c.activo = 1
    ORDER BY c.fecha_inicio DESC, i.fecha_inscripcion DESC");
$stmt->bind_param("i", $id_participante);
$stmt->execute();
$cursos = $stmt->get_result();
$stmt->close();

$badgeClass = [
    'registrado' => 'bg-secondary',
    'pendiente_pago' => 'bg-warning',
    'comprobante_enviado' => 'bg-info',
    'revision_pago' => 'bg-primary',
    'pagos programados' => 'bg-info',
    'pago_validado' => 'bg-success',
    'rechazado' => 'bg-danger',
    'Revision de pago' => 'bg-primary'
];
?>

<div class="row mb-3">
    <div class="col-md-12">
        <a href="index.php?id_curso=<?php echo $id_curso_origen; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a participantes
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Resumen del cliente</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>ID:</strong> <?php echo (int) $participante['id_participante']; ?></p>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars(trim($participante['titulo'] . ' ' . $participante['nombre'] . ' ' . $participante['apellido'])); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($participante['email']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($participante['telefono']); ?></p>
                        <p><strong>Fecha registro:</strong> <?php echo $participante['fecha_registro'] ? date('d/m/Y', strtotime($participante['fecha_registro'])) : 'N/A'; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Cursos actuales</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="cursosParticipanteTable">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID inscripción</th>
                                <th>Curso</th>
                                <th>Fechas</th>
                                <th>Inscripción</th>
                                <th>Estado</th>
                                <th>Método pago</th>
                                <th>Monto</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($cursos->num_rows > 0): ?>
                                <?php while ($curso = $cursos->fetch_assoc()): ?>
                                    <?php
                                    $estado = $curso['estado'];
                                    $claseEstado = $badgeClass[$estado] ?? 'bg-secondary';
                                    $fechaInicio = $curso['fecha_inicio'] ? date('d/m/Y', strtotime($curso['fecha_inicio'])) : 'N/A';
                                    $fechaFin = $curso['fecha_fin'] ? date('d/m/Y', strtotime($curso['fecha_fin'])) : 'N/A';
                                    $fechaInscripcion = $curso['fecha_inscripcion'] ? date('d/m/Y', strtotime($curso['fecha_inscripcion'])) : 'N/A';
                                    ?>
                                    <tr>
                                        <td><?php echo (int) $curso['id_inscripcion']; ?></td>
                                        <td><?php echo htmlspecialchars($curso['nombre_curso']); ?></td>
                                        <td><?php echo $fechaInicio . ' - ' . $fechaFin; ?></td>
                                        <td><?php echo $fechaInscripcion; ?></td>
                                        <td><span class="badge <?php echo $claseEstado; ?>"><?php echo htmlspecialchars($estado); ?></span></td>
                                        <td><?php echo $curso['metodo_pago'] ? htmlspecialchars($curso['metodo_pago']) : 'N/A'; ?></td>
                                        <td class="text-end"><?php echo $curso['monto_pagado'] ? '$' . number_format($curso['monto_pagado'], 2) : 'N/A'; ?></td>
                                        <td class="text-center">
                                            <?php if ($estado === 'registrado'): ?>
                                                <button class="btn btn-danger btn-sm quitar-curso" data-id="<?php echo (int) $curso['id_inscripcion']; ?>" data-curso="<?php echo htmlspecialchars($curso['nombre_curso'], ENT_QUOTES); ?>">
                                                    <i class="fas fa-user-minus"></i> Quitar
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">No disponible</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">El participante no tiene cursos actuales</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../Modulos/Footer.php'; ?>

<script>
$(function () {
    $('.quitar-curso').click(function () {
        const idInscripcion = $(this).data('id');
        const curso = $(this).data('curso');

        Swal.fire({
            title: '¿Quitar curso?',
            text: `Se quitará "${curso}" de este participante.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, quitar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: 'resumen.php',
                type: 'POST',
                data: {
                    id_inscripcion: idInscripcion,
                    id_participante: <?php echo (int) $id_participante; ?>
                },
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        Swal.fire('Éxito', res.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                }
            });
        });
    });

    if (document.querySelector('#cursosParticipanteTable')) {
        new DataTable('#cursosParticipanteTable', {
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            }
        });
    }
});
</script>
