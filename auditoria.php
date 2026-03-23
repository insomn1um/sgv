<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
// require_once 'includes/audit_functions.php'; // Comentado temporalmente para debugging

if (!isLoggedIn()) {
    redirect('index.php');
}

// Solo administradores pueden ver la auditoría
if (!isAdmin()) {
    showAlert('No tienes permisos para acceder a esta página. Solo administradores pueden ver la auditoría.', 'danger');
    redirect('dashboard.php');
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    error_log("Conexión a base de datos establecida correctamente");
} catch (Exception $e) {
    error_log("Error al conectar a la base de datos: " . $e->getMessage());
    die("Error de conexión a la base de datos");
}

error_log("Iniciando procesamiento de parámetros");
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$tipo_accion = $_GET['tipo_accion'] ?? '';
$usuario_id = $_GET['usuario_id'] ?? '';
error_log("Parámetros procesados: fecha_inicio=$fecha_inicio, fecha_fin=$fecha_fin, tipo_accion=$tipo_accion, usuario_id=$usuario_id");

// Obtener lista de usuarios para filtro
error_log("Iniciando consulta de usuarios");
try {
    // Verificar si la columna 'activo' existe
    $check_activo = $db->prepare("SHOW COLUMNS FROM usuarios LIKE 'activo'");
    $check_activo->execute();
    $columna_activo_existe = $check_activo->fetch();
    
    if ($columna_activo_existe) {
        $stmt = $db->prepare("SELECT id, nombre, apellido FROM usuarios WHERE activo = 1 ORDER BY nombre");
        error_log("Usando filtro WHERE activo = 1");
    } else {
        $stmt = $db->prepare("SELECT id, nombre, apellido FROM usuarios ORDER BY nombre");
        error_log("Columna 'activo' no existe, usando SELECT sin filtro");
    }
    
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Usuarios obtenidos: " . count($usuarios));
} catch (PDOException $e) {
    error_log("Error al obtener usuarios para filtro: " . $e->getMessage());
    $usuarios = [];
}

// Construir consulta con filtros
error_log("Construyendo consulta de auditoría");
$where_conditions = ["DATE(a.fecha) BETWEEN ? AND ?"];
$params = [$fecha_inicio, $fecha_fin];

if ($tipo_accion) {
    $where_conditions[] = "a.tipo_accion = ?";
    $params[] = $tipo_accion;
}

if ($usuario_id) {
    $where_conditions[] = "a.usuario_id = ?";
    $params[] = $usuario_id;
}

$where_clause = implode(" AND ", $where_conditions);
error_log("Where clause construido: " . $where_clause);

$query = "SELECT a.*, u.nombre, u.apellido 
          FROM auditoria a 
          LEFT JOIN usuarios u ON a.usuario_id = u.id 
          WHERE $where_clause 
          ORDER BY a.fecha DESC 
          LIMIT 1000";

error_log("Query construida: " . $query);
error_log("Parámetros: " . implode(', ', $params));

