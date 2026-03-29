<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

if (!isAdmin()) {
    showAlert('No tienes permisos para acceder a la auditoría.', 'danger');
    redirect('dashboard.php');
}

$database = Database::getInstance();
$db = $database->getConnection();

$alert = getAlert();
$registros = [];
$error_auditoria = null;

try {
    $colStmt = $db->query('SHOW COLUMNS FROM auditoria');
    $cols = [];
    while ($row = $colStmt->fetch(PDO::FETCH_ASSOC)) {
        $cols[$row['Field']] = true;
    }

    if (isset($cols['tipo_accion']) && isset($cols['accion'])) {
        $accionSql = 'COALESCE(a.tipo_accion, a.accion)';
    } elseif (isset($cols['tipo_accion'])) {
        $accionSql = 'a.tipo_accion';
    } elseif (isset($cols['accion'])) {
        $accionSql = 'a.accion';
    } else {
        $accionSql = "''";
    }

    if (isset($cols['modulo']) && isset($cols['tabla'])) {
        $moduloSql = 'COALESCE(a.modulo, a.tabla)';
    } elseif (isset($cols['modulo'])) {
        $moduloSql = 'a.modulo';
    } elseif (isset($cols['tabla'])) {
        $moduloSql = 'a.tabla';
    } else {
        $moduloSql = "''";
    }

    if (isset($cols['ip']) && isset($cols['ip_address'])) {
        $ipSql = 'COALESCE(a.ip, a.ip_address)';
    } elseif (isset($cols['ip'])) {
        $ipSql = 'a.ip';
    } elseif (isset($cols['ip_address'])) {
        $ipSql = 'a.ip_address';
    } else {
        $ipSql = "''";
    }

    $fechaSelect = isset($cols['fecha'])
        ? 'a.fecha'
        : (isset($cols['created_at']) ? 'a.created_at' : 'NULL');
    $orderBy = isset($cols['fecha'])
        ? 'a.fecha DESC'
        : (isset($cols['created_at']) ? 'a.created_at DESC' : 'a.id DESC');

    $sql = "SELECT a.id,
                   {$accionSql} AS accion_display,
                   {$moduloSql} AS modulo_display,
                   {$ipSql} AS ip_display,
                   a.descripcion,
                   {$fechaSelect} AS fecha_evento,
                   u.username,
                   u.nombre,
                   u.apellido
            FROM auditoria a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            ORDER BY {$orderBy}
            LIMIT 500";

    $registros = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('auditoria.php: ' . $e->getMessage());
    $error_auditoria = 'No fue posible cargar el registro de auditoría. Verifique que la tabla exista y las credenciales de la base de datos.';
}

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
                <div class="nav-item">
                    <div class="nav-link text-white-50 small fw-bold">
                        <i class="fas fa-cogs"></i> GESTIÓN
                    </div>
                    <div id="gestionSubmenu">
                        <a class="nav-link submenu" href="gestion_empresas.php">
                            <i class="fas fa-building"></i> Empresas
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

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-history"></i> Auditoría del sistema
                </h1>
            </div>

            <?php if ($alert): ?>
                <div class="alert alert-<?php echo htmlspecialchars($alert['type']); ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($alert['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_auditoria): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_auditoria); ?></div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Usuario</th>
                                        <th>Acción</th>
                                        <th>Módulo</th>
                                        <th>IP</th>
                                        <th>Descripción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($registros)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">No hay registros de auditoría.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($registros as $r): ?>
                                            <tr>
                                                <td class="text-nowrap small">
                                                    <?php
                                                    $fe = $r['fecha_evento'] ?? null;
                                                    echo $fe ? htmlspecialchars(formatDate($fe)) : '—';
                                                    ?>
                                                </td>
                                                <td class="small">
                                                    <?php
                                                    $u = trim(($r['nombre'] ?? '') . ' ' . ($r['apellido'] ?? ''));
                                                    if ($u !== '') {
                                                        echo htmlspecialchars($u);
                                                        if (!empty($r['username'])) {
                                                            echo '<br><span class="text-muted">' . htmlspecialchars($r['username']) . '</span>';
                                                        }
                                                    } elseif (!empty($r['username'])) {
                                                        echo htmlspecialchars($r['username']);
                                                    } else {
                                                        echo '—';
                                                    }
                                                    ?>
                                                </td>
                                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($r['accion_display'] ?? ''); ?></span></td>
                                                <td><?php echo htmlspecialchars($r['modulo_display'] ?? ''); ?></td>
                                                <td class="font-monospace small"><?php echo htmlspecialchars($r['ip_display'] ?? ''); ?></td>
                                                <td class="small"><?php echo htmlspecialchars($r['descripcion'] ?? ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <p class="text-muted small mt-2">Mostrando hasta 500 registros más recientes.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/sidebar-menu.js"></script>
    <script src="js/sidebar-pin.js"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            if (window.innerWidth < 992) {
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
                document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
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
        document.addEventListener('click', function(event) {
            if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                    closeSidebar();
                }
            }
        });
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) closeSidebar();
        });
    </script>
</body>
</html>
