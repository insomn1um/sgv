<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'classes/Usuario.php';
require_once 'classes/Empresa.php';
require_once 'classes/Trabajador.php';
require_once 'classes/Visita.php';
require_once 'includes/functions.php';

// Verificar si está logueado
if (!isLoggedIn()) {
    redirect('index.php');
}

$database = Database::getInstance();
$db = $database->getConnection();

$empresa = new Empresa($db);
$trabajador = new Trabajador($db);
$visita = new Visita($db);

// Obtener estadísticas
$estadisticas_empresas = $empresa->getEstadisticas();
$estadisticas_trabajadores = $trabajador->getEstadisticas();
$estadisticas_visitas = $visita->getEstadisticas();

// Obtener alerta si existe
$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SGV</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link rel="stylesheet" href="includes/main.css">
    <style>
        /* Prevenir cualquier transición o colapso en submenús */
        #gestionSubmenu, #visitasSubmenu {
            display: block !important;
            height: auto !important;
            overflow: visible !important;
            transition: none !important;
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
                <a class="nav-link active" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                
                <?php if (!isOperador()): ?>
                <!-- Gestión Section (Solo Admin y Supervisor) -->
                <div class="nav-item">
                    <div class="nav-link text-white-50 small fw-bold">
                        <i class="fas fa-cogs"></i> GESTIÓN
                    </div>
                    <div id="gestionSubmenu">
                        <a class="nav-link submenu" href="gestion_empresas.php">
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
                
                <!-- Visitas Section (Todos los usuarios) -->
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
                <!-- Reportes (Solo Supervisor y Admin) -->
                <a class="nav-link" href="reportes.php">
                    <i class="fas fa-chart-bar"></i> Reportes
                </a>
                <?php endif; ?>
                
                <?php if (isAdmin()): ?>
                <!-- Auditoría (Solo Admin) -->
                <a class="nav-link" href="auditoria.php">
                    <i class="fas fa-history"></i> Auditoría
                </a>
                <?php endif; ?>
            </nav>
        </div>
    </div>

    <!-- Overlay para móviles -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <?php if ($alert): ?>
                <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($alert['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="mb-0">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </h2>
                    <p class="text-muted">Resumen del sistema de gestión de visitas</p>
                </div>
            </div>

            <?php if (!isOperador()): ?>
            <!-- Estadísticas de Empresas (Solo Admin y Supervisor) -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $estadisticas_empresas['total_empresas'] ?? 0; ?></h4>
                                    <p class="mb-0">Total Empresas</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-building fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $estadisticas_empresas['empresas_activas'] ?? 0; ?></h4>
                                    <p class="mb-0">Empresas Activas</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $estadisticas_empresas['empresas_suspendidas'] ?? 0; ?></h4>
                                    <p class="mb-0">Empresas Suspendidas</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-pause-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $estadisticas_empresas['empresas_bloqueadas'] ?? 0; ?></h4>
                                    <p class="mb-0">Empresas Bloqueadas</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-ban fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas de Trabajadores (Solo Admin y Supervisor) -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $estadisticas_trabajadores['total_trabajadores'] ?? 0; ?></h4>
                                    <p class="mb-0">Total Trabajadores</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $estadisticas_trabajadores['trabajadores_activos'] ?? 0; ?></h4>
                                    <p class="mb-0">Trabajadores Activos</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-user-check fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $estadisticas_visitas['visitas_hoy'] ?? 0; ?></h4>
                                    <p class="mb-0">Visitas Hoy</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-day fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $estadisticas_visitas['visitas_activas'] ?? 0; ?></h4>
                                    <p class="mb-0">Visitas Activas</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Estadísticas de Visitas (Para Todos) -->
            <?php if (isOperador()): ?>
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stat-card warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $estadisticas_visitas['visitas_hoy'] ?? 0; ?></h4>
                                    <p class="mb-0">Visitas Hoy</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-day fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $estadisticas_visitas['visitas_activas'] ?? 0; ?></h4>
                                    <p class="mb-0">Visitas Activas</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $estadisticas_visitas['visitas_mes'] ?? 0; ?></h4>
                                    <p class="mb-0">Visitas Este Mes</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!isOperador()): ?>
            <!-- Empresas Activas -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-building"></i> Empresas Activas
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Obtener empresas activas (aprobadas)
                            $empresas_activas = $empresa->obtenerPorCondicion('aprobada');
                            ?>
                            
                            <?php if ($empresas_activas && count($empresas_activas) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th><i class="fas fa-building"></i> Empresa</th>
                                                <th><i class="fas fa-id-card"></i> RUT</th>
                                                <th><i class="fas fa-map-marker-alt"></i> Dirección</th>
                                                <th><i class="fas fa-phone"></i> Contacto</th>
                                                <th><i class="fas fa-calendar"></i> Fecha Registro</th>
                                                <th><i class="fas fa-cogs"></i> Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($empresas_activas as $emp): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($emp['nombre']); ?></strong>
                                                        <?php if ($emp['razon_social']): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($emp['razon_social']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <code><?php echo htmlspecialchars($emp['rut']); ?></code>
                                                    </td>
                                                    <td>
                                                        <?php if ($emp['direccion']): ?>
                                                            <?php echo htmlspecialchars($emp['direccion']); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">No especificada</span>
                                                        <?php endif; ?>
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
                                                            <span class="text-muted">No especificado</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('d/m/Y', strtotime($emp['fecha_registro'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="ver_empresa.php?id=<?php echo $emp['id']; ?>" 
                                                               class="btn btn-sm btn-outline-primary" 
                                                               title="Ver detalles">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="editar_empresa.php?id=<?php echo $emp['id']; ?>" 
                                                               class="btn btn-sm btn-outline-warning" 
                                                               title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="gestion_empresas.php" 
                                                               class="btn btn-sm btn-outline-info" 
                                                               title="Gestionar">
                                                                <i class="fas fa-cogs"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <a href="gestion_empresas.php" class="btn btn-success">
                                        <i class="fas fa-list"></i> Ver Todas las Empresas
                                    </a>
                                </div>
                                
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No hay empresas activas</h5>
                                    <p class="text-muted">Todas las empresas están pendientes de aprobación o han sido suspendidas.</p>
                                    <a href="nueva_empresa.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Crear Primera Empresa
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas (Solo Admin y Supervisor) -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-bolt"></i> Acciones Rápidas
                            </h5>
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <a href="nueva_empresa.php" class="btn btn-primary w-100">
                                        <i class="fas fa-plus"></i> Nueva Empresa
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="nuevo_trabajador.php" class="btn btn-success w-100">
                                        <i class="fas fa-user-plus"></i> Nuevo Trabajador
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="nueva_visita.php" class="btn btn-info w-100">
                                        <i class="fas fa-calendar-plus"></i> Nueva Visita
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="reportes.php" class="btn btn-warning w-100">
                                        <i class="fas fa-chart-bar"></i> Ver Reportes
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Acciones Rápidas para Operadores -->
            <?php if (isOperador()): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-bolt"></i> Acciones Rápidas
                            </h5>
                            <div class="row justify-content-center">
                                <div class="col-md-6 mb-2">
                                    <a href="nueva_visita.php" class="btn btn-primary w-100 py-3">
                                        <i class="fas fa-calendar-plus fa-2x mb-2"></i><br>
                                        <strong>Registrar Nueva Visita</strong>
                                    </a>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <a href="visitas.php" class="btn btn-info w-100 py-3">
                                        <i class="fas fa-list fa-2x mb-2"></i><br>
                                        <strong>Ver Todas las Visitas</strong>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/sidebar-menu.js"></script>
    <script src="js/sidebar-pin.js"></script>
    <script>
        // Toggle sidebar en móviles
        // Toggle sidebar en móvil con overlay
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        function toggleSidebar() {
            if (window.innerWidth < 992) {
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
                if (sidebar.classList.contains('show')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            }
        }
        
        function closeSidebar() {
            if (window.innerWidth < 992) {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        }
        
        sidebarToggle?.addEventListener('click', toggleSidebar);
        sidebarOverlay?.addEventListener('click', closeSidebar);
        
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                closeSidebar();
            }
        });
    </script>
</body>
</html> 