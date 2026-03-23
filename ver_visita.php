<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Visita.php';
require_once 'classes/Visitante.php';
require_once 'includes/functions.php';

// Verificar si está logueado
if (!isLoggedIn()) {
    redirect('index.php');
}

$database = Database::getInstance();
$db = $database->getConnection();

$visita = new Visita($db);
$visitante = new Visitante($db);

// Obtener ID de la visita
$visita_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$visita_id) {
    showAlert('ID de visita no válido.', 'danger');
    redirect('dashboard.php');
}

// Obtener datos de la visita
$datos_visita = $visita->obtenerPorId($visita_id);

if (!$datos_visita) {
    showAlert('Visita no encontrada.', 'danger');
    redirect('dashboard.php');
}

// Obtener alerta si existe
$alert = getAlert();

// Procesar edición de patente
if (isset($_POST['editar_patente']) && isLoggedIn()) {
    $nueva_patente = trim($_POST['nueva_patente']);
    $visita->id = $visita_id;
    // Actualizar solo la patente
    $stmt = $db->prepare("UPDATE visitas SET patente = :patente WHERE id = :id");
    $stmt->bindParam(':patente', $nueva_patente);
    $stmt->bindParam(':id', $visita_id);
    if ($stmt->execute()) {
        registrarAuditoria('update', 'visitas', 'Patente editada en visita', [
            'visita_id' => $visita_id,
            'patente_anterior' => $datos_visita['patente'],
            'patente_nueva' => $nueva_patente
        ]);
        showAlert('Patente actualizada correctamente.','success');
        header('Location: ver_visita.php?id=' . $visita_id);
        exit;
    } else {
        showAlert('Error al actualizar la patente.','danger');
    }
    // Refrescar datos
    $datos_visita = $visita->readOne();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Visita - SGV</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link rel="stylesheet" href="includes/main.css">
    <style>
        /* Estilos específicos para ver_visita.php si son necesarios */
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
                    <i class="fas fa-user-circle"></i> <?php echo $_SESSION['nombre'] . ' ' . $_SESSION['apellido']; ?>
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
            <div class="text-center mb-4">
                <i class="fas fa-building fa-2x mb-2"></i>
                <h5>SGV</h5>
                <small>Sistema de Gestión de Visitas</small>
            </div>
            
            <div class="mb-3">
                <small class="text-muted">Bienvenido,</small><br>
                <strong><?php echo $_SESSION['nombre'] . ' ' . $_SESSION['apellido']; ?></strong><br>
                <small class="text-muted"><?php echo getProfileName($_SESSION['rol']); ?></small>
            </div>
            
            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
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
                <a class="nav-link" href="usuarios.php">
                    <i class="fas fa-users-cog"></i> Usuarios
                </a>
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-eye"></i> Detalles de la Visita</h2>
                <div>
                    <a href="visitas.php" class="btn btn-secondary btn-action">
                        <i class="fas fa-arrow-left"></i> Volver a Visitas
                    </a>
                    <?php if ($datos_visita['estado'] == 'activa'): ?>
                    <button class="btn btn-warning btn-action" onclick="registrarSalida(<?php echo $datos_visita['id']; ?>)">
                        <i class="fas fa-sign-out-alt"></i> Registrar Salida
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Toasts para alertas -->
            <div aria-live="polite" aria-atomic="true" class="position-relative">
                <div class="toast-container position-absolute top-0 end-0 p-3">
                    <?php if ($alert): ?>
                    <div class="toast align-items-center text-bg-<?php echo $alert['type'] == 'success' ? 'success' : ($alert['type'] == 'danger' ? 'danger' : 'info'); ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                <?php echo $alert['message']; ?>
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="row">
                <!-- Información del Visitante -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header" style="background-color: #3b82f6; color: white;">
                            <h5 class="mb-0">
                                <i class="fas fa-user"></i> Información del Visitante o Contratista
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="info-item">
                                <div class="info-label">Nombre Completo</div>
                                <div class="info-value">
                                    <strong><?php echo $datos_visita['nombre'] . ' ' . $datos_visita['apellido']; ?></strong>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Identificación</div>
                                <div class="info-value">
                                    <span class="badge bg-light text-dark">
                                        <?php echo $datos_visita['tipo_identificacion'] . ' ' . $datos_visita['numero_identificacion']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Número de Contacto</div>
                                <div class="info-value">
                                    <i class="fas fa-phone text-success"></i> 
                                    <?php echo $datos_visita['numero_contacto'] ?? 'No registrado'; ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email</div>
                                <div class="info-value">
                                    <i class="fas fa-envelope text-primary"></i> 
                                    <?php echo $datos_visita['email'] ?? 'No registrado'; ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Empresa que Representa</div>
                                <div class="info-value">
                                    <i class="fas fa-building text-info"></i> 
                                    <?php echo $datos_visita['empresa_representa']; ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">A quien Visita</div>
                                <div class="info-value">
                                    <i class="fas fa-user-tie text-warning"></i> 
                                    <?php echo $datos_visita['a_quien_visita']; ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Motivo de la Visita</div>
                                <div class="info-value">
                                    <i class="fas fa-comment text-secondary"></i> 
                                    <?php echo $datos_visita['motivo_visita']; ?>
                                </div>
                            </div>
                            <?php if (!empty($datos_visita['patente_vehiculo'])): ?>
                            <div class="info-item">
                                <div class="info-label">Patente del Vehículo</div>
                                <div class="info-value">
                                    <i class="fas fa-car text-dark"></i> 
                                    <span class="badge bg-secondary"><?php echo $datos_visita['patente_vehiculo']; ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($datos_visita['tipo_vehiculo'])): ?>
                            <div class="info-item">
                                <div class="info-label">Tipo de Vehículo</div>
                                <div class="info-value">
                                    <i class="fas fa-<?php 
                                        echo $datos_visita['tipo_vehiculo'] === 'auto' ? 'car' : 
                                            ($datos_visita['tipo_vehiculo'] === 'camioneta' ? 'truck-pickup' : 
                                            ($datos_visita['tipo_vehiculo'] === 'camion' ? 'truck' : 
                                            ($datos_visita['tipo_vehiculo'] === 'moto' ? 'motorcycle' : 
                                            ($datos_visita['tipo_vehiculo'] === 'furgon' ? 'shuttle-van' : 
                                            ($datos_visita['tipo_vehiculo'] === 'van' ? 'van-shuttle' : 'car-side'))))); 
                                    ?> text-primary"></i> 
                                    <span class="badge bg-primary"><?php echo htmlspecialchars(ucfirst($datos_visita['tipo_vehiculo'])); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Información de la Visita -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header" style="background-color: #1e293b; color: white;">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-check"></i> Información de la Visita
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">ID de Visita</div>
                                        <div class="info-value">
                                            <span class="badge bg-dark">#<?php echo $datos_visita['id']; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">Número de Tarjeta</div>
                                        <div class="info-value">
                                            <span class="badge bg-info"><?php echo $datos_visita['numero_tarjeta']; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">Estado</div>
                                        <div class="info-value">
                                            <?php if ($datos_visita['estado'] == 'activa'): ?>
                                                <span class="status-badge status-active">
                                                    <i class="fas fa-check-circle"></i> Activa
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-finished">
                                                    <i class="fas fa-times-circle"></i> Finalizada
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">Duración</div>
                                        <div class="info-value">
                                            <?php 
                                            $ingreso = new DateTime($datos_visita['fecha_ingreso']);
                                            if ($datos_visita['fecha_salida']) {
                                                $salida = new DateTime($datos_visita['fecha_salida']);
                                                $duracion = $ingreso->diff($salida);
                                                echo $duracion->format('%Hh %Im');
                                            } else {
                                                $ahora = new DateTime();
                                                $duracion = $ingreso->diff($ahora);
                                                echo '<span class="text-success">' . $duracion->format('%Hh %Im') . '</span>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">Fecha de Ingreso</div>
                                        <div class="info-value">
                                            <i class="fas fa-sign-in-alt text-success"></i> 
                                            <?php echo formatDate($datos_visita['fecha_ingreso']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">Fecha de Salida</div>
                                        <div class="info-value">
                                            <?php if ($datos_visita['fecha_salida']): ?>
                                                <i class="fas fa-sign-out-alt text-danger"></i> 
                                                <?php echo formatDate($datos_visita['fecha_salida']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">
                                                    <i class="fas fa-clock"></i> Pendiente
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">Patente</div>
                                        <div class="info-value">
                                            <?php if (isAdmin() || isSupervisor()): ?>
                                                <form method="POST" class="d-flex align-items-center gap-2" style="max-width: 250px;">
                                                    <input type="text" name="nueva_patente" class="form-control form-control-sm" value="<?php echo htmlspecialchars($datos_visita['patente'] ?? '-'); ?>" maxlength="20" style="width: 120px;">
                                                    <button type="submit" name="editar_patente" class="btn btn-sm btn-primary"><i class="fas fa-save"></i></button>
                                                </form>
                                            <?php else: ?>
                                                <i class="fas fa-car text-dark"></i> 
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($datos_visita['patente'] ?? '-'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if (!empty($datos_visita['tipo_vehiculo'])): ?>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">Tipo de Vehículo</div>
                                        <div class="info-value">
                                            <i class="fas fa-<?php 
                                                echo $datos_visita['tipo_vehiculo'] === 'auto' ? 'car' : 
                                                    ($datos_visita['tipo_vehiculo'] === 'camioneta' ? 'truck-pickup' : 
                                                    ($datos_visita['tipo_vehiculo'] === 'camion' ? 'truck' : 
                                                    ($datos_visita['tipo_vehiculo'] === 'moto' ? 'motorcycle' : 
                                                    ($datos_visita['tipo_vehiculo'] === 'furgon' ? 'shuttle-van' : 
                                                    ($datos_visita['tipo_vehiculo'] === 'van' ? 'van-shuttle' : 'car-side'))))); 
                                            ?> text-primary"></i> 
                                            <span class="badge bg-primary"><?php echo htmlspecialchars(ucfirst($datos_visita['tipo_vehiculo'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($datos_visita['observaciones'])): ?>
                            <div class="info-item mt-3">
                                <div class="info-label">Observaciones</div>
                                <div class="info-value">
                                    <i class="fas fa-sticky-note text-warning"></i> 
                                    <?php echo htmlspecialchars($datos_visita['observaciones'] ?? '-'); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Información del Registro -->
            <div class="card mb-4">
                <div class="card-header" style="background-color: #f8f9fa;">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Información del Registro</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-item">
                                <div class="info-label">Registrado por</div>
                                <div class="info-value">
                                    <i class="fas fa-user-cog text-primary"></i> 
                                    <?php echo $datos_visita['registrado_por_nombre'] ?? 'Sistema'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <div class="info-label">Fecha de Registro</div>
                                <div class="info-value">
                                    <i class="fas fa-calendar-plus text-info"></i> 
                                    <?php echo formatDate($datos_visita['fecha_ingreso']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <div class="info-label">Última Actualización</div>
                                <div class="info-value">
                                    <i class="fas fa-edit text-secondary"></i> 
                                    <?php 
                                    if ($datos_visita['fecha_salida']) {
                                        echo formatDate($datos_visita['fecha_salida']);
                                    } else {
                                        echo formatDate($datos_visita['fecha_ingreso']);
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Acciones -->
            <div class="card">
                <div class="card-header" style="background-color: #f8f9fa;">
                    <h5 class="mb-0"><i class="fas fa-cogs"></i> Acciones</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if ($datos_visita['estado'] == 'activa'): ?>
                        <button class="btn btn-warning btn-action" onclick="registrarSalida(<?php echo $datos_visita['id']; ?>)">
                            <i class="fas fa-sign-out-alt"></i> Registrar Salida
                        </button>
                        <?php endif; ?>
                        <a href="visitas.php" class="btn btn-outline-secondary btn-action">
                            <i class="fas fa-list"></i> Ver Todas las Visitas
                        </a>
                        <a href="visitantes.php" class="btn btn-outline-info btn-action">
                            <i class="fas fa-users"></i> Ver Visitantes y Contratistas
                        </a>
                        <a href="nueva_visita.php" class="btn" style="background-color: #3b82f6; border-color: #3b82f6; color: white;" class="btn-action">
                            <i class="fas fa-plus"></i> Nueva Visita
                        </a>
                        <button class="btn btn-outline-primary btn-action" onclick="window.print()">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle para móviles
        document.addEventListener("DOMContentLoaded", function() {
            const sidebar = document.getElementById("sidebar");
            const sidebarToggle = document.getElementById("sidebarToggle");
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener("click", function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    sidebar.classList.toggle("show");
                });
            }
            
            // Cerrar sidebar al hacer click fuera (en móviles)
            document.addEventListener("click", function(e) {
                if (window.innerWidth < 992 && sidebar && sidebar.classList.contains("show")) {
                    if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                        sidebar.classList.remove("show");
                    }
                }
            });
            
            // Inicializar toasts si existen
            var toastElList = [].slice.call(document.querySelectorAll(".toast"));
            if (toastElList.length > 0) {
                toastElList.forEach(function(toastEl) {
                    try {
                        new bootstrap.Toast(toastEl, { delay: 4000 }).show();
                    } catch (error) {
                        console.warn("Error al inicializar toast:", error);
                    }
                });
            }
        });
        
        // Función para registrar salida
        function registrarSalida(visitaId) {
            if (confirm("¿Confirmar salida del visitante?")) {
                fetch("ajax/registrar_salida.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: "visita_id=" + visitaId
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Error en la respuesta del servidor");
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert("Error al registrar la salida: " + (data.message || "Error desconocido"));
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("Error al procesar la solicitud");
                });
            }
        }
    </script>
    <script src="js/sidebar-menu.js"></script>
    <script src="js/sidebar-pin.js"></script>
</body>
</html> 