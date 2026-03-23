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

$database = Database::getInstance();
$db = $database->getConnection();
$empresa = new Empresa($db);
$trabajador = new Trabajador($db);

// Filtros
$estado = $_GET['estado'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Construir consulta con filtros
$where_conditions = ["1=1"];
$params = [];

if ($estado) {
    $where_conditions[] = "e.estado = ?";
    $params[] = $estado;
}

if ($busqueda) {
    $where_conditions[] = "(e.nombre LIKE ? OR e.razon_social LIKE ? OR e.rut LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
}

$where_clause = implode(" AND ", $where_conditions);

$query = "SELECT e.*, 
                 COUNT(t.id) as total_trabajadores,
                 COUNT(CASE WHEN t.estado = 'activo' THEN 1 END) as trabajadores_activos,
                 MAX(t.fecha_registro) as ultimo_trabajador
          FROM empresas e 
          LEFT JOIN trabajadores t ON e.id = t.empresa_id
          WHERE $where_clause 
          GROUP BY e.id
          ORDER BY e.fecha_registro DESC 
          LIMIT 1000";

$stmt = $db->prepare($query);
$stmt->execute($params);
$empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
$estadisticas = $empresa->getEstadisticas();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empresas y Contratistas - SGV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link rel="stylesheet" href="includes/main.css">
    <style>
        /* Estilos específicos para empresas.php si son necesarios */
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
                <a class="nav-link active" href="empresas.php">
                    <i class="fas fa-building"></i> Empresas y Contratistas
                </a>
                <a class="nav-link" href="trabajadores.php">
                    <i class="fas fa-users"></i> Trabajadores
                </a>
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
                        <h2 class="mb-0">
                            <i class="fas fa-building"></i> Gestión de Empresas y Contratistas
                        </h2>
                        <a href="nueva_empresa.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva Empresa
                        </a>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $estadisticas['total_empresas']; ?></h4>
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
                                    <h4 class="mb-0"><?php echo $estadisticas['empresas_activas']; ?></h4>
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
                                    <h4 class="mb-0"><?php echo $estadisticas['empresas_suspendidas']; ?></h4>
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
                                    <h4 class="mb-0"><?php echo $estadisticas['empresas_bloqueadas']; ?></h4>
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

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="estado" class="form-label">Estado</label>
                            <select name="estado" id="estado" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="activa" <?php echo $estado === 'activa' ? 'selected' : ''; ?>>Activa</option>
                                <option value="suspendida" <?php echo $estado === 'suspendida' ? 'selected' : ''; ?>>Suspendida</option>
                                <option value="bloqueada" <?php echo $estado === 'bloqueada' ? 'selected' : ''; ?>>Bloqueada</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="busqueda" class="form-label">Buscar</label>
                            <input type="text" name="busqueda" id="busqueda" class="form-control" 
                                   placeholder="Nombre, razón social o RUT" value="<?php echo htmlspecialchars($busqueda); ?>">
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

            <!-- Tabla de Empresas -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Empresa</th>
                                    <th>RUT</th>
                                    <th>Estado</th>
                                    <th>Trabajadores</th>
                                    <th>Último Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($empresas as $emp): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($emp['nombre'] ?? '-'); ?></strong>
                                            <?php if ($emp['razon_social']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($emp['razon_social'] ?? '-'); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($emp['rut'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $emp['estado'] === 'activa' ? 'success' : 
                                                ($emp['estado'] === 'suspendida' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($emp['estado'] ?? '-'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $emp['total_trabajadores']; ?> trabajadores
                                        </span>
                                        <?php if ($emp['trabajadores_activos'] > 0): ?>
                                        <br><small class="text-success"><?php echo $emp['trabajadores_activos']; ?> activos</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($emp['ultimo_trabajador']): ?>
                                        <?php echo date('d/m/Y', strtotime($emp['ultimo_trabajador'] ?? '-')); ?>
                                        <?php else: ?>
                                        <span class="text-muted">Sin trabajadores</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="ver_empresa.php?id=<?php echo $emp['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary btn-action">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="editar_empresa.php?id=<?php echo $emp['id']; ?>" 
                                               class="btn btn-sm btn-outline-warning btn-action">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-info btn-action"
                                                    onclick="cambiarEstado(<?php echo $emp['id']; ?>, '<?php echo $emp['estado'] ?? '-'; ?>')">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
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
    <script>
        // Toggle sidebar en móviles
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

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
    <script src="js/sidebar-menu.js"></script>
    <script src="js/sidebar-pin.js"></script>
</body>
</html> 