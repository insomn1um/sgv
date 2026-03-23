<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'classes/Trabajador.php';
require_once 'classes/Empresa.php';
require_once 'includes/functions.php';

// Verificar si está logueado
if (!isLoggedIn()) {
    redirect('index.php');
}

// Solo administradores y supervisores pueden crear trabajadores
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

// Obtener empresa preseleccionada si se pasa el parámetro
$empresa_preseleccionada = null;
if (isset($_GET['empresa_id']) && !empty($_GET['empresa_id'])) {
    $empresa_preseleccionada = $_GET['empresa_id'];
}

// Obtener lista de empresas para el select
$empresas = $empresa->obtenerTodas();

// Procesar formulario
if ($_POST && isset($_POST['crear_trabajador'])) {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $numero_identificacion = trim($_POST['numero_identificacion']);
    $cargo = trim($_POST['cargo']);
    $empresa_id = $_POST['empresa_id'];
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    
    if (empty($nombre) || empty($apellido) || empty($numero_identificacion) || empty($empresa_id)) {
        $mensaje = 'El nombre, apellido, número de identificación y empresa son campos obligatorios.';
        $tipo_mensaje = 'danger';
    } else {
        $user_id = $_SESSION['user_id'] ?? null;
        
        if ($trabajador->crear($nombre, $apellido, $numero_identificacion, $cargo, $empresa_id, $telefono, $email, $user_id)) {
            if ($user_id) {
                registrarAuditoria('create', 'trabajador', 'Trabajador creado', [
                    'nombre' => $nombre . ' ' . $apellido,
                    'numero_identificacion' => $numero_identificacion,
                    'empresa_id' => $empresa_id
                ]);
            }
            $mensaje = 'Trabajador registrado exitosamente.';
            $tipo_mensaje = 'success';
            
            // Limpiar formulario
            $_POST = array();
        } else {
            $mensaje = 'Error al registrar el trabajador.';
            $tipo_mensaje = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Trabajador - SGV</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link rel="stylesheet" href="includes/main.css">
    <style>
        /* Estilos específicos para nuevo_trabajador.php si son necesarios */
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
                <a class="nav-link" href="empresas.php">
                    <i class="fas fa-building"></i> Empresas y Contratistas
                </a>
                <a class="nav-link active" href="trabajadores.php">
                    <i class="fas fa-users"></i> Trabajadores
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
                    <i class="fas fa-user-cog"></i> Usuarios
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
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0">
                                <i class="fas fa-user-plus"></i> Crear Trabajador
                            </h2>
                            <p class="text-muted">Registrar un nuevo trabajador</p>
                        </div>
                        <?php if (isset($empresa_preseleccionada)): ?>
                            <a href="ver_empresa.php?id=<?php echo $empresa_preseleccionada; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a Empresa
                            </a>
                        <?php else: ?>
                            <a href="trabajadores.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a Trabajadores
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre" class="form-label">
                                            <i class="fas fa-user"></i> Nombre *
                                        </label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" 
                                               value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="apellido" class="form-label">
                                            <i class="fas fa-user"></i> Apellido *
                                        </label>
                                        <input type="text" class="form-control" id="apellido" name="apellido" 
                                               value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="numero_identificacion" class="form-label">
                                            <i class="fas fa-id-card"></i> Número de Identificación *
                                        </label>
                                        <input type="text" class="form-control" id="numero_identificacion" name="numero_identificacion" 
                                               value="<?php echo htmlspecialchars($_POST['numero_identificacion'] ?? ''); ?>" 
                                               placeholder="12.345.678-9" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="cargo" class="form-label">
                                            <i class="fas fa-briefcase"></i> Cargo
                                        </label>
                                        <input type="text" class="form-control" id="cargo" name="cargo" 
                                               value="<?php echo htmlspecialchars($_POST['cargo'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="empresa_id" class="form-label">
                                        <i class="fas fa-building"></i> Empresa *
                                    </label>
                                    <select class="form-select" id="empresa_id" name="empresa_id" required>
                                        <option value="">Seleccionar empresa...</option>
                                        <?php foreach ($empresas as $emp): ?>
                                            <option value="<?php echo $emp['id']; ?>" 
                                                    <?php echo ((isset($_POST['empresa_id']) && $_POST['empresa_id'] == $emp['id']) || 
                                                               (isset($empresa_preseleccionada) && $empresa_preseleccionada == $emp['id'])) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($emp['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="telefono" class="form-label">
                                            <i class="fas fa-phone"></i> Teléfono
                                        </label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono" 
                                               value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope"></i> Email
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <?php if (isset($empresa_preseleccionada)): ?>
                                        <a href="ver_empresa.php?id=<?php echo $empresa_preseleccionada; ?>" class="btn btn-secondary me-md-2">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                    <?php else: ?>
                                        <a href="trabajadores.php" class="btn btn-secondary me-md-2">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                    <?php endif; ?>
                                    <button type="submit" name="crear_trabajador" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar en móviles
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Validación de número de identificación chileno
        document.getElementById('numero_identificacion').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9kK]/g, '');
            if (value.length > 1) {
                value = value.slice(0, -1) + '-' + value.slice(-1);
            }
            if (value.length > 4) {
                value = value.slice(0, 2) + '.' + value.slice(2);
            }
            if (value.length > 8) {
                value = value.slice(0, 6) + '.' + value.slice(6);
            }
            if (value.length > 12) {
                value = value.slice(0, 10) + '.' + value.slice(10);
            }
            e.target.value = value;
        });
    </script>
    <script src="js/sidebar-menu.js"></script>
    <script src="js/sidebar-pin.js"></script>
</body>
</html> 