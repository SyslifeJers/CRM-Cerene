<?php
include '../Modulos/head.php';

$id_curso = $_GET['id'] ?? null;
require_once '../DB/Conexion.php';
$database = new Database();

if (!$id_curso || !$curso = $database->getCursoById($id_curso)) {
    header("Location: cursos.php?error=curso_no_encontrado");
    exit();
}
?>

<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title"><?= htmlspecialchars($curso['nombre_curso']) ?></h4>
        <div class="btn-group float-right">
          <a href="contenido.php?id_curso=<?= $id_curso ?>" class="btn btn-success">Agregar Contenido</a>
          <a href="agregar.php?id_curso=<?= $id_curso ?>" class="btn btn-info">Agregar Reunión Zoom</a>
        </div>
      </div>
      <div class="card-body">
        
        <!-- Pestañas para organizar la información -->
        <ul class="nav nav-tabs" id="cursoTabs" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" id="contenido-tab" data-tab="contenido" href="#contenido" role="tab">Contenido</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="reuniones-tab" data-tab="reuniones" href="#reuniones" role="tab">Reuniones</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="info-tab" data-tab="info" href="#info" role="tab">Información del Curso</a>
          </li>
        </ul>
        
        <!-- Contenedor dinámico para el contenido -->
        <div id="tabContent" class="tab-content p-3 border border-top-0 rounded-bottom">
          <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
            <p>Cargando contenido del curso...</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../Modulos/footer.php'; ?>

<script>
// Función para copiar enlaces
function copiarEnlace(enlace) {
    navigator.clipboard.writeText(enlace);
    alert("Enlace copiado: " + enlace);
};

// Función para cargar contenido de pestañas via AJAX
function cargarContenidoTab(tabName) {
    const tabContent = document.getElementById('tabContent');
    tabContent.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p>Cargando ${tabName}...</p>
        </div>
    `;
    
    let url = '';
    switch(tabName) {
        case 'contenido':
            url = `ajax/get_contenido_curso.php?id_curso=<?= $id_curso ?>`;
            break;
        case 'reuniones':
            url = `ajax/get_reuniones_zoom.php?id_curso=<?= $id_curso ?>`;
            break;
        case 'info':
            url = `ajax/get_info_curso.php?id_curso=<?= $id_curso ?>`;
            break;
    }
    
    fetch(url)
        .then(response => response.text())
        .then(data => {
            tabContent.innerHTML = data;
            // Inicializar DataTables si existen en la respuesta
            if (tabName === 'contenido' || tabName === 'reuniones') {
                $(`#tabla${tabName.charAt(0).toUpperCase() + tabName.slice(1)}`).DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
                    }
                });
            }
        })
        .catch(error => {
            tabContent.innerHTML = `
                <div class="alert alert-danger">
                    Error al cargar el contenido: ${error.message}
                </div>
            `;
        });
}

// Manejar clic en pestañas
document.querySelectorAll('[data-tab]').forEach(tab => {
    tab.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Actualizar clases activas
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        this.classList.add('active');
        
        // Cargar contenido
        const tabName = this.getAttribute('data-tab');
        cargarContenidoTab(tabName);
    });
});

// Cargar contenido inicial (primera pestaña)
document.addEventListener('DOMContentLoaded', function() {
    cargarContenidoTab('contenido');
});
</script>