try {
    error_log("Iniciando verificación de tabla auditoria");
    // Primero verificar si la tabla auditoria tiene datos
    $check_stmt = $db->prepare("SELECT COUNT(*) as total FROM auditoria");
    $check_stmt->execute();
    $total_registros = $check_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    error_log("Total de registros en auditoria: " . $total_registros);
    
    error_log("Ejecutando consulta principal de auditoría");
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $auditoria = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Registros encontrados con filtros: " . count($auditoria));
    error_log("Procesamiento de auditoría completado exitosamente");
} catch (PDOException $e) {
    error_log("Error en consulta de auditoría: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $auditoria = [];
    $total_registros = 0;
}

error_log("Iniciando renderizado HTML de auditoría");

// Asegurar que todas las variables estén definidas
$auditoria = $auditoria ?? [];
$total_registros = $total_registros ?? 0;
$usuarios = $usuarios ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría - SGV</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link rel="stylesheet" href="includes/main.css">
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
                <a class="nav-link active" href="auditoria.php">
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
                <h2><i class="fas fa-history"></i> Auditoría del Sistema</h2>
                <div>
                    <button onclick="window.print()" class="btn" style="background-color: #3b82f6; border-color: #3b82f6; color: white;">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                    <a href="insertar_auditoria_ejemplo.php" class="btn btn-success ms-2">
                        <i class="fas fa-plus"></i> Insertar Datos de Ejemplo
                    </a>
                    <button onclick="limpiarAuditoria()" class="btn btn-warning ms-2">
                        <i class="fas fa-trash"></i> Limpiar Auditoría
                    </button>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros</h5>
                </div>
                <div class="card-body">
                    <form class="row g-3" method="GET">
                        <div class="col-md-3">
                            <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                            <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="fecha_fin" class="form-label">Fecha Fin</label>
                            <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="tipo_accion" class="form-label">Tipo de Acción</label>
                            <select id="tipo_accion" name="tipo_accion" class="form-select">
                                <option value="">Todos</option>
                                <option value="login" <?php if($tipo_accion=='login') echo 'selected'; ?>>Inicio de Sesión</option>
                                <option value="logout" <?php if($tipo_accion=='logout') echo 'selected'; ?>>Cierre de Sesión</option>
                                <option value="create" <?php if($tipo_accion=='create') echo 'selected'; ?>>Crear</option>
                                <option value="update" <?php if($tipo_accion=='update') echo 'selected'; ?>>Actualizar</option>
                                <option value="delete" <?php if($tipo_accion=='delete') echo 'selected'; ?>>Eliminar</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="usuario_id" class="form-label">Usuario</label>
                            <select id="usuario_id" name="usuario_id" class="form-select">
                                <option value="">Todos</option>
                                <?php foreach ($usuarios as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php if($usuario_id==$user['id']) echo 'selected'; ?>>
                                        <?php echo $user['nombre'] . ' ' . $user['apellido']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn" style="background-color: #1e293b; border-color: #1e293b; color: white;">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <a href="auditoria.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tabla de auditoría -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-table"></i> Registro de Actividades
                        <span class="badge bg-primary ms-2"><?php echo count($auditoria); ?> registros</span>
                    </h5>
                    <?php if (empty($auditoria)): ?>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Consulta ejecutada: <?php echo htmlspecialchars($query); ?><br>
                            Parámetros: <?php echo implode(', ', $params); ?><br>
                            Total de registros en tabla: <?php echo $total_registros ?? 'N/A'; ?>
                        </small>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha y Hora</th>
                                    <th>Usuario</th>
                                    <th>Acción</th>
                                    <th>Módulo</th>
                                    <th>Descripción</th>
                                    <th>IP</th>
                                    <th>Detalles</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($auditoria)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <p>No se encontraron registros de auditoría</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($auditoria as $registro): ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar"></i> <?php echo formatDate($registro['fecha']); ?>
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock"></i> <?php echo date('H:i:s', strtotime($registro['fecha'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($registro['nombre'] ?? '-'): ?>
                                                    <strong><?php echo htmlspecialchars($registro['nombre'] ?? '-') . ' ' . htmlspecialchars($registro['apellido'] ?? '-'); ?></strong>
                                                <?php else: ?>
                                                    <span class="text-muted">Sistema</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = 'bg-secondary';
                                                switch($registro['tipo_accion'] ?? '-') {
                                                    case 'login': $badge_class = 'bg-success'; break;
                                                    case 'logout': $badge_class = 'bg-warning'; break;
                                                    case 'create': $badge_class = 'bg-primary'; break;
                                                    case 'update': $badge_class = 'bg-info'; break;
                                                    case 'delete': $badge_class = 'bg-danger'; break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?> action-badge">
                                                    <?php echo ucfirst($registro['tipo_accion'] ?? '-'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <?php echo ucfirst($registro['modulo'] ?? '-'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($registro['descripcion'] ?? '-'); ?>">
                                                    <?php echo htmlspecialchars($registro['descripcion'] ?? '-'); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <i class="fas fa-network-wired"></i> <?php echo htmlspecialchars($registro['ip'] ?? '-'); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#detalleModal"
                                                        data-fecha="<?php echo formatDate($registro['fecha']); ?>"
                                                        data-usuario="<?php echo htmlspecialchars($registro['nombre'] ?? '-') . ' ' . htmlspecialchars($registro['apellido'] ?? '-'); ?>"
                                                        data-accion="<?php echo ucfirst($registro['tipo_accion'] ?? '-'); ?>"
                                                        data-modulo="<?php echo ucfirst($registro['modulo'] ?? '-'); ?>"
                                                        data-descripcion="<?php echo htmlspecialchars($registro['descripcion'] ?? '-'); ?>"
                                                        data-ip="<?php echo htmlspecialchars($registro['ip'] ?? '-'); ?>"
                                                        data-datos="<?php echo htmlspecialchars($registro['datos_adicionales'] ?? '-'); ?>">
                                                    <i class="fas fa-eye"></i> Ver
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <!-- Modal de Detalles -->
    <div class="modal fade" id="detalleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle"></i> Detalles del Registro
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fecha y Hora:</label>
                            <p id="modal-fecha"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Usuario:</label>
                            <p id="modal-usuario"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Acción:</label>
                            <p id="modal-accion"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Módulo:</label>
                            <p id="modal-modulo"></p>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Descripción:</label>
                            <p id="modal-descripcion"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Dirección IP:</label>
                            <p id="modal-ip"></p>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Datos Adicionales:</label>
                            <pre id="modal-datos" class="bg-light p-3 rounded"></pre>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
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
            
            // Modal de detalles
            const detalleModal = document.getElementById('detalleModal');
            if (detalleModal) {
                detalleModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const fecha = button.getAttribute('data-fecha');
                    const usuario = button.getAttribute('data-usuario');
                    const accion = button.getAttribute('data-accion');
                    const modulo = button.getAttribute('data-modulo');
                    const descripcion = button.getAttribute('data-descripcion');
                    const ip = button.getAttribute('data-ip');
                    const datos = button.getAttribute('data-datos');
                    
                    const modalFecha = detalleModal.querySelector('#modal-fecha');
                    const modalUsuario = detalleModal.querySelector('#modal-usuario');
                    const modalAccion = detalleModal.querySelector('#modal-accion');
                    const modalModulo = detalleModal.querySelector('#modal-modulo');
                    const modalDescripcion = detalleModal.querySelector('#modal-descripcion');
                    const modalIp = detalleModal.querySelector('#modal-ip');
                    const modalDatos = detalleModal.querySelector('#modal-datos');
                    
                    modalFecha.textContent = fecha;
                    modalUsuario.textContent = usuario || 'Sistema';
                    modalAccion.textContent = accion;
                    modalModulo.textContent = modulo;
                    modalDescripcion.textContent = descripcion;
                    modalIp.textContent = ip;
                    modalDatos.textContent = datos || 'No hay datos adicionales';
                });
            }
        });
        
        // Función para limpiar la tabla de auditoría
        function limpiarAuditoria() {
            if (confirm("¿Está seguro de que desea limpiar toda la tabla de auditoría? Esta acción no se puede deshacer.")) {
                fetch("limpiar_auditoria.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Auditoría limpiada exitosamente");
                        location.reload();
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("Error al limpiar la auditoría");
                });
            }
        }
    </script>
</body>
</html> 