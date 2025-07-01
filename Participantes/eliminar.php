<?php
session_start();
require_once '../DB/Conexion.php';
$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Eliminación individual por participante
    if (isset($_POST['id_participante'])) {
        $id_participante = intval($_POST['id_participante']);
        if ($id_participante <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            exit();
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("DELETE FROM inscripciones WHERE id_participante = ?");
            $stmt->bind_param("i", $id_participante);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM participantes WHERE id_participante = ?");
            $stmt->bind_param("i", $id_participante);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Participante eliminado']);
        } catch (Exception $e) {
            $conn->rollback();
            error_log('Error al eliminar participante: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error del servidor']);
        }
        exit();
    }

    // Eliminación masiva por curso
    $id_curso = intval($_POST['id_curso'] ?? 0);
    if ($id_curso <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de curso inválido']);
        exit();
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT DISTINCT id_participante FROM inscripciones WHERE id_curso = ? AND (comprobante_path IS NULL OR comprobante_path = '')");
        $stmt->bind_param("i", $id_curso);
        $stmt->execute();
        $result = $stmt->get_result();
        $participantes = [];
        while ($row = $result->fetch_assoc()) {
            $participantes[] = $row['id_participante'];
        }
        $stmt->close();

        if (empty($participantes)) {
            echo json_encode(['success' => false, 'message' => 'No hay participantes para eliminar']);
            exit();
        }

        $stmtDelIns = $conn->prepare("DELETE FROM inscripciones WHERE id_curso = ? AND id_participante = ?");
        foreach ($participantes as $id_part) {
            $stmtDelIns->bind_param("ii", $id_curso, $id_part);
            $stmtDelIns->execute();
        }
        $stmtDelIns->close();

        $stmtCheck = $conn->prepare("SELECT COUNT(*) AS total FROM inscripciones WHERE id_participante = ?");
        $stmtDelPart = $conn->prepare("DELETE FROM participantes WHERE id_participante = ?");
        foreach ($participantes as $id_part) {
            $stmtCheck->bind_param("i", $id_part);
            $stmtCheck->execute();
            $res = $stmtCheck->get_result()->fetch_assoc();
            if ($res['total'] == 0) {
                $stmtDelPart->bind_param("i", $id_part);
                $stmtDelPart->execute();
            }
        }
        $stmtCheck->close();
        $stmtDelPart->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Participantes eliminados: ' . count($participantes)]);
    } catch (Exception $e) {
        $conn->rollback();
        error_log('Error al eliminar participantes: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
    exit();
}

include '../Modulos/Head.php';

$id_part = isset($_GET['id_participante']) ? intval($_GET['id_participante']) : 0;

if ($id_part > 0) {
    $stmt = $conn->prepare("SELECT nombre, apellido, email, telefono FROM participantes WHERE id_participante = ?");
    $stmt->bind_param("i", $id_part);
    $stmt->execute();
    $resPart = $stmt->get_result();
    $participante = $resPart->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("SELECT c.id_curso, c.nombre_curso FROM inscripciones i JOIN cursos c ON i.id_curso = c.id_curso WHERE i.id_participante = ?");
    $stmt->bind_param("i", $id_part);
    $stmt->execute();
    $cursosPart = $stmt->get_result();
    $stmt->close();
} else {
    $query = "SELECT c.id_curso, c.nombre_curso,
                     (SELECT COUNT(*) FROM inscripciones i WHERE i.id_curso = c.id_curso AND (i.comprobante_path IS NULL OR i.comprobante_path = '')) AS sin_comprobante
               FROM cursos c
               WHERE c.activo = 1
               ORDER BY c.nombre_curso";
    $resultCursos = $conn->query($query);
}
?>
<?php if ($id_part > 0 && $participante): ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Datos del participante</h4>
                </div>
                <div class="card-body">
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($participante['nombre'] . ' ' . $participante['apellido']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($participante['email']); ?></p>
                    <p><strong>Tel&eacute;fono:</strong> <?php echo htmlspecialchars($participante['telefono']); ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Cursos relacionados</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Curso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($c = $cursosPart->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $c['id_curso']; ?></td>
                                    <td><?php echo htmlspecialchars($c['nombre_curso']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <button id="btnEliminarParticipante" data-id="<?php echo $id_part; ?>" class="btn btn-danger mt-3">
                            <i class="fas fa-trash"></i> Eliminar participante
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Eliminar participantes sin comprobante</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="cursosTable">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Curso</th>
                                    <th>Sin comprobante</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($curso = $resultCursos->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $curso['id_curso']; ?></td>
                                        <td><?php echo htmlspecialchars($curso['nombre_curso']); ?></td>
                                        <td><?php echo $curso['sin_comprobante']; ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-danger btn-sm eliminar-participantes" data-id="<?php echo $curso['id_curso']; ?>">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php include '../Modulos/Footer.php'; ?>
<script>
$(function(){
    $('#btnEliminarParticipante').click(function(){
        const idPart = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar participante?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if(result.isConfirmed){
                $.ajax({
                    url: 'eliminar.php',
                    type: 'POST',
                    data: {id_participante: idPart},
                    dataType: 'json',
                    success: function(res){
                        if(res.success){
                            Swal.fire('Éxito', res.message, 'success');
                            setTimeout(() => window.location.href = 'index.php', 1000);
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function(){
                        Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                    }
                });
            }
        });
    });
    $('.eliminar-participantes').click(function(){
        const idCurso = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar inscritos sin comprobante?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if(result.isConfirmed){
                $.ajax({
                    url: 'eliminar.php',
                    type: 'POST',
                    data: {id_curso: idCurso},
                    dataType: 'json',
                    success: function(res){
                        if(res.success){
                            Swal.fire('Éxito', res.message, 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function(){
                        Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                    }
                });
            }
        });
    });
});
</script>
