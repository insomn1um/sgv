<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Empresa.php';
require_once 'classes/Trabajador.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

// Solo administradores y supervisores pueden ver empresas
if (isOperador()) {
    showAlert('No tienes permisos para acceder a esta página. Solo administradores y supervisores pueden gestionar empresas.', 'danger');
    redirect('dashboard.php');
}

$empresa_id = (int)($_GET['id'] ?? 0);
if (!$empresa_id) {
    redirect('gestion_empresas.php');
}

$database = Database::getInstance();
$db = $database->getConnection();
$empresa = new Empresa($db);
$trabajador = new Trabajador($db);

$empresa_data = $empresa->obtenerPorId($empresa_id);
if (!$empresa_data) {
    redirect('gestion_empresas.php');
}

// Obtener trabajadores de la empresa
$trabajadores = $trabajador->obtenerPorEmpresa($empresa_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Empresa - <?php echo htmlspecialchars($empresa_data['nombre']); ?> - SGV</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link rel="stylesheet" href="includes/main.css">
    <style>
        /* Estilos específicos para ver_empresa.php si son necesarios */
        .info-label {
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .info-value {
            color: #212529;
        }
        @media (max-width: 991.98px) {
            .sidebar {
                left: -260px;
                transition: left 0.3s;
            }
            .sidebar.show {
                left: 0;
            }
        }
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
                                <i class="fas fa-building"></i> Detalles de Empresa
                            </h2>
                            <p class="text-muted"><?php echo htmlspecialchars($empresa_data['nombre']); ?></p>
                        </div>
                        <div>
                            <a href="editar_empresa.php?id=<?php echo $empresa_data['id']; ?>" class="btn btn-warning me-2">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="gestion_empresas.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a Empresas
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Información de la Empresa -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle"></i> Información de la Empresa
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-building"></i> Nombre
                                </div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($empresa_data['nombre']); ?>
                                </div>
                            </div>
                            
                            <?php if ($empresa_data['razon_social']): ?>
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-file-contract"></i> Razón Social
                                </div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($empresa_data['razon_social']); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-id-card"></i> RUT
                                </div>
                                <div class="info-value">
                                    <code><?php echo htmlspecialchars($empresa_data['rut']); ?></code>
                                </div>
                            </div>
                            
                            <?php if ($empresa_data['direccion']): ?>
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-map-marker-alt"></i> Dirección
                                </div>
                                <div class="info-value">
                                    <?php echo nl2br(htmlspecialchars($empresa_data['direccion'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($empresa_data['telefono']): ?>
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-phone"></i> Teléfono
                                </div>
                                <div class="info-value">
                                    <a href="tel:<?php echo htmlspecialchars($empresa_data['telefono']); ?>">
                                        <?php echo htmlspecialchars($empresa_data['telefono']); ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($empresa_data['email']): ?>
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-envelope"></i> Email
                                </div>
                                <div class="info-value">
                                    <a href="mailto:<?php echo htmlspecialchars($empresa_data['email']); ?>">
                                        <?php echo htmlspecialchars($empresa_data['email']); ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-calendar"></i> Fecha de Registro
                                </div>
                                <div class="info-value">
                                    <?php echo date('d/m/Y H:i', strtotime($empresa_data['fecha_registro'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado y Acciones -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-cog"></i> Estado y Acciones
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="info-label">Estado Actual</div>
                                <?php
                                $badge_class = '';
                                $estado_text = '';
                                switch ($empresa_data['condicion']) {
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
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="cambiarEstado(<?php echo $empresa_data['id']; ?>, '<?php echo $empresa_data['condicion']; ?>')">
                                    <i class="fas fa-toggle-on"></i> Cambiar Estado
                                </button>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="nuevo_trabajador.php?empresa_id=<?php echo $empresa_data['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> Agregar Trabajador
                                </a>
                                <a href="trabajadores.php?empresa_id=<?php echo $empresa_data['id']; ?>" class="btn btn-outline-info">
                                    <i class="fas fa-users"></i> Ver Trabajadores
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas Rápidas -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-pie"></i> Estadísticas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <h4 class="text-primary"><?php echo count($trabajadores); ?></h4>
                                    <small class="text-muted">Trabajadores</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-success"><?php echo count(array_filter($trabajadores, function($t) { return $t['estado'] === 'activo'; })); ?></h4>
                                    <small class="text-muted">Activos</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Trabajadores -->
            <?php if (!empty($trabajadores)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i> Trabajadores de la Empresa
                        <span class="badge bg-secondary ms-2"><?php echo count($trabajadores); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>RUT</th>
                                    <th>Cargo</th>
                                    <th>Estado</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trabajadores as $trab): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($trab['nombre'] . ' ' . $trab['apellido']); ?></strong>
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($trab['numero_identificacion']); ?></code>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($trab['cargo'] ?? 'No especificado'); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $trab['estado'] === 'activo' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($trab['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y', strtotime($trab['fecha_registro'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="ver_trabajador.php?id=<?php echo $trab['id']; ?>" 
                                                   class="btn btn-sm btn-outline-info" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="editar_trabajador.php?id=<?php echo $trab['id']; ?>" 
                                                   class="btn btn-sm btn-outline-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para cambiar estado -->
    <div class="modal fade" id="estadoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cambiar Estado de Empresa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="estadoForm">
                        <input type="hidden" id="empresa_id" name="empresa_id">
                        <div class="mb-3">
                            <label for="nuevo_estado" class="form-label">Nuevo Estado</label>
                            <select name="nuevo_estado" id="nuevo_estado" class="form-select" required>
                                <option value="aprobada">Aprobada</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="denegada">Denegada</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="motivo" class="form-label">Motivo del cambio</label>
                            <textarea name="motivo" id="motivo" class="form-control" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarEstado()">Guardar Cambio</button>
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
    </script>
    <script>
function cambiarEstado(empresaId, condicionActual) {
    document.getElementById('empresa_id').value = empresaId;
    document.getElementById('nuevo_estado').value = condicionActual;
    document.getElementById('motivo').value = '';
    const modal = new bootstrap.Modal(document.getElementById('estadoModal'));
    modal.show();
}
function guardarEstado() {
    const form = document.getElementById('estadoForm');
    const empresaId = form.empresa_id.value;
    const nuevoEstado = form.nuevo_estado.value;
    const motivo = form.motivo.value.trim();
    if (!empresaId || !nuevoEstado || !motivo) {
        alert('Todos los campos son requeridos');
        return;
    }
    const formData = new FormData(form);
    fetch('ajax/cambiar_estado_empresa.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al cambiar la condición');
    });
}
</script>
</body>
</html> 