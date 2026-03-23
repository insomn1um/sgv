<?php
session_start();
require_once 'includes/functions.php';
require_once 'classes/Trabajador.php';
require_once 'classes/Visita.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Solo administradores y supervisores pueden ver trabajadores
if (isOperador()) {
    showAlert('No tienes permisos para acceder a esta página. Solo administradores y supervisores pueden gestionar trabajadores.', 'danger');
    redirect('dashboard.php');
}

// Verificar que se proporcione un ID y validarlo
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: trabajadores.php');
    exit();
}

$trabajador = new Trabajador();
$visita = new Visita();

// Validar y sanitizar el ID
$trabajador_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if ($trabajador_id === false || $trabajador_id <= 0) {
    header('Location: trabajadores.php');
    exit();
}

$datos_trabajador = $trabajador->obtenerPorId($trabajador_id);

if (!$datos_trabajador) {
    header('Location: trabajadores.php');
    exit();
}

// Obtener solo las primeras 30 visitas (optimizado: no carga todas las visitas)
$visitas = $visita->obtenerPorTrabajador($trabajador_id, 30, 0);
$total_visitas = $visita->contarPorTrabajador($trabajador_id);

// Obtener estadísticas de visitas (optimizado: una sola consulta)
$estadisticas_visitas = $visita->obtenerEstadisticasPorTrabajador($trabajador_id);

