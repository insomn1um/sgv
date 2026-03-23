<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'classes/Trabajador.php';
require_once 'classes/Empresa.php';
require_once 'includes/functions.php';

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    redirect('index.php');
}

// Solo administradores y supervisores pueden gestionar trabajadores
if (isOperador()) {
    showAlert('No tienes permisos para acceder a esta página. Solo administradores y supervisores pueden gestionar trabajadores.', 'danger');
    redirect('dashboard.php');
}

$database = Database::getInstance();
$db = $database->getConnection();

$trabajador = new Trabajador($db);
$empresa = new Empresa($db);
$mensaje = '';
$tipo_mensaje = '';

// Procesar formulario de nuevo trabajador
if ($_POST && isset($_POST['crear_trabajador'])) {
    $empresa_id = $_POST['empresa_id'];
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $tipo_identificacion = $_POST['tipo_identificacion'];
    $numero_identificacion = trim($_POST['numero_identificacion']);
    $cargo = trim($_POST['cargo']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    
    if (empty($nombre) || empty($apellido) || empty($tipo_identificacion) || empty($numero_identificacion) || empty($empresa_id)) {
        $mensaje = 'Los campos marcados con * son obligatorios.';
        $tipo_mensaje = 'danger';
    } elseif ($trabajador->existeIdentificacion($tipo_identificacion, $numero_identificacion)) {
        $mensaje = 'Ya existe un trabajador con esa identificación.';
        $tipo_mensaje = 'danger';
    } else {
        if ($trabajador->crear($nombre, $apellido, $numero_identificacion, $cargo, $empresa_id, $telefono, $email, $_SESSION['user_id'])) {
            registrarAuditoria('create', 'trabajadores', 'Trabajador creado', [
                'nombre' => $nombre . ' ' . $apellido,
                'empresa_id' => $empresa_id
            ]);
            $mensaje = 'Trabajador registrado exitosamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al registrar el trabajador.';
            $tipo_mensaje = 'danger';
        }
    }
}

// Obtener trabajadores
$trabajadores = $trabajador->obtenerTodos();

// Filtrar por empresa si se especifica
if (isset($_GET['empresa_id']) && !empty($_GET['empresa_id'])) {
    $trabajadores = $trabajador->obtenerPorEmpresa($_GET['empresa_id']);
    $empresa_actual = $empresa->obtenerPorId($_GET['empresa_id']);
}

// Búsqueda
$termino_busqueda = '';
if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $termino_busqueda = trim($_GET['buscar']);
    $trabajadores = $trabajador->buscar($termino_busqueda);
}

