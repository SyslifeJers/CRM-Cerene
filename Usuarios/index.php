<?php

include '../Modulos/Head.php';

$rol = $_SESSION['rol'];
?>

<div class="container mt-4">
    <h2 class="mb-4">Lista de Usuarios</h2>

    <?php if ($rol == 3): ?>
    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#exampleModal">
        Registro de Usuario
    </button>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-sm table-bordered" id="myTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <?php if ($rol == 3): ?><th>Contraseña</th><?php endif; ?>
                    <th>Activo</th>
                    <th>Registro</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <?php if ($rol == 3): ?><th>Acciones</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                // Conexión
                $host = 'localhost';
                $db = 'clini234_cerene';
                $user = 'clini234_cerene';
                $pass = 'tu{]ScpQ-Vcg';
                $charset = 'utf8mb4';

                $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                try {
                    $pdo = new PDO($dsn, $user, $pass, $options);
                } catch (\PDOException $e) {
                    die("Error de conexión: " . $e->getMessage());
                }

                $sql = "SELECT usua.`id`, usua.`name`, `user`, `pass`, usua.`activo`, `registro`, `telefono`, `correo`, r.name AS rol 
                        FROM `Usuarios` usua 
                        INNER JOIN Rol r ON r.id = usua.IdRol WHERE usua.IdRol in (3,4)";
                $stmt = $pdo->query($sql);

                while ($row = $stmt->fetch()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['user']) . '</td>';
                    if ($rol == 3) {
                        echo '<td>' . htmlspecialchars($row['pass']) . '</td>';
                    }
                    echo '<td>' . ($row["activo"] == 1 ? 'Sí' : 'No') . '</td>';
                    echo '<td>' . htmlspecialchars($row['registro']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['telefono']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['correo']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['rol']) . '</td>';

                    if ($rol == 3) {
                        echo '<td>';
                        echo '<button class="btn btn-primary btn-sm" onclick="editUser(' . $row['id'] . ')">Editar</button>';
                        if ($row["activo"] == 1){
                            echo ' <button class="btn btn-danger btn-sm" onclick="deactivateUser(' . $row['id'] . ')">Desactivar</button>';
                        } else {
                            echo ' <button class="btn btn-success btn-sm" onclick="deactivateUser(' . $row['id'] . ')">Activar</button>';
                        }
                        echo '</td>';
                    }
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="registerForm" action="insert_user.php" method="POST" onsubmit="return validateForm();">
                <div class="modal-header">
                    <h2>Registro de Usuario</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">


                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre*</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="user" class="form-label">Usuario*</label>
                        <input type="text" class="form-control" id="user" name="user" required>
                    </div>
                    <div class="mb-3">
                        <label for="pass" class="form-label">Contraseña*</label>
                        <input type="password" class="form-control" id="pass" name="pass" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_pass" class="form-label">Confirmar Contraseña*</label>
                        <input type="password" class="form-control" id="confirm_pass" name="confirm_pass" required>
                    </div>
                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono">
                    </div>
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo</label>
                        <input type="email" class="form-control" id="correo" name="correo">
                    </div>
                    <div class="mb-3">
                        <input  class="form-select" id="IdRol" name="IdRol" value="4"  hidden/>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Registrar</button>

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>

                </div>
            </form>
        </div>
    </div>
</div>
<!-- Resto de código de modales y scripts -->
<?php include '../Modulos/Footer.php'; ?>
<script>
    $(document).ready(function () {
          function validateForm() {
        var pass = document.getElementById("pass").value;
        var confirm_pass = document.getElementById("confirm_pass").value;

        if (pass.length < 6) {
            alert("La contraseña debe tener al menos 6 caracteres.");
            return false;
        }

        if (pass !== confirm_pass) {
            alert("Las contraseñas no coinciden.");
            return false;
        }

        return true;
    }
        $('#myTable').DataTable({
            language: {
                lengthMenu: 'Mostrar _MENU_ entradas',
                zeroRecords: 'No se encontraron resultados',
                info: 'Mostrando página _PAGE_ de _PAGES_',
                search: 'Buscar:',
                paginate: {
                    first: 'Primero',
                    last: 'Último',
                    next: 'Siguiente',
                    previous: 'Anterior'
                },
                infoEmpty: 'No hay datos disponibles',
                infoFiltered: '(filtrado de _MAX_ registros totales)',
            },
        });
    });
</script>