// Obtener alerta si existe
$alert = getAlert();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Trabajador - SGV</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link rel="stylesheet" href="includes/main.css">
    <style>
        /* Estilos específicos para ver_trabajador.php si son necesarios */
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
                        <a class="nav-link submenu" href="gestion_empresas.php">
                            <i class="fas fa-building"></i> Empresas y Contratistas
                        </a>
                        <a class="nav-link submenu active" href="trabajadores.php">
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
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-user"></i> Detalles de Trabajador
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="trabajadores.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a Trabajadores
                            </a>
                            <a href="nueva_visita.php?trabajador_id=<?php echo $trabajador_id; ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Nueva Visita
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Información del trabajador -->
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-info-circle"></i> Información del Trabajador
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <div class="info-label">Nombre Completo:</div>
                                            <div class="info-value"><?php echo htmlspecialchars($datos_trabajador['nombre'] . ' ' . $datos_trabajador['apellido']); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <div class="info-label">Cargo:</div>
                                            <div class="info-value"><?php echo htmlspecialchars($datos_trabajador['cargo'] ?: 'No especificado'); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <div class="info-label">Tipo de Identificación:</div>
                                            <div class="info-value"><?php echo htmlspecialchars($datos_trabajador['tipo_identificacion']); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <div class="info-label">Número de Identificación:</div>
                                            <div class="info-value"><?php echo htmlspecialchars($datos_trabajador['numero_identificacion']); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <div class="info-label">Teléfono:</div>
                                            <div class="info-value"><?php echo htmlspecialchars($datos_trabajador['telefono'] ?: 'No especificado'); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <div class="info-label">Email:</div>
                                            <div class="info-value"><?php echo htmlspecialchars($datos_trabajador['email'] ?: 'No especificado'); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <div class="info-label">Empresa:</div>
                                            <div class="info-value">
                                                <?php if (!empty($datos_trabajador['empresa_id'])): ?>
                                                <a href="ver_empresa.php?id=<?php echo (int)$datos_trabajador['empresa_id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($datos_trabajador['empresa_nombre'] ?? 'No especificado'); ?>
                                                </a>
                                                <?php else: ?>
                                                    <span class="text-muted"><?php echo htmlspecialchars($datos_trabajador['empresa_nombre'] ?? 'Sin empresa asociada'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <div class="info-label">Estado de la Empresa:</div>
                                            <div class="info-value">
                                                <?php 
                                                $condicion = $datos_trabajador['empresa_condicion'] ?? null;
                                                $badge_class = 'badge-pendiente';
                                                if ($condicion === 'aprobada') {
                                                    $badge_class = 'badge-aprobada';
                                                } elseif ($condicion === 'denegada') {
                                                    $badge_class = 'badge-denegada';
                                                }
                                                ?>
                                                <span class="badge badge-condicion <?php echo $badge_class; ?>">
                                                    <?php echo $condicion !== null && $condicion !== '' ? ucfirst((string)$condicion) : '—'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <div class="info-label">Fecha de Registro:</div>
                                            <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($datos_trabajador['fecha_registro'])); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Visitas del trabajador -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-calendar-check"></i> Historial de Visitas (<?php echo $total_visitas; ?>)
                                </h5>
                                <a href="visitas.php?trabajador_id=<?php echo $trabajador_id; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-list"></i> Ver Todas
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($visitas)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No hay visitas registradas para este trabajador</p>
                                        <a href="nueva_visita.php?trabajador_id=<?php echo $trabajador_id; ?>" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Registrar Primera Visita
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>A quien visita</th>
                                                    <th>Motivo</th>
                                                    <th>Tarjeta</th>
                                                    <th>Patente</th>
                                                    <th>Estado</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($visitas as $vis): ?>
                                                    <tr>
                                                        <td><?php echo date('d/m/Y H:i', strtotime($vis['fecha_ingreso'])); ?></td>
                                                        <td><?php echo htmlspecialchars($vis['a_quien_visita'] ?? '-'); ?></td>
                                                        <td><?php echo htmlspecialchars(substr($vis['motivo_visita'], 0, 50)) . (strlen($vis['motivo_visita']) > 50 ? '...' : ''); ?></td>
                                                        <td><span class="badge bg-info"><?php echo htmlspecialchars($vis['numero_tarjeta'] ?: '-'); ?></span></td>
                                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($vis['patente'] ?: '-'); ?></span></td>
                                                        <td>
                                                            <?php if ($vis['estado'] === 'activa'): ?>
                                                                <span class="badge bg-success">Activa</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">Finalizada</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <a href="ver_visita.php?id=<?php echo $vis['id']; ?>" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <?php if ($vis['estado'] === 'activa'): ?>
                                                                <button class="btn btn-sm btn-outline-warning" onclick="registrarSalida(<?php echo $vis['id']; ?>)" title="Registrar salida">
                                                                    <i class="fas fa-sign-out-alt"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <?php if ($total_visitas > 30): ?>
                                            <div class="text-center mt-3">
                                                <a href="visitas.php?trabajador_id=<?php echo $trabajador_id; ?>" class="btn btn-outline-primary">
                                                    Ver todas las visitas (<?php echo $total_visitas; ?>)
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas y acciones -->
                    <div class="col-lg-4">
                        <!-- Estadísticas de visitas -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-bar"></i> Estadísticas de Visitas
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6 mb-3">
                                        <div class="text-primary">
                                            <h4><?php echo $estadisticas_visitas['total'] ?? 0; ?></h4>
                                            <small>Total Visitas</small>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="text-success">
                                            <h4><?php echo $estadisticas_visitas['activas'] ?? 0; ?></h4>
                                            <small>Visitas Activas</small>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="text-warning">
                                            <h4><?php echo $estadisticas_visitas['hoy'] ?? 0; ?></h4>
                                            <small>Visitas Hoy</small>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="text-info">
                                            <h4><?php echo $estadisticas_visitas['mes'] ?? 0; ?></h4>
                                            <small>Visitas Mes</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Acciones rápidas -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-bolt"></i> Acciones Rápidas
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="nueva_visita.php?trabajador_id=<?php echo $trabajador_id; ?>" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Nueva Visita
                                    </a>
                                    <a href="editar_trabajador.php?id=<?php echo $trabajador_id; ?>" class="btn btn-outline-warning">
                                        <i class="fas fa-edit"></i> Editar Trabajador
                                    </a>
                                    <a href="visitas.php?trabajador_id=<?php echo $trabajador_id; ?>" class="btn btn-outline-info">
                                        <i class="fas fa-calendar-check"></i> Ver Todas las Visitas
                                    </a>
                                    <?php if (!empty($datos_trabajador['empresa_id'])): ?>
                                    <a href="ver_empresa.php?id=<?php echo (int)$datos_trabajador['empresa_id']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-building"></i> Ver Empresa
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/sidebar-menu.js"></script>
    <script src="js/sidebar-pin.js"></script>
    <script>
        // Toggle sidebar en móvil con overlay
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        function toggleSidebar() {
            if (window.innerWidth < 992) {
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
                // Prevenir scroll del body cuando el sidebar está abierto
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
        
        // Cerrar sidebar al hacer clic en el overlay
        sidebarOverlay?.addEventListener('click', closeSidebar);
        
        // Cerrar sidebar al hacer clic fuera en móvil
        document.addEventListener('click', function(event) {
            if (window.innerWidth < 992) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target) && sidebar.classList.contains('show')) {
                    closeSidebar();
                }
            }
        });
        
        // Cerrar sidebar al redimensionar la ventana si pasa a desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                closeSidebar();
            }
        });
        
        function registrarSalida(visitaId) {
            if (confirm('¿Está seguro de registrar la salida de esta visita?')) {
                fetch('ajax/registrar_salida.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'visita_id=' + visitaId
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
                    alert('Error al registrar la salida');
                });
            }
        }
    </script>
</body>
</html> 