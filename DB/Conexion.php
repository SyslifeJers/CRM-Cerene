<?php

class Database {

    private $servername = "localhost";

    private $username = "cereneUser";

    private $password = "Rtx2080_";

    private $dbname = "crmcerene";

    private $conn;



public function __construct() {
    $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
    if ($this->conn->connect_error) {
        die("Conexión fallida: " . $this->conn->connect_error);
    }
    $this->conn->set_charset("utf8");
}



    public function getConnection() {

        return $this->conn;

    }



    public function closeConnection() {

        if ($this->conn) {

            $this->conn->close();

        }

    }
public function getCursosTable($filtro_activos = true) {
    // Inicializar HTML con DataTable
    $html = '
    <div class="table-responsive">
        <table id="cursosTable" class="table table-striped table-hover table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombre del Curso</th>
                    <th>Fechas</th>
                    <th>Cupo</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>';

    // Construir consulta con filtro opcional
    $query = "SELECT 
                c.id_curso, 
                c.nombre_curso, 
                c.fecha_inicio, 
                c.fecha_fin, 
                c.cupo_maximo,
                c.activo,
                c.clave_curso,
                c.link_inscripcion,
                COUNT(i.id_inscripcion) as inscritos
              FROM cursos c
              LEFT JOIN inscripciones i ON c.id_curso = i.id_curso
              WHERE ".($filtro_activos ? "c.activo = 1" : "1")."
              GROUP BY c.id_curso
              ORDER BY c.fecha_inicio DESC";
    
    $result = $this->conn->query($query);

    // Manejo de errores
    if ($result === false) {
        error_log("Error en consulta: " . $this->conn->error);
        return '<div class="alert alert-danger">Error al cargar cursos: '.htmlspecialchars($this->conn->error).'</div>';
    }

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Calcular cupo disponible
            $cupo_disponible = $row['cupo_maximo'] - $row['inscritos'];
            $porcentaje_ocupado = $row['cupo_maximo'] > 0 ? round(($row['inscritos']/$row['cupo_maximo'])*100) : 0;
            
            // Formatear fechas
            $fecha_inicio = date('d/m/Y', strtotime($row['fecha_inicio']));
            $fecha_fin = date('d/m/Y', strtotime($row['fecha_fin']));
            
            // Determinar estado
            $hoy = date('Y-m-d');
            $estado = '';
            
            if ($row['fecha_inicio'] > $hoy) {
                $estado = '<span class="badge bg-info">Próximo</span>';
            } elseif ($row['fecha_inicio'] <= $hoy && $row['fecha_fin'] >= $hoy) {
                $estado = '<span class="badge bg-success">En curso</span>';
            } else {
                $estado = '<span class="badge bg-secondary">Finalizado</span>';
            }
            
            if (!$row['activo']) {
                $estado = '<span class="badge bg-danger">Inactivo</span>';
            }

            $html .= '
            <tr>
                <td>'.$row["id_curso"].'</td>
                <td>
                    <strong>'.htmlspecialchars($row["nombre_curso"]).'</strong><br>
                    <small class="text-muted">Clave: '.$row['clave_curso'].'</small>
                </td>
                <td>
                    <strong>Inicio:</strong> '.$fecha_inicio.'<br>
                    <strong>Fin:</strong> '.$fecha_fin.'
                </td>
                <td>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar '.($porcentaje_ocupado > 80 ? 'bg-warning' : '').'" 
                             role="progressbar" 
                             style="width: '.$porcentaje_ocupado.'%" 
                             aria-valuenow="'.$porcentaje_ocupado.'" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            '.$porcentaje_ocupado.'%
                        </div>
                    </div>
                    <small>'.$row['inscritos'].' / '.$row['cupo_maximo'].'</small>
                </td>
                <td>'.$estado.'</td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">

                        <a href="editar_curso.php?id='.$row['id_curso'].'" class="btn btn-primary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="/Participantes/index.php?id_curso='.$row['id_curso'].'" class="btn btn-success" title="Inscripciones">
                            <i class="fas fa-users"></i>
                        </a>
                        <button class="btn btn-warning" onclick="copiarEnlace(\''.$row['link_inscripcion'].'\')" title="Copiar enlace">
                            <i class="fas fa-copy"></i>
                        </button>
                        <button class="btn btn-danger" onclick="cambiarEstadoCurso('.$row['id_curso'].','.($row['activo'] ? 0 : 1).')" title="'.($row['activo'] ? 'Desactivar' : 'Activar').'">
                            <i class="fas fa-power-off"></i>
                        </button>
                    </div>
                </td>
            </tr>';
        }
    } else {
        $html .= '<tr><td colspan="6" class="text-center py-4">No se encontraron cursos registrados</td></tr>';
    }

    $html .= '</tbody>
        </table>
    </div>
    ';
    
    return $html;
}
public function getParticipantesTable() {
    $html = '
    <table border="1" id="participantesTable" class="table table-striped table-hover">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Fecha Registro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>';

    $query = "SELECT id_participante, nombre, apellido, email, telefono, fecha_registro FROM participantes";
    $result = $this->conn->query($query);

    // Manejo de errores de consulta
    if ($result === false) {
        error_log("Error en la consulta: " . $this->conn->error);
        return '<div class="alert alert-danger">Error al cargar los participantes: ' . htmlspecialchars($this->conn->error) . '</div>';
    }

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $html .= '
            <tr>
                <td>'.$row["id_participante"].'</td>
                <td>'.htmlspecialchars($row["nombre"]).'</td>
                <td>'.htmlspecialchars($row["apellido"]).'</td>
                <td>'.htmlspecialchars($row["email"]).'</td>
                <td>'.htmlspecialchars($row["telefono"]).'</td>
                <td>'.$row["fecha_registro"].'</td>
                <td>
                    <button class="btn btn-info btn-sm" onclick="verDetalle('.$row['id_participante'].')">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                    <button class="btn btn-warning btn-sm" onclick="editarParticipante('.$row['id_participante'].')">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                </td>
            </tr>';
        }
    } else {
        $html .= '<tr><td colspan="7" class="text-center">No hay participantes registrados</td></tr>';
    }

    $html .= '</tbody></table>';
    
    // Añadir estilo para la tabla (opcional)
    $html .= '
    <style>
        #participantesTable {
            width: 100%;
            margin-top: 20px;
        }
        #participantesTable th {
            background-color: #343a40;
            color: white;
            padding: 10px;
        }
        #participantesTable td {
            padding: 8px;
            vertical-align: middle;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            margin: 2px;
        }
    </style>';

    return $html;
}

