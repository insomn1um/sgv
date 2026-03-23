<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Empresa.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

// Solo administradores y supervisores pueden editar empresas
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

$empresa_data = $empresa->obtenerPorId($empresa_id);
if (!$empresa_data) {
    redirect('gestion_empresas.php');
}

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_empresa'])) {
    $nombre = trim($_POST['nombre']);
    $razon_social = trim($_POST['razon_social']);
    $rut = trim($_POST['rut']);
    $direccion = trim($_POST['direccion']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    
    if (empty($nombre) || empty($rut)) {
        $mensaje = 'El nombre y RUT son campos obligatorios.';
        $tipo_mensaje = 'danger';
    } else {
        // Verificar si el RUT ya existe en otra empresa
        $empresa_existente = $empresa->buscar($rut);
        $rut_duplicado = false;
        foreach ($empresa_existente as $emp) {
            if ($emp['id'] != $empresa_id && $emp['rut'] === $rut) {
                $rut_duplicado = true;
                break;
            }
        }
        
        if ($rut_duplicado) {
            $mensaje = 'Error: El RUT ' . htmlspecialchars($rut) . ' ya está registrado en otra empresa.';
            $tipo_mensaje = 'danger';
        } else {
            if ($empresa->actualizar($empresa_id, $nombre, $razon_social, $rut, $direccion, $telefono, $email)) {
                if (isset($_SESSION['user_id'])) {
                    registrarAuditoria('update', 'empresas', 'Empresa actualizada', [
                        'id' => $empresa_id,
                        'nombre' => $nombre,
                        'rut' => $rut
                    ]);
                }
                $mensaje = 'Empresa actualizada exitosamente.';
                $tipo_mensaje = 'success';
                
                // Actualizar datos en la variable
                $empresa_data = $empresa->obtenerPorId($empresa_id);
            } else {
                $mensaje = 'Error al actualizar la empresa.';
                $tipo_mensaje = 'danger';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Empresa - <?php echo htmlspecialchars($empresa_data['nombre']); ?> - SGV</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link rel="stylesheet" href="includes/main.css">
    <style>
        /* Estilos específicos para editar_empresa.php si son necesarios */
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
                                <i class="fas fa-edit"></i> Editar Empresa
                            </h2>
                            <p class="text-muted">Modificar información de <?php echo htmlspecialchars($empresa_data['nombre']); ?></p>
                        </div>
                        <div>
                            <a href="ver_empresa.php?id=<?php echo $empresa_data['id']; ?>" class="btn btn-info me-2">
                                <i class="fas fa-eye"></i> Ver Detalles
                            </a>
                            <a href="gestion_empresas.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a Empresa
                            </a>
                        </div>
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
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-building"></i> Información de la Empresa
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre" class="form-label">
                                            <i class="fas fa-building"></i> Nombre de la Empresa *
                                        </label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" 
                                               value="<?php echo htmlspecialchars($empresa_data['nombre']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="rut" class="form-label">
                                            <i class="fas fa-id-card"></i> RUT *
                                        </label>
                                        <input type="text" class="form-control" id="rut" name="rut" 
                                               value="<?php echo htmlspecialchars($empresa_data['rut']); ?>" 
                                               placeholder="12.345.678-9" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="razon_social" class="form-label">
                                        <i class="fas fa-file-contract"></i> Razón Social
                                    </label>
                                    <input type="text" class="form-control" id="razon_social" name="razon_social" 
                                           value="<?php echo htmlspecialchars($empresa_data['razon_social'] ?? ''); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="direccion" class="form-label">
                                        <i class="fas fa-map-marker-alt"></i> Dirección
                                    </label>
                                    <textarea class="form-control" id="direccion" name="direccion" rows="2"><?php echo htmlspecialchars($empresa_data['direccion'] ?? ''); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="telefono" class="form-label">
                                            <i class="fas fa-phone"></i> Teléfono
                                        </label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono" 
                                               value="<?php echo htmlspecialchars($empresa_data['telefono'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope"></i> Email
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($empresa_data['email'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="gestion_empresas.php" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                    <button type="submit" name="actualizar_empresa" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Actualizar Empresa
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
    <script src="js/sidebar-menu.js"></script>
    <script src="js/sidebar-pin.js"></script>
    <script>
        // Toggle sidebar en móviles
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Validación de RUT chileno
        document.getElementById('rut').addEventListener('input', function(e) {
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
</body>
</html> 