<?php

class Database
{
    private $servername = "localhost";
    private $username = "u529445062_jers";
    private $password = "Rtx2080_";
    private $dbname = "u529445062_cenere";
    private $conn;


    public function __construct()
    {
        $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
        if ($this->conn->connect_error) {
            die("Conexión fallida: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8");
    }


    public function getConnection()
    {
        return $this->conn;
    }


    public function closeConnection()
    {

        if ($this->conn) {

            $this->conn->close();
        }
    }

    public function generarClaveCertificadoInscripcion($id_inscripcion)
    {
        $id_inscripcion = (int) $id_inscripcion;
        if ($id_inscripcion <= 0) {
            return null;
        }

        $stmt = $this->conn->prepare("SELECT i.id_participante, c.clave_certificado
            FROM inscripciones i
            INNER JOIN cursos c ON c.id_curso = i.id_curso
            WHERE i.id_inscripcion = ?");
        $stmt->bind_param("i", $id_inscripcion);
        $stmt->execute();
        $registro = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$registro) {
            return null;
        }

        $prefijo = trim((string) $registro['clave_certificado']);
        $clave = $prefijo !== ''
            ? $prefijo . '-' . str_pad((string) $registro['id_participante'], 4, '0', STR_PAD_LEFT)
            : null;

        $stmt = $this->conn->prepare("UPDATE inscripciones SET clave_certificado = ? WHERE id_inscripcion = ?");
        $stmt->bind_param("si", $clave, $id_inscripcion);
        $stmt->execute();
        $stmt->close();

        return $clave;
    }

    public function regenerarClavesCertificadoCurso($id_curso)
    {
        $id_curso = (int) $id_curso;
        if ($id_curso <= 0) {
            return 0;
        }

        $stmt = $this->conn->prepare("SELECT clave_certificado FROM cursos WHERE id_curso = ?");
        $stmt->bind_param("i", $id_curso);
        $stmt->execute();
        $curso = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$curso || trim((string) $curso['clave_certificado']) === '') {
            $stmt = $this->conn->prepare("UPDATE inscripciones SET clave_certificado = NULL WHERE id_curso = ?");
            $stmt->bind_param("i", $id_curso);
            $stmt->execute();
            $afectadas = $stmt->affected_rows;
            $stmt->close();

            return $afectadas;
        }

        $prefijo = trim((string) $curso['clave_certificado']);
        $stmt = $this->conn->prepare("UPDATE inscripciones
            SET clave_certificado = CONCAT(?, '-', LPAD(id_participante, 4, '0'))
            WHERE id_curso = ?");
        $stmt->bind_param("si", $prefijo, $id_curso);
        $stmt->execute();
        $afectadas = $stmt->affected_rows;
        $stmt->close();

        return $afectadas;
    }

    public function getCursosTable($filtro_activos = true)
    {
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
                c.clave_certificado,
                c.link_inscripcion,
                COUNT(i.id_inscripcion) as inscritos,
                c.requiere_pago
              FROM cursos c
              LEFT JOIN inscripciones i ON c.id_curso = i.id_curso
              WHERE " . ($filtro_activos ? "c.activo = 1" : "1") . "
              GROUP BY c.id_curso
              ORDER BY c.id_curso DESC";

        $result = $this->conn->query($query);

        // Manejo de errores
        if ($result === false) {
            error_log("Error en consulta: " . $this->conn->error);
            return '<div class="alert alert-danger">Error al cargar cursos: ' . htmlspecialchars($this->conn->error) . '</div>';
        }

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Calcular cupo disponible
                $cupo_disponible = $row['cupo_maximo'] - $row['inscritos'];
                $porcentaje_ocupado = $row['cupo_maximo'] > 0 ? round(($row['inscritos'] / $row['cupo_maximo']) * 100) : 0;

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
                <td>' . $row["id_curso"] . '</td>
                <td>
                    <strong>' . htmlspecialchars($row["nombre_curso"]) . '</strong><br>
                    <small class="text-muted">Clave: ' . htmlspecialchars($row['clave_curso']) . '</small>' .
                    (!empty($row['clave_certificado']) ? '<br><small class="text-muted">Certificado: ' . htmlspecialchars($row['clave_certificado']) . '-0000</small>' : '') . '
                </td>
                <td>
                    <strong>Inicio:</strong> ' . $fecha_inicio . '<br>
                    <strong>Fin:</strong> ' . $fecha_fin . '
                </td>
                <td>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar ' . ($porcentaje_ocupado > 80 ? 'bg-warning' : '') . '" 
                             role="progressbar" 
                             style="width: ' . $porcentaje_ocupado . '%" 
                             aria-valuenow="' . $porcentaje_ocupado . '" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            ' . $porcentaje_ocupado . '%
                        </div>
                    </div>
                    <small>' . $row['inscritos'] . ' / ' . $row['cupo_maximo'] . '</small>
                </td>
                <td>' . $estado . '</td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">

                        <a href="editar.php?id=' . $row['id_curso'] . '" class="btn btn-primary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="/Participantes/index.php?id_curso=' . $row['id_curso'] . '" class="btn btn-success" title="Inscripciones">
                            <i class="fas fa-users"></i>
                        </a>
                        <button class="btn btn-warning" onclick="copiarEnlace(\'' . $row['link_inscripcion'] . '\')" title="Copiar enlace">
                            <i class="fas fa-copy"></i>
                        </button>
                        <button class="btn btn-danger" onclick="cambiarEstadoCurso(' . $row['id_curso'] . ',' . ($row['activo'] ? 0 : 1) . ')" title="' . ($row['activo'] ? 'Desactivar' : 'Activar') . '">
                            <i class="fas fa-power-off"></i>
                        </button>
                        <a href="detalle.php?id=' . $row['id_curso'] . '" class="btn btn-secondary" title="Detalle">
                            <i class="fas fa-info-circle"></i>
                        </a>
                        <a href="curso.php?id=' . $row['id_curso'] . '" class="btn btn-info" title="Ver curso">
                            <i class="fas fa-eye"></i>
                        </a>
                        
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
    public function getParticipantesTable()
    {
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
                <td>' . $row["id_participante"] . '</td>
                <td>' . htmlspecialchars($row["nombre"]) . '</td>
                <td>' . htmlspecialchars($row["apellido"]) . '</td>
                <td>' . htmlspecialchars($row["email"]) . '</td>
                <td>' . htmlspecialchars($row["telefono"]) . '</td>
                <td>' . $row["fecha_registro"] . '</td>
                <td>
                    <button class="btn btn-info btn-sm" onclick="verDetalle(' . $row['id_participante'] . ')">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                    <button class="btn btn-warning btn-sm" onclick="editarParticipante(' . $row['id_participante'] . ')">
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

    public function getUsuariosTable()
    {
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
                <td>' . $row["id"] . '</td>
                <td>' . htmlspecialchars($row["name"]) . '</td>
                <td>' . htmlspecialchars($row["user"]) . '</td>
                <td>' . $estado . '</td>
                <td>' . htmlspecialchars($row["telefono"]) . '</td>
                <td>' . htmlspecialchars($row["correo"]) . '</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-primary" onclick="editarUsuario(' . $row['id'] . ')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="cambiarEstado(' . $row['id'] . ',' . $row['activo'] . ')">
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

    public function getInscripcionesTable($id_curso = null)
    {
        $html = '
    <div class="table-responsive">
        <table id="inscripcionesTable" class="table table-bordered table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Participante</th>
                    <th>Fecha Inscripción</th>
                    <th>Estado</th>
                    <th>Pago</th>
                    <th>Comprobante</th>
                    <th class="text-center" style="min-width: 110px;">Documento</th>
                    <th class="text-center" style="min-width: 190px;">Certificado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>';

        $tieneCertificadosEnvios = false;
        $certificadosTableResult = $this->conn->query("SHOW TABLES LIKE 'certificados_envios'");
        if ($certificadosTableResult && $certificadosTableResult->num_rows > 0) {
            $tieneCertificadosEnvios = true;
        }

        // Consulta mejorada para incluir comprobante_path
        $estatusCertificadoSelect = 'i.estatus_certificado';
        $claveCertificadoInscripcionSelect = 'i.clave_certificado AS clave_certificado_inscripcion';
        $certificadoEnvioSelect = $tieneCertificadosEnvios
            ? 'cert.estatus AS certificado_envio_estatus, cert.mensaje AS certificado_envio_mensaje, cert.nombre_archivo AS certificado_nombre_archivo, cert.fecha_generacion AS certificado_fecha_generacion, cert.fecha_envio AS certificado_fecha_envio'
            : 'NULL AS certificado_envio_estatus, NULL AS certificado_envio_mensaje, NULL AS certificado_nombre_archivo, NULL AS certificado_fecha_generacion, NULL AS certificado_fecha_envio';
        $certificadoEnvioJoin = $tieneCertificadosEnvios
            ? "LEFT JOIN (
                    SELECT ce.*
                    FROM certificados_envios ce
                    INNER JOIN (
                        SELECT id_inscripcion, MAX(id_envio) AS id_envio
                        FROM certificados_envios
                        GROUP BY id_inscripcion
                    ) ult ON ult.id_envio = ce.id_envio
                ) cert ON cert.id_inscripcion = i.id_inscripcion"
            : '';
        $query = "SELECT 
                i.id_inscripcion, 
                i.id_curso,
                i.id_participante,
                i.IdOpcionPago as id_opcion_pago,
                i.estado,
                $estatusCertificadoSelect,
                $claveCertificadoInscripcionSelect,
                i.metodo_pago,
                i.monto_pagado,
                i.nota,
                i.comprobante_path,
                i.fecha_inscripcion,
                i.fecha_cambio_estado,
                pagos.metodos_pago,
                pagos.monto_reportado,
                pagos.monto_validado,
                $certificadoEnvioSelect,
                c.nombre_curso,
                p.nombre as nombre_participante,
                p.apellido as apellido_participante,
                p.email as email_participante,
                p.telefono as telefono_participante,
                p.documento,
                p.titulo
              FROM inscripciones i
              LEFT JOIN cursos c ON i.id_curso = c.id_curso
              LEFT JOIN participantes p ON i.id_participante = p.id_participante
              LEFT JOIN (
                    SELECT
                        id_inscripcion,
                        GROUP_CONCAT(DISTINCT metodo_pago ORDER BY metodo_pago SEPARATOR ', ') AS metodos_pago,
                        SUM(IFNULL(monto_pagado, 0)) AS monto_reportado,
                        SUM(CASE WHEN validado = 1 THEN IFNULL(monto_pagado, 0) ELSE 0 END) AS monto_validado
                    FROM comprobantes_inscripcion
                    GROUP BY id_inscripcion
              ) pagos ON pagos.id_inscripcion = i.id_inscripcion
              $certificadoEnvioJoin";

        if ($id_curso !== null) {
            $query .= " WHERE i.id_curso = " . intval($id_curso);
        }

        $query .= " ORDER BY i.fecha_inscripcion DESC";

        $result = $this->conn->query($query);

        if ($result === false) {
            error_log("Error en consulta: " . $this->conn->error);
            return '<div class="alert alert-danger">Error al cargar inscripciones: ' . htmlspecialchars($this->conn->error) . '</div>';
        }

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
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

                // Formatear fechas
                $fecha_inscripcion = date('d/m/Y', strtotime($row['fecha_inscripcion']));
                $fecha_cambio = $row['fecha_cambio_estado'] ? date('d/m/Y H:i', strtotime($row['fecha_cambio_estado'])) : 'N/A';

                // Botón o enlace de comprobante según la opción de pago
                if ($row['id_opcion_pago']) {
                    // Múltiples pagos: ir a pantalla de gestión
                                        $pendientes = 0;
                    $pendStmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM comprobantes_inscripcion WHERE id_inscripcion = ? AND validado = 0");
                    $pendStmt->bind_param("i", $row['id_inscripcion']);
                    if ($pendStmt->execute()) {
                        $resPend = $pendStmt->get_result();
                        if ($pendRow = $resPend->fetch_assoc()) {
                            $pendientes = (int) $pendRow['cnt'];
                        }
                    }
                    $pendStmt->close();

                    $badgePendiente = $pendientes > 0 ? ' <span class="text-warning" title="Pagos pendientes por validar"><i class="fas fa-exclamation-circle"></i></span>'. $pendientes .' Revisar' : '';
                    $botonComprobante = $badgePendiente .'<a href="pagos.php?id=' . $row['id_inscripcion'] . '" class="btn btn-sm btn-info"><i class="fas fa-file-invoice"></i> Ver pagos programados</a>';
                } else {
                    // Pago único: mostrar visor modal existente
                    $fechaPago = ($row['estado'] === 'pago_validado' && !empty($row['fecha_cambio_estado']))
                        ? date('Y-m-d', strtotime($row['fecha_cambio_estado']))
                        : '';
                    if ($row['comprobante_path']) {
                        $archivo = htmlspecialchars($row['comprobante_path']);
                        $monto = htmlspecialchars((string) ($row['monto_pagado'] ?? ''));
                        $fecha = htmlspecialchars($fechaPago);
                        $idInscripcion = (int) $row['id_inscripcion'];
                        $botonComprobante = "<button class=\"btn btn-sm btn-info\" onclick=\"verComprobante(" . $idInscripcion . ", '" . $archivo . "', '" . $monto . "', '" . $fecha . "')\"><i class=\"fas fa-file-invoice\"></i> Ver</button>";
                    } else {
                        $botonComprobante = 'N/A';
                    }
                }
                $Pagopendiente = '';
                    if($row['estado'] == 'comprobante_enviado') {
                        // Pago único: mostrar monto pagado
                        $Pagopendiente = '<span class="text-warning" title="Pagos pendientes por validar"><i class="fas fa-exclamation-circle"></i></span> 1 por revisar';
                    } 
                $estatusCertificado = $row['estatus_certificado'] ?: 'pendiente';
                $certificadoLabels = [
                    'pendiente' => 'Pendiente',
                    'enviado' => 'Enviado',
                    'problema' => 'Tiene problema',
                    'reenviado' => 'Reenviado'
                ];
                $certificadoClases = [
                    'pendiente' => 'bg-secondary',
                    'enviado' => 'bg-success',
                    'problema' => 'bg-danger',
                    'reenviado' => 'bg-info'
                ];
                $certificadoLabel = $certificadoLabels[$estatusCertificado] ?? $estatusCertificado;
                $certificadoClase = $certificadoClases[$estatusCertificado] ?? 'bg-secondary';

                $estatusEnvioCertificado = trim((string) ($row['certificado_envio_estatus'] ?? ''));
                $estatusEnvioKey = strtolower($estatusEnvioCertificado);
                $envioCertificadoClases = [
                    'enviado' => 'bg-success',
                    'reenviado' => 'bg-info',
                    'generado' => 'bg-primary',
                    'pendiente' => 'bg-warning text-dark',
                    'error' => 'bg-danger',
                    'fallido' => 'bg-danger',
                    'problema' => 'bg-danger'
                ];
                $envioCertificadoClase = $envioCertificadoClases[$estatusEnvioKey] ?? ($estatusEnvioCertificado !== '' ? 'bg-secondary' : 'bg-warning text-dark');
                $envioCertificadoLabel = $estatusEnvioCertificado !== '' ? ucfirst($estatusEnvioCertificado) : 'Sin envío';
                $fechaEnvioCertificado = !empty($row['certificado_fecha_envio'])
                    ? date('d/m/Y H:i', strtotime($row['certificado_fecha_envio']))
                    : (!empty($row['certificado_fecha_generacion']) ? date('d/m/Y H:i', strtotime($row['certificado_fecha_generacion'])) : '');
                $archivoCertificado = !empty($row['certificado_nombre_archivo'])
                    ? '<small class="d-block text-muted">' . htmlspecialchars($row['certificado_nombre_archivo']) . '</small>'
                    : '';
                $mensajeCertificadoTexto = (string) ($row['certificado_envio_mensaje'] ?? '');
                $mensajeCertificadoCorto = strlen($mensajeCertificadoTexto) > 60
                    ? substr($mensajeCertificadoTexto, 0, 60) . '...'
                    : $mensajeCertificadoTexto;
                $mensajeCertificado = $mensajeCertificadoTexto !== ''
                    ? '<small class="d-block text-muted" title="' . htmlspecialchars($mensajeCertificadoTexto, ENT_QUOTES) . '">' . htmlspecialchars($mensajeCertificadoCorto) . '</small>'
                    : '';
                $claveCertificadoControl = !empty($row['clave_certificado_inscripcion'])
                    ? '<small class="d-block text-primary fw-bold mt-1">Clave: ' . htmlspecialchars($row['clave_certificado_inscripcion']) . '</small>'
                    : '';

                if ($row['id_opcion_pago']) {
                    $metodosPago = $row['metodos_pago'] ?: 'Pagos diferidos';
                    $montoPago = $row['monto_reportado'] !== null ? (float) $row['monto_reportado'] : 0;
                    $montoValidado = $row['monto_validado'] !== null ? (float) $row['monto_validado'] : 0;
                    $pagoHtml = '<div><i class="fas fa-calendar-alt text-info"></i> ' . htmlspecialchars($metodosPago) . '</div>';
                    $pagoHtml .= '<strong>' . ($montoPago > 0 ? '$' . number_format($montoPago, 2) : 'N/A') . '</strong>';
                    if ($montoValidado > 0) {
                        $pagoHtml .= '<small class="d-block text-success">Validado: $' . number_format($montoValidado, 2) . '</small>';
                    }
                } else {
                    $pagoHtml = $row['metodo_pago']
                        ? '<div>' . htmlspecialchars($row['metodo_pago']) . '</div>'
                        : '<span class="text-muted"><i class="fas fa-ban"></i> N/A</span>';
                    $pagoHtml .= '<strong class="d-block">' . ($row['monto_pagado'] ? '$' . number_format($row['monto_pagado'], 2) : 'N/A') . '</strong>';
                }

                // Documento de estudios
                $botonDocumento = $row['documento']
                    ? '<a href="../documentos/' . htmlspecialchars($row['documento'], ENT_QUOTES) . '" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-file"></i> Ver</a>'
                    : '<span class="text-danger">No subido</span>';

                $puedeIntercambiarArchivos = isset($_SESSION['rol'])
                    && (int) $_SESSION['rol'] === 3
                    && empty($row['id_opcion_pago'])
                    && (!empty($row['comprobante_path']) || !empty($row['documento']));

                $puedeSubirComprobanteAdmin = isset($_SESSION['idAdmin'])
                    && empty($row['id_opcion_pago'])
                    && empty($row['comprobante_path']);

                if ($puedeSubirComprobanteAdmin) {
                    $botonComprobante = '<button class="btn btn-sm btn-dark subir-comprobante-admin" data-id="' . (int) $row['id_inscripcion'] . '" data-participante="' . htmlspecialchars($row["nombre_participante"] . ' ' . $row["apellido_participante"], ENT_QUOTES) . '" title="Subir comprobante como admin">
                            <i class="fas fa-upload"></i> Subir
                        </button>';
                }

                $html .= '
            <tr>
                <td>' . $row["id_inscripcion"] . '</td>
                <td>
                    <a href="resumen.php?id_participante=' . (int) $row['id_participante'] . '&id_curso=' . (int) $row['id_curso'] . '" class="text-decoration-none">
                        <strong>'. htmlspecialchars($row["titulo"]) . ' ' . htmlspecialchars($row["nombre_participante"]) . ' ' . htmlspecialchars($row["apellido_participante"]) . '</strong>
                    </a><br>
                     <small class="text-muted">' . $row["email_participante"] . '</small>
                    <small class="text-muted">' . $row["telefono_participante"] . '</small><hr>
                    <small class="text-muted">' . $row["id_participante"] . '</small>
                </td>
                <td>' . $fecha_inscripcion . '</td>
                <td>
                    <span class="badge ' . $badgeClass[$row["estado"]] . '">
                        ' . $row["estado"] . '
                    </span><br>
                    <small>' . $fecha_cambio . '</small>
                </td>
                <td class="text-end">' . $pagoHtml . '</td>
                <td class="text-center">' 
                . $Pagopendiente . ' ' .
                $botonComprobante . '</td>
                <td class="text-center align-middle">' . $botonDocumento . '</td>
                <td class="text-center align-middle" style="min-width: 190px;">
                    <div class="certificado-status">
                    <span class="badge ' . $envioCertificadoClase . '">' . htmlspecialchars($envioCertificadoLabel) . '</span>
                    ' . ($fechaEnvioCertificado ? '<small class="d-block text-muted">' . $fechaEnvioCertificado . '</small>' : '') . '
                    ' . $archivoCertificado . '
                    ' . $mensajeCertificado . '
                    ' . $claveCertificadoControl . '
                    </div>
                    <div class="mt-2 pt-2 border-top">
                        <label class="form-label small text-muted mb-1">Control interno</label>
                        <select class="form-select form-select-sm estatus-certificado" data-id="' . (int) $row['id_inscripcion'] . '">
                        <option value="pendiente" ' . ($estatusCertificado === 'pendiente' ? 'selected' : '') . '>Pendiente</option>
                        <option value="enviado" ' . ($estatusCertificado === 'enviado' ? 'selected' : '') . '>Enviado</option>
                        <option value="problema" ' . ($estatusCertificado === 'problema' ? 'selected' : '') . '>Tiene problema</option>
                        <option value="reenviado" ' . ($estatusCertificado === 'reenviado' ? 'selected' : '') . '>Reenviado</option>
                        </select>
                    </div>
                </td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <button style="display: none;" class="btn btn-primary" onclick="editarInscripcion(' . $row['id_inscripcion'] . ')" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-info nota-btn" data-id="' . $row['id_inscripcion'] . '" data-nota="' . $row['nota'] . '" title="Nota">
                            <i class="fas fa-pen"></i>
                        </button>';

                if ($puedeIntercambiarArchivos) {
                    $html .= '<button class="btn btn-secondary intercambiar-archivos" data-id="' . $row['id_inscripcion'] . '" title="Intercambiar documento y comprobante">
                            <i class="fas fa-retweet"></i>
                        </button>';
                }

                if (!$row['id_opcion_pago'] || 1 == 1) {
                    $html .= '<button class="btn btn-success asignar-opcion" data-id="' . $row['id_inscripcion'] . '">
                            <i class="fas fa-calendar-plus"></i>
                        </button>';
                }

                $html .= '
                        <div class="btn-group">
                            <button type="button" class="btn btn-warning dropdown-toggle" data-bs-toggle="dropdown" title="Cambiar estado">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item cambiar-estado" href="#" data-id="' . $row['id_inscripcion'] . '" data-estado="registrado">Registrado</a></li>
                                <li><a class="dropdown-item cambiar-estado" href="#" data-id="' . $row['id_inscripcion'] . '" data-estado="pendiente_pago">Pendiente Pago</a></li>
                                <li><a class="dropdown-item cambiar-estado" href="#" data-id="' . $row['id_inscripcion'] . '" data-estado="comprobante_enviado">Comprobante Enviado</a></li>
                                <li><a class="dropdown-item cambiar-estado" href="#" data-id="' . $row['id_inscripcion'] . '" data-estado="pago_validado">Pago Validado</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item rechazar-inscripcion" href="#" data-id="' . $row['id_inscripcion'] . '">Rechazar</a></li>
                            </ul>
                            <a href="eliminar.php?id_participante=' . $row['id_participante'] . '" class="btn btn-danger mb-3 ms-2">
                                <i class="fas fa-user-slash"></i> Eliminar participantes
                            </a>
                        </div>
                    </div>
                </td>
            </tr>';
            }
        } else {
            $html .= '<tr><td colspan="9" class="text-center">No hay inscripciones registradas' . ($id_curso ? ' para este curso' : '') . '</td></tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    public function getContenidoCursoTable($id_curso)
    {
        $html = '
    <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Tipo</th>
                    <th>Publicación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>';

        $query = "SELECT 
                id_contenido, 
                titulo, 
                tipo_contenido, 
                fecha_publicacion,
                archivo_ruta,
                enlace_url,
                PagoPorce
              FROM contenido_curso
              WHERE id_curso = ?
              ORDER BY orden, fecha_publicacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id_curso);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false) {
            error_log("Error en consulta: " . $this->conn->error);
            return '<div class="alert alert-danger">Error al cargar contenido: ' . htmlspecialchars($this->conn->error) . '</div>';
        }

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Determinar icono según tipo de contenido
                $icono = '';
                switch ($row['tipo_contenido']) {
                    case 'documento':
                        $icono = '<i class="fas fa-file-pdf text-danger"></i>';
                        break;
                    case 'video':
                        $icono = '<i class="fas fa-video text-primary"></i>';
                        break;
                    case 'enlace':
                        $icono = '<i class="fas fa-link text-success"></i>';
                        break;
                    case 'presentacion':
                        $icono = '<i class="fas fa-presentation text-warning"></i>';
                        break;
                    case 'tarea':
                        $icono = '<i class="fas fa-tasks text-info"></i>';
                        break;
                }

                // Formatear fecha
                $fecha_publicacion = date('d/m/Y H:i', strtotime($row['fecha_publicacion']));

                // Determinar enlace o acción
                $recurso = $row['enlace_url'] ?: $row['archivo_ruta'];
                $accion = $row['enlace_url']
                    ? 'onclick="window.open(\'' . htmlspecialchars($row['enlace_url']) . '\', \'_blank\')"'
                    : 'href="/' . htmlspecialchars($row['archivo_ruta']) . '" download';

                $html .= '
            <tr>
                <td>' . $row["id_contenido"] . '</td>
                <td>' . htmlspecialchars($row["titulo"]) . ' (' . htmlspecialchars($row["PagoPorce"]) . ')</td>
                <td>' . $icono . ' ' . ucfirst($row['tipo_contenido']) . '</td>
                <td>' . $fecha_publicacion . '</td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <a ' . $accion . ' class="btn btn-primary" title="Ver/Descargar">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="contenido/editar.php?id=' . $row['id_contenido'] . '" class="btn btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-danger" onclick="eliminarContenido(' . $row['id_contenido'] . ')" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>';
            }
        } else {
            $html .= '<tr><td colspan="5" class="text-center py-4">No hay contenido disponible para este curso</td></tr>';
        }