// Obtener todas las empresas para el formulario
$empresas = $empresa->obtenerTodas();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Trabajadores - SGV</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link rel="stylesheet" href="includes/main.css">
    <style>
        /* Estilos específicos para trabajadores.php si son necesarios */
        .cursor-pointer { cursor: pointer; }
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

    <!-- Overlay para móviles -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-3 mb-4 border-bottom">
                    <h1 class="h2 mb-0">
                        <i class="fas fa-users"></i> Gestión de Trabajadores
                        <?php if (isset($empresa_actual)): ?>
                            <small class="text-muted">- <?php echo htmlspecialchars($empresa_actual['nombre']); ?></small>
                        <?php endif; ?>
                    </h1>
                    <button class="btn btn-primary" id="btnAbrirModalNuevoTrabajador" data-bs-toggle="modal" data-bs-target="#modalNuevoTrabajador">
                        <i class="fas fa-plus"></i> Nuevo Trabajador
                    </button>
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
                            <input type="text" name="buscar" class="form-control me-2" placeholder="Buscar trabajador..." value="<?php echo htmlspecialchars($termino_busqueda); ?>">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6 text-end">
                        <?php if (isset($empresa_actual)): ?>
                            <a href="trabajadores.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Ver todos
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tabla de trabajadores -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Identificación</th>
                                        <th>Cargo</th>
                                        <th>Condición Trabajador</th>
                                        <th>Empresa</th>
                                        <th>Condición Empresa</th>
                                        <th>Contacto</th>
                                        <th>Fecha Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($trabajadores)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">
                                                <i class="fas fa-users fa-2x mb-2"></i>
                                                <br>No se encontraron trabajadores
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($trabajadores as $trab): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars(($trab['nombre'] ?? '-') . ' ' . ($trab['apellido'] ?? '-')); ?></strong>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?php echo htmlspecialchars($trab['tipo_identificacion'] ?? '-'); ?></small><br>
                                                    <?php echo htmlspecialchars($trab['numero_identificacion'] ?? '-'); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($trab['cargo'] ?? '-'); ?></td>
                                                <td>
                                                    <?php
                                                    $estado_trabajador = strtolower($trab['estado'] ?? '');
                                                    switch ($estado_trabajador) {
                                                        case 'activo':
                                                            $badge_class_trab = 'bg-success';
                                                            break;
                                                        case 'inactivo':
                                                            $badge_class_trab = 'bg-danger';
                                                            break;
                                                        case 'suspendido':
                                                            $badge_class_trab = 'bg-warning';
                                                            break;
                                                        default:
                                                            $badge_class_trab = 'bg-secondary';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badge_class_trab; ?>">
                                                        <?php echo ucfirst($trab['estado'] ?? '-'); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($trab['empresa_nombre'] ?? '-'); ?></td>
                                                <td>
                                                    <?php
                                                    $condicion = strtolower($trab['empresa_condicion'] ?? '');
                                                    switch ($condicion) {
                                                        case 'aprobada':
                                                            $badge_class = 'bg-success';
                                                            break;
                                                        case 'pendiente':
                                                            $badge_class = 'bg-warning';
                                                            break;
                                                        case 'denegada':
                                                            $badge_class = 'bg-danger';
                                                            break;
                                                        default:
                                                            $badge_class = 'bg-secondary';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?>">
                                                        <?php echo ucfirst($trab['empresa_condicion'] ?? '-'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($trab['telefono'])): ?>
                                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($trab['telefono']); ?><br>
                                                    <?php endif; ?>
                                                    <?php if (!empty($trab['email'])): ?>
                                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($trab['email']); ?>
                                                    <?php endif; ?>
                                                    <?php if (empty($trab['telefono']) && empty($trab['email'])): ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo !empty($trab['fecha_registro']) ? date('d/m/Y H:i', strtotime($trab['fecha_registro'])) : '-'; ?></td>
                                                <td>
                                                    <a href="ver_trabajador.php?id=<?php echo $trab['id']; ?>" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="editar_trabajador.php?id=<?php echo $trab['id']; ?>" class="btn btn-sm btn-outline-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="visitas.php?trabajador_id=<?php echo $trab['id']; ?>" class="btn btn-sm btn-outline-info" title="Ver visitas">
                                                        <i class="fas fa-calendar-check"></i>
                                                    </a>
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

    <!-- Modal Nuevo Trabajador -->
    <div class="modal fade" id="modalNuevoTrabajador" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus"></i> Nuevo Trabajador
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="empresa_id" class="form-label">
                                        <i class="fas fa-building"></i> Empresa *
                                    </label>
                                    <select name="empresa_id" id="empresa_id" class="form-select" required>
                                        <option value="">Seleccione una empresa</option>
                                        <?php foreach ($empresas as $emp): ?>
                                            <option value="<?php echo $emp['id']; ?>">
                                                <?php echo htmlspecialchars($emp['nombre']); ?> 
                                                (<?php echo ucfirst($emp['condicion']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">
                                        <i class="fas fa-user"></i> Nombre *
                                    </label>
                                    <input type="text" name="nombre" id="nombre" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="apellido" class="form-label">
                                        <i class="fas fa-user"></i> Apellido *
                                    </label>
                                    <input type="text" name="apellido" id="apellido" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="cargo" class="form-label">
                                        <i class="fas fa-briefcase"></i> Cargo
                                    </label>
                                    <input type="text" name="cargo" id="cargo" class="form-control" placeholder="Ej: Técnico, Supervisor, etc.">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tipo_identificacion" class="form-label">
                                        <i class="fas fa-id-card"></i> Tipo de Identificación *
                                    </label>
                                    <select name="tipo_identificacion" id="tipo_identificacion" class="form-select" required>
                                        <option value="">Seleccione tipo</option>
                                        <option value="RUT">RUT</option>
                                        <option value="DNI">DNI</option>
                                        <option value="PASAPORTE">Pasaporte</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="numero_identificacion" class="form-label">
                                        <i class="fas fa-hashtag"></i> Número de Identificación *
                                    </label>
                                    <input type="text" name="numero_identificacion" id="numero_identificacion" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">
                                        <i class="fas fa-phone"></i> Teléfono
                                    </label>
                                    <input type="tel" name="telefono" id="telefono" class="form-control" placeholder="+56912345678">
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope"></i> Email
                                    </label>
                                    <input type="email" name="email" id="email" class="form-control" placeholder="trabajador@empresa.com">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_trabajador" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
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
    
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            closeSidebar();
        }
    });
    
    // Mejorar accesibilidad: mover el foco al botón de abrir modal al cerrar el modal
    var modal = document.getElementById('modalNuevoTrabajador');
    var btnAbrir = document.getElementById('btnAbrirModalNuevoTrabajador');
    if (modal && btnAbrir) {
        modal.addEventListener('hidden.bs.modal', function () {
            btnAbrir.focus();
        });
    }
    </script>
</body>
</html> 