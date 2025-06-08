<?php
session_start();
if (!isset($_SESSION['participante_id']) ) {
    header("Location: registro.php");
    exit();
}

include 'Modulos/head.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4>¡Registro Exitoso!</h4>
                </div>
                <div class="card-body text-center">
                    <h5 class="mb-4">Bienvenido/a <?= htmlspecialchars($_SESSION['nombre']) ?></h5>
                    
                    <div class="alert alert-info">
                        <h5><i class="fas fa-key"></i> Tu contraseña temporal</h5>
                        <div class="display-4 my-3"><?= $_SESSION['pass_temporal'] ?></div>
                        <p>Guarda esta contraseña para futuros accesos.</p>
                    </div>
                    
                    <div class="mt-4">
                        <a href="panel_usuario.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-tachometer-alt"></i> Ir a mi panel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Limpiar la contraseña temporal de la sesión
unset($_SESSION['pass_temporal']);
include 'Modulos/footer.php'; 
?>