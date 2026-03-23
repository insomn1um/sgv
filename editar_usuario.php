<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Usuario.php';
require_once 'includes/functions.php';

// Solo administradores pueden editar usuarios
if (!isAdmin()) {
    showAlert('No tienes permisos para acceder a esta página. Solo administradores pueden gestionar usuarios.', 'danger');
    redirect('dashboard.php');
}

$database = Database::getInstance();
$db = $database->getConnection();
$usuario = new Usuario($db);

$id = intval($_GET['id'] ?? 0);
if (!$id) redirect('usuarios.php');
$usuario->id = $id;
if (!$usuario->readOne()) redirect('usuarios.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario->nombre = trim($_POST['nombre']);
    $usuario->apellido = trim($_POST['apellido']);
    $usuario->email = trim($_POST['email']);
    $usuario->rol = $_POST['rol'];
    $usuario->activo = isset($_POST['activo']) ? 1 : 0;
    if (!$usuario->nombre || !$usuario->apellido || !$usuario->email || !$usuario->rol) {
        $error = 'Todos los campos son obligatorios.';
    } elseif ($usuario->emailExists($usuario->email, $usuario->id)) {
        $error = 'El email ya está registrado.';
    } else {
        if ($usuario->update()) {
            if (!empty($_POST['password'])) {
                $usuario->changePassword($_POST['password']);
            }
            showAlert('Usuario actualizado exitosamente.','success');
            redirect('usuarios.php');
        } else {
            $error = 'Error al actualizar el usuario.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - SGV</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link rel="stylesheet" href="includes/main.css">
    <style>
        /* Estilos específicos para editar_usuario.php si son necesarios */
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
                        <a class="nav-link submenu active" href="usuarios.php">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-edit"></i> Editar Usuario</h2>
                    <a href="usuarios.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a Usuarios</a>
                </div>
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card shadow-lg">
                            <div class="card-header bg-warning text-dark">
                                <h4 class="mb-0"><i class="fas fa-edit"></i> Información del Usuario</h4>
                            </div>
                            <div class="card-body">
                                <?php if ($error): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                <form method="POST" class="needs-validation" novalidate>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="nombre">
                                                <i class="fas fa-id-card"></i> Nombre *
                                            </label>
                                            <input type="text" name="nombre" id="nombre" class="form-control" value="<?php echo htmlspecialchars($usuario->nombre); ?>" required>
                                            <div class="invalid-feedback">
                                                Por favor ingrese el nombre.
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="apellido">
                                                <i class="fas fa-id-card"></i> Apellido *
                                            </label>
                                            <input type="text" name="apellido" id="apellido" class="form-control" value="<?php echo htmlspecialchars($usuario->apellido); ?>" required>
                                            <div class="invalid-feedback">
                                                Por favor ingrese el apellido.
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="email">
                                                <i class="fas fa-envelope"></i> Email *
                                            </label>
                                            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($usuario->email); ?>" required>
                                            <div class="invalid-feedback">
                                                Por favor ingrese un email válido.
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="rol">
                                                <i class="fas fa-user-tag"></i> Rol *
                                            </label>
                                            <select name="rol" id="rol" class="form-select" required>
                                                <option value="admin" <?php if($usuario->rol=='admin') echo 'selected'; ?>>Administrador</option>
                                                <option value="supervisor" <?php if($usuario->rol=='supervisor') echo 'selected'; ?>>Supervisor</option>
                                                <option value="operador" <?php if($usuario->rol=='operador') echo 'selected'; ?>>Operador</option>
                                            </select>
                                            <div class="invalid-feedback">
                                                Por favor seleccione un rol.
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="password">
                                                <i class="fas fa-lock"></i> Contraseña (dejar en blanco para no cambiar)
                                            </label>
                                            <input type="password" name="password" id="password" class="form-control">
                                            <div class="form-text">
                                                <i class="fas fa-info-circle"></i> Solo complete si desea cambiar la contraseña.
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox" name="activo" id="activo" <?php if($usuario->activo) echo 'checked'; ?>>
                                                <label class="form-check-label" for="activo">
                                                    <i class="fas fa-toggle-on"></i> Usuario activo
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4 d-flex justify-content-between">
                                        <a href="usuarios.php" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-save"></i> Guardar Cambios
                                        </button>
                                    </div>
                                </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/sidebar-menu.js"></script>
    <script src="js/sidebar-pin.js"></script>
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
        
        // Función para registrar salida (solo si existe)
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
        }</script>
</body>
</html> 