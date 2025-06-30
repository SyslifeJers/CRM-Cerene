<?php
require_once '../DB/Conexion.php';
$database = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_curso = $_POST['nombre_curso'];
    $descripcion = $_POST['descripcion'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $costo = (float)$_POST['costo']; // Asegurar que es float
    $cupo_maximo = (int)$_POST['cupo_maximo']; // Asegurar que es int
    $requiere_pago = isset($_POST['requiere_pago']) ? 1 : 0;
    
    // Generar clave única para el curso
    $clave_curso = substr(strtoupper(uniqid()), 0, 8);
    
    try {
        // Consulta corregida con parámetros exactos
        $stmt = $database->getConnection()->prepare("INSERT INTO cursos 
            (nombre_curso, descripcion, fecha_inicio, fecha_fin, costo, cupo_maximo, clave_curso, requiere_pago) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Cadena de tipos corregida (7 parámetros: 5 strings, 1 double, 1 integer, 1 integer)
        $stmt->bind_param("ssssdisi", 
            $nombre_curso, 
            $descripcion, 
            $fecha_inicio, 
            $fecha_fin, 
            $costo, 
            $cupo_maximo, 
            $clave_curso, 
            $requiere_pago
        );
        
        if ($stmt->execute()) {
            $id_curso = $stmt->insert_id;
            $enlace_registro = "http://".$_SERVER['HTTP_HOST']."/Registro.php?clave=".$clave_curso;
            
            // Actualizar el link_inscripcion
            $update_stmt = $database->getConnection()->prepare("UPDATE cursos SET link_inscripcion = ? WHERE id_curso = ?");
            $update_stmt->bind_param("si", $enlace_registro, $id_curso);
            $update_stmt->execute();
            
            $_SESSION['mensaje_exito'] = "Curso registrado exitosamente!<br>
                            <strong>Clave del curso:</strong> $clave_curso<br>
                            <strong>Enlace de registro:</strong> <a href='$enlace_registro' target='_blank'>$enlace_registro</a>";
                                        sleep(5); // pausa de 5 segundos
            header("Location: index.php");
            exit();
        }
    } catch (Exception $e) {
        $error = "Error al registrar el curso: " . $e->getMessage();
    }
}
?>
<?php include '../Modulos/Head.php'; ?>

 
     <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-book"></i> Registrar Nuevo Curso</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($mensaje_exito)): ?>
                            <div class="alert alert-success"><?= $mensaje_exito ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="form-section">
                                <h4><i class="fas fa-info-circle"></i> Información Básica</h4>
                                <div class="mb-3">
                                    <label for="nombre_curso" class="form-label">Nombre del Curso*</label>
                                    <input type="text" class="form-control" id="nombre_curso" name="nombre_curso" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h4><i class="fas fa-calendar-alt"></i> Fechas</h4>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="fecha_inicio" class="form-label">Fecha de Inicio*</label>
                                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="fecha_fin" class="form-label">Fecha de Fin*</label>
                                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h4><i class="fas fa-money-bill-wave"></i> Costo</h4>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="costo" class="form-label">Costo ($)*</label>
                                        <input type="number" step="0.01" class="form-control" id="costo" name="costo" min="0" required>
                                    </div>
                                    <div class="col-md-6 mb-3 d-flex align-items-end">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="requiere_pago" name="requiere_pago" value="1">
                                            <label class="form-check-label" for="requiere_pago">Requiere pago</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-section">
                                <h4><i class="fas fa-users"></i> Cupo</h4>
                                <div class="mb-3">
                                    <label for="cupo_maximo" class="form-label">Número máximo de participantes*</label>
                                    <input type="number" class="form-control" id="cupo_maximo" name="cupo_maximo" min="1" value="30" required>
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> Registrar Curso
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php include '../Modulos/Footer.php'; ?>