public function getUsuariosTable() {
    $html = '
    <div class="table-responsive">
        <table id="usuariosTable" class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>ID</th>
                    <th>Nombre Completo</th>
                    <th>Usuario</th>
                    <th>Estado</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>';

    $query = "SELECT id, name, user, activo, telefono, correo FROM usuarios"; // Excluimos la contraseña por seguridad
    $result = $this->conn->query($query);

    // Manejo de errores
    if ($result === false) {
        error_log("Error en consulta: " . $this->conn->error);
        return '<div class="alert alert-danger">Error al cargar usuarios: ' . htmlspecialchars($this->conn->error) . '</div>';
    }

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $estado = $row["activo"] == 1 ? 
                     '<span class="badge badge-success">Activo</span>' : 
                     '<span class="badge badge-danger">Inactivo</span>';
            
            $html .= '
            <tr>
                <td>'.$row["id"].'</td>
                <td>'.htmlspecialchars($row["name"]).'</td>
                <td>'.htmlspecialchars($row["user"]).'</td>
                <td>'.$estado.'</td>
                <td>'.htmlspecialchars($row["telefono"]).'</td>
                <td>'.htmlspecialchars($row["correo"]).'</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-primary" onclick="editarUsuario('.$row['id'].')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="cambiarEstado('.$row['id'].','.$row['activo'].')">
                        <i class="fas fa-power-off"></i>
                    </button>
                </td>
            </tr>';
        }
    } else {
        $html .= '<tr><td colspan="7" class="text-center">No hay usuarios registrados</td></tr>';
    }

    $html .= '</tbody>
        </table>
    </div>';

    // Estilos y scripts adicionales
    $html .= '
    <style>
        #usuariosTable {
            font-size: 14px;
        }
        #usuariosTable th {
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
        }
        .badge {
            font-size: 85%;
        }
    </style>
    <script>
    function cambiarEstado(id, estadoActual) {
        if(confirm("¿Cambiar estado del usuario?")) {
            // Lógica AJAX para cambiar estado
            fetch("cambiar_estado.php?id="+id+"&estado="+(estadoActual ? 0 : 1))
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert("Error: " + data.message);
                    }
                });
        }
    }
    </script>';

    return $html;
}

