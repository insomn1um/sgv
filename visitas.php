<?php
session_start();
require_once 'includes/functions.php';
require_once 'classes/Visita.php';
require_once 'classes/Empresa.php';

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    redirect('index.php');
}

$visita = new Visita();
$empresa = new Empresa();
$mensaje = '';
$tipo_mensaje = '';

// Obtener visitas
$visitas = $visita->obtenerTodas();

// Filtrar por trabajador si se especifica
if (isset($_GET['trabajador_id']) && !empty($_GET['trabajador_id'])) {
    $visitas = $visita->obtenerPorTrabajador($_GET['trabajador_id']);
}

// Filtrar por empresa si se especifica
if (isset($_GET['empresa_id']) && !empty($_GET['empresa_id'])) {
    $visitas = $visita->obtenerPorEmpresa($_GET['empresa_id']);
}

// Filtrar por estado
if (isset($_GET['estado']) && !empty($_GET['estado'])) {
    if ($_GET['estado'] === 'activas') {
        $visitas = $visita->obtenerActivas();
    } elseif ($_GET['estado'] === 'hoy') {
        $visitas = $visita->obtenerDelDia();
    }
}

// Búsqueda
$termino_busqueda = '';
if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $termino_busqueda = trim($_GET['buscar']);
    $visitas = $visita->buscar($termino_busqueda);
}

