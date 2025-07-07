<?php
require_once '../DB/Conexion.php';
include '../Modulos/Head.php';

$id_inscripcion = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_inscripcion <= 0) {
    echo '<div class="alert alert-danger">ID de inscripción inválido</div>';
    include '../Modulos/Footer.php';
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// 1. Obtener inscripción, curso y opción de pago
$stmt = $conn->prepare("SELECT i.id_inscripcion, i.IdOpcionPago AS id_opcion_pago,
                       i.metodo_pago, i.referencia_pago, i.monto_pagado,
                       i.comprobante_path, i.estado, i.fecha_cambio_estado,
                       c.nombre_curso, c.costo, op.numero_pagos, i.id_participante
                       FROM inscripciones i
                       JOIN cursos c ON i.id_curso = c.id_curso
                       LEFT JOIN opciones_pago op ON i.IdOpcionPago = op.id_opcion
                       WHERE i.id_inscripcion = ?");
$stmt->bind_param("i", $id_inscripcion);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo '<div class="alert alert-warning">Inscripción no encontrada</div>';
    include '../Modulos/Footer.php';
    exit();
}
$inscripcion = $result->fetch_assoc();
$stmt->close();

$numero_pagos = (int)($inscripcion['numero_pagos'] ?? 1);
$isMultiple = !empty($inscripcion['id_opcion_pago']);
$pagos = [];

// 2. Obtener comprobantes
if ($isMultiple) {
    $pagosStmt = $conn->prepare("SELECT id_comprobante, numero_pago, metodo_pago, referencia_pago, monto_pagado, comprobante_path, fecha_carga, validado, nota
                                  FROM comprobantes_inscripcion
                                  WHERE id_inscripcion = ?
                                  ORDER BY numero_pago");
    $pagosStmt->bind_param("i", $id_inscripcion);
    $pagosStmt->execute();
    $resPagos = $pagosStmt->get_result();
    while ($row = $resPagos->fetch_assoc()) {
        $pagos[] = $row;
    }
    $pagosStmt->close();
} else {
    if ($inscripcion['comprobante_path']) {
        $pagos[] = [
            'numero_pago'     => 1,
            'metodo_pago'     => $inscripcion['metodo_pago'],
            'referencia_pago' => $inscripcion['referencia_pago'],
            'monto_pagado'    => $inscripcion['monto_pagado'],
            'comprobante_path' => $inscripcion['comprobante_path'],
            'fecha_carga'     => $inscripcion['fecha_cambio_estado'],
            'validado'        => $inscripcion['estado'] === 'pago_validado' ? 1 : 0
        ];
    }
}

// 3. Obtener datos del participante
$infoStmt = $conn->prepare("SELECT nombre, apellido, email, telefono FROM participantes WHERE id_participante = ?");
$infoStmt->bind_param("i", $inscripcion['id_participante']);
$infoStmt->execute();
$info = $infoStmt->get_result()->fetch_assoc();
$infoStmt->close();

// 4. Calcular pagos validados
$total_validado = 0;
foreach ($pagos as $pago) {
    if ($pago['validado'] == 1) {
        $total_validado += $pago['monto_pagado'];
    }
}
$faltante = $inscripcion['costo'] - $total_validado;
?>

<div class="container mt-4">
    <div class="card mb-4">
        <div class="card-header bg-info text-white"><strong>Información del Participante</strong></div>
        <div class="card-body">
            <p><strong>Nombre:</strong> <?= htmlspecialchars($info['nombre'] . ' ' . $info['apellido']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($info['email']) ?></p>
            <p>
                <strong>Teléfono:</strong>
                <?= htmlspecialchars($info['telefono']) ?>
                <?php if (!empty($info['telefono'])): ?>
                    <?php
                    // Limpiar el número para WhatsApp (quitar espacios, guiones, paréntesis)
                    $telefono_wa = preg_replace('/\D+/', '', $info['telefono']);
                    ?>
                    <a href="https://wa.me/<?= $telefono_wa ?>" target="_blank" class="ms-2" title="Enviar WhatsApp">
                        <img src="https://img.icons8.com/color/24/000000/whatsapp--v1.png" alt="WhatsApp">
                    </a>
                <?php endif; ?>
            </p>
            <p><strong>Curso:</strong> <?= htmlspecialchars($inscripcion['nombre_curso']) ?></p>
            <p><strong>Costo del curso:</strong> $<?= number_format($inscripcion['costo'], 2) ?></p>
            <p><strong>Total pagado validado:</strong> $<?= number_format($total_validado, 2) ?></p>
            <p><strong>Faltante:</strong> $<?= number_format($faltante, 2) ?></p>
        </div>
    </div>

    <h3 class="mb-4">Pagos</h3>
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th># Pago</th>
                <th>Método</th>
                <th>Referencia</th>
                <th>Monto</th>
                <th>Comprobante</th>
                <th>Fecha</th>
                <th><?= $isMultiple ? 'Validado' : 'Estado' ?></th>
                <th>Nota</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($pagos) > 0): ?>
                <?php foreach ($pagos as $pago): ?>
                    <tr>
                        <td><?= $pago['numero_pago'] ?></td>
                        <td><?= htmlspecialchars($pago['metodo_pago']) ?></td>
                        <td><?= htmlspecialchars($pago['referencia_pago']) ?></td>
                        <td>$<?= number_format($pago['monto_pagado'], 2) ?></td>
                        <td><a href="../comprobantes/<?= htmlspecialchars($pago['comprobante_path']) ?>" target="_blank">Ver</a></td>
                        <td><?= date('d/m/Y', strtotime($pago['fecha_carga'])) ?></td>
                        <td>
                            <?php if ($isMultiple): ?>
                                <select class="form-select form-select-sm validar-select" data-id="<?= $pago['id_comprobante'] ?>">
                                    <option value="0" <?= $pago['validado'] == 0 ? 'selected' : '' ?>>Pendiente</option>
                                    <option value="1" <?= $pago['validado'] == 1 ? 'selected' : '' ?>>Correcto</option>
                                    <option value="3" <?= $pago['validado'] == 3 ? 'selected' : '' ?>>Rechazado</option>
                                </select>
                            <?php else: ?>
                                <?= htmlspecialchars($inscripcion['estado']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($pago['validado'] == 3): ?>
                                <?= htmlspecialchars($pago['nota'] ?? 'Sin nota') ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">Sin comprobantes cargados</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($faltante <= 0 && $inscripcion['estado'] !== 'pago_validado'): ?>
    <div class="text-center my-3">
        <button id="btnFinalizar" class="btn btn-success">
            <i class="fas fa-check-circle"></i> Finalizar proceso
        </button>
    </div>
<?php endif; ?>




<?php
include '../Modulos/Footer.php';
$database->closeConnection();
?>
<script>
$(document).ready(function () {
    $('.validar-select').change(function () {
        const id = $(this).data('id');
        const val = $(this).val();

        if (val == 3) {
            // Si es Rechazado, pedir razón
            Swal.fire({
                title: 'Razón del rechazo',
                input: 'textarea',
                inputLabel: 'Por favor indica el motivo',
                inputPlaceholder: 'Escribe aquí la razón del rechazo...',
                inputAttributes: {
                    'aria-label': 'Razón del rechazo'
                },
                showCancelButton: true,
                confirmButtonText: 'Enviar',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Debes escribir una razón';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('gestion_comprobante.php', {
                        accion: 'actualizar',
                        id_comprobante: id,
                        validado: val,
                        nota: result.value
                    }, function (res) {
                        if (res.success) {
                            Swal.fire('Éxito', res.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    }, 'json');
                } else {
                    // Si cancela, volver al valor anterior (opcional)
                    location.reload();
                }
            });
        } else {
            // Si es Pendiente o Correcto
            $.post('gestion_comprobante.php', {
                accion: 'actualizar',
                id_comprobante: id,
                validado: val
            }, function (res) {
                if (res.success) {
                    Swal.fire('Éxito', res.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }
    });

    $('#btnFinalizar').on('click', function () {
        Swal.fire({
            title: '¿Finalizar proceso?',
            text: 'Se marcará la inscripción como Pago Validado.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, finalizar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('gestion_inscripcion.php', {
                    accion: 'cambiar_estado',
                    id_inscripcion: <?php echo $id_inscripcion; ?>,
                    estado: 'pago_validado'
                }, function (res) {
                    if (res.success) {
                        Swal.fire('Éxito', res.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }, 'json');
            }
        });
    });
});
</script>