public function getInscripcionesTable($id_curso = null) {
    $html = '
    <div class="table-responsive">
        <table id="inscripcionesTable" class="table table-bordered table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>ID Inscripción</th>
                    <th>Participante</th>
                    <th>Fecha Inscripción</th>
                    <th>Estado</th>
                    <th>Método Pago</th>
                    <th>Monto</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>';

    // Consulta base con JOINs
    $query = "SELECT 
                i.id_inscripcion, 
                i.id_curso, 
                i.id_participante,
                DATE_FORMAT(i.fecha_inscripcion, '%d/%m/%Y %H:%i') as fecha_inscripcion,
                i.estado,
                i.metodo_pago,
                i.monto_pagado,
                c.nombre_curso as nombre_curso,
                p.nombre as nombre_participante,
                p.apellido as apellido_participante,
                p.email as email_participante,
                p.telefono as telefono_participante
              FROM inscripciones i
              LEFT JOIN cursos c ON i.id_curso = c.id_curso
              LEFT JOIN participantes p ON i.id_participante = p.id_participante";
    
    // Añadir filtro por curso si se especifica
    if ($id_curso !== null) {
        $query .= " WHERE i.id_curso = " . intval($id_curso);
    }
    
    $query .= " ORDER BY i.fecha_inscripcion DESC";
    
    $result = $this->conn->query($query);

    // Manejo de errores
    if ($result === false) {
        error_log("Error en consulta: " . $this->conn->error);
        return '<div class="alert alert-danger">Error al cargar inscripciones: ' . htmlspecialchars($this->conn->error) . '</div>';
    }

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Estilo según estado
            $badgeClass = [
                'registrado' => 'bg-secondary',
                'comprobante_enviado' => 'bg-info',
                'pago_validado' => 'bg-success',
                'rechazado' => 'bg-danger'
            ];
            
            $html .= '
            <tr>
                <td>'.$row["id_inscripcion"].'</td>
                <td>
                    <strong>'.htmlspecialchars($row["nombre_participante"]).' '.htmlspecialchars($row["apellido_participante"]).'</strong><br>
                    <small class="text-muted">ID: '.$row["id_participante"].'</small><br>
                    <small class="text-muted">'.htmlspecialchars($row["email_participante"]).'</small><br>
                    <small class="text-muted">'.htmlspecialchars($row["telefono_participante"]).'</small>
                </td>
                <td>'.$row["fecha_inscripcion"].'</td>
                <td>
                    <span class="badge '.$badgeClass[$row["estado"]].'">
                        '.ucfirst(str_replace('_', ' ', $row["estado"])).'
                    </span>
                </td>
                <td>'.($row["metodo_pago"] ? htmlspecialchars($row["metodo_pago"]) : 'N/A').'</td>
                <td class="text-end">'.($row["monto_pagado"] ? '$'.number_format($row["monto_pagado"], 2) : 'N/A').'</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-info" onclick="verDetalleInscripcion('.$row['id_inscripcion'].')" title="Ver detalles">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="editarInscripcion('.$row['id_inscripcion'].')" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-warning dropdown-toggle" data-bs-toggle="dropdown" title="Cambiar estado">
                            <i class="fas fa-exchange-alt"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="cambiarEstadoInscripcion('.$row['id_inscripcion'].', \'registrado\')">Registrado</a></li>
                            <li><a class="dropdown-item" href="#" onclick="cambiarEstadoInscripcion('.$row['id_inscripcion'].', \'comprobante_enviado\')">Comprobante Enviado</a></li>
                            <li><a class="dropdown-item" href="#" onclick="cambiarEstadoInscripcion('.$row['id_inscripcion'].', \'pago_validado\')">Pago Validado</a></li>
                            <li><a class="dropdown-item" href="#" onclick="cambiarEstadoInscripcion('.$row['id_inscripcion'].', \'rechazado\')">Rechazado</a></li>
                        </ul>
                    </div>
                </td>
            </tr>';
        }
    } else {
        $html .= '<tr><td colspan="7" class="text-center">No hay inscripciones registradas'.($id_curso ? ' para este curso' : '').'</td></tr>';
    }

    $html .= '</tbody>
        </table>
    </div>';

    return $html;
}
}

?>