// Obtener alerta si existe
$alert = getAlert();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Visitas - SGV</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link rel="stylesheet" href="includes/main.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
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
                        <a class="nav-link submenu active" href="visitas.php">
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-3 mb-4 border-bottom">
                    <h1 class="h2 mb-0">
                        <i class="fas fa-calendar-check"></i> Gestión de Visitas
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="nueva_visita.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Nueva Visita
                            </a>
                            <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><h6 class="dropdown-header">Crear visita para empresa específica:</h6></li>
                                <?php foreach ($empresa->obtenerTodas() as $emp): ?>
                                    <li>
                                        <a class="dropdown-item" href="nueva_visita.php?empresa_id=<?php echo $emp['id']; ?>">
                                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($emp['nombre']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($mensaje); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filtros y búsqueda -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="text" name="buscar" class="form-control me-2" placeholder="Buscar visitas..." value="<?php echo htmlspecialchars($termino_busqueda); ?>">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <div class="btn-group" role="group">
                            <a href="visitas.php" class="btn btn-outline-secondary">Todas</a>
                            <a href="visitas.php?estado=activas" class="btn btn-outline-success">Activas</a>
                            <a href="visitas.php?estado=hoy" class="btn btn-outline-warning">Hoy</a>
                        </div>
                    </div>
                </div>

                <!-- Tabla de visitas -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Visitante</th>
                                        <th>De la Empresa</th>
                                        <th>Trabajador Visitado</th>
                                        <th>Motivo</th>
                                        <th>Tarjeta</th>
                                        <th>Patente</th>
                                        <th>Tipo Vehículo</th>
                                        <th>Ingreso</th>
                                        <th>Salida</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($visitas)): ?>
                                        <tr>
                                            <td colspan="11" class="text-center text-muted">
                                                <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                                <br>No se encontraron visitas
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($visitas as $vis): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($vis['a_quien_visita']); ?></strong><br>
                                                    <small class="text-muted">Visitante</small>
                                                </td>
                                                <td>
                                                    <?php if (!empty($vis['empresa_visitante'])): ?>
                                                        <span class="badge bg-primary">
                                                            <?php echo htmlspecialchars($vis['empresa_visitante']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($vis['nombre'] . ' ' . $vis['apellido']); ?></strong><br>
                                                    <small class="text-muted"><?php echo $vis['tipo_identificacion'] . ': ' . $vis['numero_identificacion']; ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars(substr($vis['motivo_visita'], 0, 50)) . (strlen($vis['motivo_visita']) > 50 ? '...' : ''); ?></td>
                                                <td><span class="badge bg-info"><?php echo htmlspecialchars($vis['numero_tarjeta'] ?: '-'); ?></span></td>
                                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($vis['patente'] ?: '-'); ?></span></td>
                                                <td>
                                                    <?php if (!empty($vis['tipo_vehiculo'])): ?>
                                                        <span class="badge bg-primary">
                                                            <i class="fas fa-<?php 
                                                                echo $vis['tipo_vehiculo'] === 'auto' ? 'car' : 
                                                                    ($vis['tipo_vehiculo'] === 'camioneta' ? 'truck-pickup' : 
                                                                    ($vis['tipo_vehiculo'] === 'camion' ? 'truck' : 
                                                                    ($vis['tipo_vehiculo'] === 'moto' ? 'motorcycle' : 
                                                                    ($vis['tipo_vehiculo'] === 'furgon' ? 'shuttle-van' : 
                                                                    ($vis['tipo_vehiculo'] === 'van' ? 'van-shuttle' : 'car-side'))))); 
                                                            ?>"></i>
                                                            <?php echo htmlspecialchars(ucfirst($vis['tipo_vehiculo'])); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($vis['fecha_ingreso'])); ?></td>
                                                <td>
                                                    <?php if ($vis['fecha_salida']): ?>
                                                        <?php echo date('d/m/Y H:i', strtotime($vis['fecha_salida'])); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
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
                                                        <button class="btn btn-sm btn-outline-warning" onclick="mostrarModalSalida(<?php echo $vis['id']; ?>, '<?php echo htmlspecialchars(addslashes($vis['a_quien_visita'])); ?>')" title="Registrar salida">
                                                            <i class="fas fa-sign-out-alt"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if (isAdmin() || isSupervisor()): ?>
                                                        <a href="ver_trabajador.php?id=<?php echo $vis['trabajador_id']; ?>" class="btn btn-sm btn-outline-info" title="Ver trabajador">
                                                            <i class="fas fa-user"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación de Salida -->
    <div class="modal fade" id="modalConfirmarSalida" tabindex="-1" aria-labelledby="modalConfirmarSalidaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white;">
                    <h5 class="modal-title" id="modalConfirmarSalidaLabel">
                        <i class="fas fa-sign-out-alt"></i> Confirmar Salida de Visita
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-question-circle" style="font-size: 3rem; color: #ffc107;"></i>
                    </div>
                    <h6 class="text-center mb-3">¿Está seguro de registrar la salida de esta visita?</h6>
                    <div class="alert alert-info mb-0">
                        <strong><i class="fas fa-user"></i> Visitante:</strong>
                        <p class="mb-0" id="nombreVisitante"></p>
                        <hr class="my-2">
                        <strong><i class="fas fa-clock"></i> Fecha/Hora:</strong>
                        <p class="mb-0" id="fechaHoraSalida"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-warning" id="btnConfirmarSalida">
                        <i class="fas fa-check"></i> Confirmar Salida
                    </button>
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
        
        // Cerrar sidebar al hacer clic fuera en móvil
        document.addEventListener('click', function(event) {
            if (window.innerWidth < 992) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target) && sidebar.classList.contains('show')) {
                    closeSidebar();
                }
            }
        });
        
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                closeSidebar();
            }
        });
        
        // Variables globales para el modal
        let visitaIdActual = null;
        let modalSalida = null;
        
        // Inicializar modal
        document.addEventListener('DOMContentLoaded', function() {
            modalSalida = new bootstrap.Modal(document.getElementById('modalConfirmarSalida'));
        });
        
        // Mostrar modal de confirmación
        function mostrarModalSalida(visitaId, nombreVisitante) {
            visitaIdActual = visitaId;
            
            // Actualizar información en el modal
            document.getElementById('nombreVisitante').textContent = nombreVisitante;
            
            // Fecha y hora actual
            const ahora = new Date();
            const opciones = { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('fechaHoraSalida').textContent = ahora.toLocaleDateString('es-ES', opciones);
            
            // Mostrar modal
            modalSalida.show();
        }
        
        // Confirmar salida
        document.getElementById('btnConfirmarSalida')?.addEventListener('click', function() {
            const btn = this;
            const originalContent = btn.innerHTML;
            
            // Deshabilitar botón y mostrar spinner
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            
            fetch('ajax/registrar_salida.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'visita_id=' + visitaIdActual
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Respuesta del servidor:', data);
                
                if (data.success) {
                    // Cerrar modal
                    modalSalida.hide();
                    
                    // Mostrar notificación de éxito
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                    alertDiv.style.zIndex = '9999';
                    alertDiv.innerHTML = `
                        <i class="fas fa-check-circle"></i> ${data.message || 'Salida registrada correctamente'}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.body.appendChild(alertDiv);
                    
                    // Recargar después de 1 segundo
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    // Mostrar error en modal
                    const modalBody = document.querySelector('#modalConfirmarSalida .modal-body');
                    const errorAlert = document.createElement('div');
                    errorAlert.className = 'alert alert-danger mt-3';
                    errorAlert.innerHTML = `<strong><i class="fas fa-exclamation-triangle"></i> Error:</strong><br>${data.message || 'Error desconocido'}`;
                    modalBody.appendChild(errorAlert);
                    
                    // Restaurar botón
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            })
            .catch(error => {
                console.error('Error completo:', error);
                
                // Mostrar error en modal
                const modalBody = document.querySelector('#modalConfirmarSalida .modal-body');
                const errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-danger mt-3';
                errorAlert.innerHTML = `<strong><i class="fas fa-exclamation-triangle"></i> Error de conexión:</strong><br>${error.message}`;
                modalBody.appendChild(errorAlert);
                
                // Restaurar botón
                btn.disabled = false;
                btn.innerHTML = originalContent;
            });
        });
        
        // Limpiar alertas al abrir el modal
        document.getElementById('modalConfirmarSalida')?.addEventListener('show.bs.modal', function() {
            // Remover alertas de error previas
            const alertas = this.querySelectorAll('.alert-danger');
            alertas.forEach(alerta => alerta.remove());
        });
    </script>
</body>
</html> 