        $html .= '</tbody>
        </table>
    </div>';

        return $html;
    }
    public function getReunionesZoomTable($id_curso) {
    $html = '
    <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Título</th>
                    <th>Fecha y Hora</th>
                    <th>Duración</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>';

    $query = "SELECT 
                id_reunion,
                titulo,
                fecha_hora,
                duracion_minutos,
                url_zoom,
                codigo_acceso,
                grabacion_url
              FROM reuniones_zoom
              WHERE id_curso = ?
              ORDER BY fecha_hora DESC";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("i", $id_curso);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        error_log("Error en consulta: " . $this->conn->error);
        return '<div class="alert alert-danger">Error al cargar reuniones: '.htmlspecialchars($this->conn->error).'</div>';
    }

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Formatear fecha y hora
            $fecha_hora = date('d/m/Y H:i', strtotime($row['fecha_hora']));
            
            // Determinar estado de la reunión
            $ahora = new DateTime();
            $fecha_reunion = new DateTime($row['fecha_hora']);
            $fin_reunion = (clone $fecha_reunion)->add(new DateInterval('PT'.$row['duracion_minutos'].'M'));
            
            $estado = '';
            if ($ahora < $fecha_reunion) {
                $estado = '<span class="badge bg-info">Programada</span>';
            } elseif ($ahora >= $fecha_reunion && $ahora <= $fin_reunion) {
                $estado = '<span class="badge bg-success">En vivo</span>';
            } else {
                $estado = '<span class="badge bg-secondary">Finalizada</span>';
            }
            
            // Botón de acceso según estado
            $boton_acceso = '';
            if ($ahora >= $fecha_reunion && $ahora <= $fin_reunion) {
                $boton_acceso = '<a href="'.htmlspecialchars($row['url_zoom']).'" target="_blank" class="btn btn-success btn-sm">
                    <i class="fas fa-video"></i> Unirse
                </a>';
            } elseif ($row['grabacion_url']) {
                $boton_acceso = '<a href="'.htmlspecialchars($row['grabacion_url']).'" target="_blank" class="btn btn-info btn-sm">
                    <i class="fas fa-play-circle"></i> Ver grabación
                </a>';
            }

            $html .= '
            <tr>
                <td>'.htmlspecialchars($row["titulo"]).'</td>
                <td>'.$fecha_hora.'</td>
                <td>'.$row['duracion_minutos'].' minutos</td>
                <td>'.$estado.'</td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                        '.$boton_acceso.'
                        <button class="btn btn-primary" onclick="copiarEnlace(\''.htmlspecialchars($row['url_zoom']).'\')" title="Copiar enlace">
                            <i class="fas fa-copy"></i>
                        </button>
                        <a href="reuniones/editar.php?id='.$row['id_reunion'].'" class="btn btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-danger" onclick="eliminarReunion('.$row['id_reunion'].', '. $id_curso .')" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>';
        }
    } else {
        $html .= '<tr><td colspan="5" class="text-center py-4">No hay reuniones programadas para este curso</td></tr>';
    }

    $html .= '</tbody>
        </table>
    </div>';
    
    return $html;
}
public function getCursoById($id_curso) {
    $query = "SELECT 
                id_curso, 
                nombre_curso, 
                descripcion, 
                fecha_inicio, 
                fecha_fin, 
                costo,
                cupo_maximo,
                activo,
                link_inscripcion,
                clave_curso,
                requiere_pago
              FROM cursos
              WHERE id_curso = ?";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("i", $id_curso);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        error_log("Error al obtener curso: " . $this->conn->error);
        return false;
    }
}


public function getOpcionesPagoCurso() {
    $stmt = $this->conn->prepare("SELECT `id_opcion`, `numero_pagos`, a.`id_frecuencia`,f.tipo, `activo`, `costo_adicional`, `nota` FROM `opciones_pago` a INNER join frecuencia_pago f on f.id_frecuencia = a.id_frecuencia WHERE `activo` = 1;");
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}
public function getContenidoCursoParticipante($id_curso) {
    $query = "SELECT
                id_contenido,
                titulo,
                descripcion,
                tipo_contenido,
                archivo_ruta,
                enlace_url,
                fecha_publicacion,
                PagoPorce
              FROM contenido_curso
              WHERE id_curso = ?
              ORDER BY orden, fecha_publicacion DESC";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("i", $id_curso);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

public function getReunionesZoomParticipante($id_curso) {
    $query = "SELECT
                id_reunion,
                titulo,
                descripcion,
                fecha_hora,
                duracion_minutos,
                url_zoom,
                codigo_acceso,
                grabacion_url,
                PagoPorce
              FROM reuniones_zoom
              WHERE id_curso = ?
              ORDER BY fecha_hora DESC";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("i", $id_curso);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}
} 
