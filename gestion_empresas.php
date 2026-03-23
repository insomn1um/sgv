<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Empresa.php';
require_once 'classes/Trabajador.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

// Solo administradores y supervisores pueden gestionar empresas
if (isOperador()) {
    showAlert('No tienes permisos para acceder a esta página. Solo administradores y supervisores pueden gestionar empresas.', 'danger');
    redirect('dashboard.php');
}

$database = Database::getInstance();
$db = $database->getConnection();
$empresa = new Empresa($db);
$trabajador = new Trabajador($db);

// Procesar eliminación
if (isset($_POST['eliminar_empresa']) && isset($_POST['empresa_id'])) {
    $empresa_id = (int)$_POST['empresa_id'];
    if ($empresa->eliminar($empresa_id)) {
        if (isset($_SESSION['user_id'])) {
            registrarAuditoria('delete', 'empresas', 'Empresa eliminada', ['id' => $empresa_id]);
        }
        $mensaje = 'Empresa eliminada exitosamente.';
        $tipo_mensaje = 'success';
    } else {
        $mensaje = 'Error al eliminar la empresa.';
        $tipo_mensaje = 'danger';
    }
}

// Filtros
$condicion = $_GET['condicion'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Obtener empresas con filtros
$empresas = [];
if ($condicion && $busqueda) {
    $empresas = $empresa->buscar($busqueda);
    $empresas = array_filter($empresas, function($e) use ($condicion) {
        return $e['condicion'] === $condicion;
    });
} elseif ($condicion) {
    $empresas = $empresa->obtenerPorCondicion($condicion);
} elseif ($busqueda) {
    $empresas = $empresa->buscar($busqueda);
} else {
    $empresas = $empresa->obtenerTodas();
}

// Obtener estadísticas
$estadisticas = $empresa->getEstadisticas();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empresas - SGV</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link rel="stylesheet" href="includes/main.css">
    <style>
        /* Estilos específicos para gestion_empresas.php si son necesarios */
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top shadow" style="background-color: #3b82f6;">
        <div class="container-fluid">
            <button class="btn btn-outline-light d-lg-none me-2" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-building"></i> SGV
            </a>
            <div class="ms-auto d-flex align-items-center">
                <span class="text-white me-3 d-none d-md-inline">
                    <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']); ?>
                </span>
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-cog"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="cambiar_contrasena.php"><i class="fas fa-key"></i> Cambiar Contraseña</a></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="p-3">
            <h5 class="text-center mb-4">
                <i class="fas fa-building"></i> SGV
            </h5>
            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                
                <?php if (!isOperador()): ?>
                <!-- Gestión Section (Solo Admin y Supervisor) -->
                <div class="nav-item">
                    <div class="nav-link text-white-50 small fw-bold">
                        <i class="fas fa-cogs"></i> GESTIÓN
                    </div>
                    <div id="gestionSubmenu">
                        <a class="nav-link submenu active" href="gestion_empresas.php">
                            <i class="fas fa-building"></i> Empresas y Contratistas
                        </a>
                        <a class="nav-link submenu" href="trabajadores.php">
                            <i class="fas fa-users"></i> Trabajadores
                        </a>
                        <?php if (isAdmin()): ?>
                        <a class="nav-link submenu" href="usuarios.php">
                            <i class="fas fa-user-cog"></i> Usuarios
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Visitas Section -->
                <div class="nav-item">
                    <div class="nav-link text-white-50 small fw-bold">
                        <i class="fas fa-calendar-check"></i> VISITAS
                    </div>
                    <div id="visitasSubmenu">
                        <a class="nav-link submenu" href="visitas.php">
                            <i class="fas fa-list"></i> Listar Visitas
                        </a>
                        <a class="nav-link submenu" href="nueva_visita.php">
                            <i class="fas fa-plus-circle"></i> Nueva Visita
                        </a>
                    </div>
                </div>
                <?php if (isSupervisor()): ?>
                <a class="nav-link" href="reportes.php">
                    <i class="fas fa-chart-bar"></i> Reportes
                </a>
                <?php endif; ?>
                <?php if (isAdmin()): ?>
                <a class="nav-link" href="auditoria.php">
                    <i class="fas fa-history"></i> Auditoría
                </a>
                <?php endif; ?>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0">
                                <i class="fas fa-cogs"></i> Gestión de Empresas
                            </h2>
                            <p class="text-muted">Administrar empresas y contratistas del sistema</p>
                        </div>
                        <a href="nueva_empresa.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva Empresa
                        </a>
                    </div>
                </div>
            </div>

            <?php if (isset($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Toasts para alertas AJAX -->
            <div aria-live="polite" aria-atomic="true" class="position-relative">
                <div class="toast-container position-absolute top-0 end-0 p-3" id="toastContainer" style="z-index: 1050;">
                    <!-- Los toasts se insertarán dinámicamente aquí -->
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <h3 class="mb-0"><?php echo $estadisticas['total_empresas']; ?></h3>
                            <p class="mb-0">Total Empresas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card success">
                        <div class="card-body text-center">
                            <h3 class="mb-0"><?php echo $estadisticas['empresas_aprobadas']; ?></h3>
                            <p class="mb-0">Aprobadas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card warning">
                        <div class="card-body text-center">
                            <h3 class="mb-0"><?php echo $estadisticas['empresas_pendientes']; ?></h3>
                            <p class="mb-0">Pendientes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card danger">
                        <div class="card-body text-center">
                            <h3 class="mb-0"><?php echo $estadisticas['empresas_denegadas']; ?></h3>
                            <p class="mb-0">Denegadas</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="condicion" class="form-label">Estado</label>
                            <select class="form-select" id="condicion" name="condicion">
                                <option value="">Todos los estados</option>
                                <option value="pendiente" <?php echo $condicion === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="aprobada" <?php echo $condicion === 'aprobada' ? 'selected' : ''; ?>>Aprobada</option>
                                <option value="denegada" <?php echo $condicion === 'denegada' ? 'selected' : ''; ?>>Denegada</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="busqueda" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="busqueda" name="busqueda" 
                                   value="<?php echo htmlspecialchars($busqueda); ?>" 
                                   placeholder="Nombre, RUT o Razón Social">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de Empresas -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> Lista de Empresas
                        <span class="badge bg-secondary ms-2"><?php echo count($empresas); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($empresas)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-building fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No se encontraron empresas</h5>
                            <p class="text-muted">Intenta ajustar los filtros o crear una nueva empresa.</p>
                            <a href="nueva_empresa.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Crear Empresa
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Empresa</th>
                                        <th>RUT</th>
                                        <th>Contacto</th>
                                        <th>Estado</th>
                                        <th>Fecha Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($empresas as $emp): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($emp['nombre']); ?></strong>
                                                    <?php if ($emp['razon_social']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($emp['razon_social']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($emp['rut']); ?></code>
                                            </td>
                                            <td>
                                                <?php if ($emp['telefono'] || $emp['email']): ?>
                                                    <?php if ($emp['telefono']): ?>
                                                        <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($emp['telefono']); ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($emp['email']): ?>
                                                        <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($emp['email']); ?></div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin contacto</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = '';
                                                $estado_text = '';
                                                switch ($emp['condicion']) {
                                                    case 'aprobada':
                                                        $badge_class = 'badge-aprobada';
                                                        $estado_text = 'Aprobada';
                                                        break;
                                                    case 'pendiente':
                                                        $badge_class = 'badge-pendiente';
                                                        $estado_text = 'Pendiente';
                                                        break;
                                                    case 'denegada':
                                                        $badge_class = 'badge-denegada';
                                                        $estado_text = 'Denegada';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge badge-condicion <?php echo $badge_class; ?>">
                                                    <?php echo $estado_text; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y', strtotime($emp['fecha_registro'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="ver_empresa.php?id=<?php echo $emp['id']; ?>" 
                                                       class="btn btn-sm btn-outline-info" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="editar_empresa.php?id=<?php echo $emp['id']; ?>" 
                                                       class="btn btn-sm btn-outline-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="cambiarEstado(<?php echo $emp['id']; ?>, '<?php echo $emp['condicion'] ?? '-'; ?>')"
                                                            title="Cambiar Estado">
                                                        <i class="fas fa-toggle-on"></i> Cambiar Estado
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="confirmarEliminacion(<?php echo $emp['id']; ?>, '<?php echo htmlspecialchars($emp['nombre']); ?>')"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación de Eliminación -->
    <div class="modal fade" id="eliminarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle text-danger"></i> Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar la empresa <strong id="empresaNombre"></strong>?</p>
                    <p class="text-danger"><small>Esta acción no se puede deshacer.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="empresa_id" id="empresaId">
                        <button type="submit" name="eliminar_empresa" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Cambio de Estado -->
    <div class="modal fade" id="estadoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-toggle-on text-primary"></i> Cambiar Estado de Empresa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="estadoForm">
                        <input type="hidden" id="empresa_id" name="empresa_id">
                        <div class="mb-3">
                            <label for="nuevo_estado" class="form-label">Nuevo Estado</label>
                            <select class="form-select" id="nuevo_estado" name="nuevo_estado" required>
                                <option value="">Seleccionar estado...</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="aprobada">Aprobada</option>
                                <option value="denegada">Denegada</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="motivo" class="form-label">Motivo del Cambio</label>
                            <textarea class="form-control" id="motivo" name="motivo" rows="3" 
                                      placeholder="Describe el motivo del cambio de estado..." required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarEstado()">
                        <i class="fas fa-save"></i> Guardar Cambio
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/sidebar-menu.js"></script>
    <script src="js/sidebar-pin.js"></script>
    <script>
        // Toggle sidebar en móviles
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Confirmar eliminación
        function confirmarEliminacion(empresaId, empresaNombre) {
            document.getElementById('empresaId').value = empresaId;
            document.getElementById('empresaNombre').textContent = empresaNombre;
            new bootstrap.Modal(document.getElementById('eliminarModal')).show();
        }

        // Función para mostrar toasts
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer');
            
            // Crear el toast
            const toastHtml = `
                <div class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'}"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            // Insertar el toast
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            
            // Obtener el último toast insertado
            const toastElement = toastContainer.lastElementChild;
            
            // Mostrar el toast
            const toast = new bootstrap.Toast(toastElement, { 
                delay: 4000,
                autohide: true
            });
            
            toast.show();
            
            // Remover el toast del DOM después de que se oculte
            toastElement.addEventListener('hidden.bs.toast', function() {
                toastElement.remove();
            });
        }
        
        // Cambiar estado de empresa
        function cambiarEstado(empresaId, condicionActual) {
            document.getElementById('empresa_id').value = empresaId;
            // No establecer el estado actual como valor por defecto
            document.getElementById('nuevo_estado').value = '';
            document.getElementById('motivo').value = '';
            
            // Mostrar información de la condición actual
            const modal = new bootstrap.Modal(document.getElementById('estadoModal'));
            modal.show();
            
            // Agregar información sobre el estado actual
            const modalBody = document.querySelector('#estadoModal .modal-body');
            const infoDiv = document.createElement('div');
            infoDiv.className = 'alert alert-info mb-3';
            infoDiv.innerHTML = `<strong>Estado actual:</strong> ${condicionActual}`;
            
            // Remover información anterior si existe
            const existingInfo = modalBody.querySelector('.alert-info');
            if (existingInfo) {
                existingInfo.remove();
            }
            
            // Insertar al principio del modal body
            modalBody.insertBefore(infoDiv, modalBody.firstChild);
        }
        function guardarEstado() {
            const form = document.getElementById('estadoForm');
            const empresaId = form.empresa_id.value;
            const nuevoEstado = form.nuevo_estado.value;
            const motivo = form.motivo.value.trim();
            
            if (!empresaId || !nuevoEstado || !motivo) {
                showToast('Todos los campos son requeridos', 'danger');
                return;
            }
            
            // Deshabilitar el botón para evitar doble envío
            const submitBtn = document.querySelector('#estadoModal .btn-primary');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            
            const formData = new FormData(form);
            fetch('ajax/cambiar_estado_empresa.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    showToast('Estado actualizado correctamente', 'success');
                    // Cerrar modal y recargar página
                    const modal = bootstrap.Modal.getInstance(document.getElementById('estadoModal'));
                    modal.hide();
                    location.reload();
                } else {
                    showToast('Error: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error al cambiar la condición. Verifica la conexión e intenta nuevamente.', 'danger');
            })
            .finally(() => {
                // Restaurar el botón
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        }
    </script>
</body>
</html> 