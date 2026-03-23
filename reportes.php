<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Visita.php';
require_once 'includes/functions.php';

// Solo administradores y supervisores pueden ver reportes
if (!isSupervisor()) {
    showAlert('No tienes permisos para acceder a esta página. Solo administradores y supervisores pueden ver reportes.', 'danger');
    redirect('dashboard.php');
}

$database = Database::getInstance();
$db = $database->getConnection();
$visita = new Visita($db);

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$tipo_reporte = $_GET['tipo_reporte'] ?? 'general';

// Función para obtener reporte de visitas
function getReporteVisitas($db, $fecha_inicio, $fecha_fin) {
    $sql = "SELECT v.*, t.nombre, t.apellido, t.tipo_identificacion, t.numero_identificacion, t.cargo,
                   e.nombre as empresa_nombre, e.condicion as empresa_condicion
            FROM visitas v 
            JOIN trabajadores t ON v.trabajador_id = t.id 
            JOIN empresas e ON t.empresa_id = e.id
            WHERE DATE(v.fecha_ingreso) BETWEEN ? AND ?
            ORDER BY v.fecha_ingreso DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener estadísticas con fechas
function getEstadisticasConFechas($db, $fecha_inicio, $fecha_fin) {
    $sql = "SELECT 
                'activa' as tipo,
                COUNT(*) as total
            FROM visitas 
            WHERE estado = 'activa' AND DATE(fecha_ingreso) BETWEEN ? AND ?
            UNION ALL
            SELECT 
                'total' as tipo,
                COUNT(*) as total
            FROM visitas 
            WHERE DATE(fecha_ingreso) BETWEEN ? AND ?
            UNION ALL
            SELECT 
                'finalizada' as tipo,
                COUNT(*) as total
            FROM visitas 
            WHERE estado = 'finalizada' AND DATE(fecha_ingreso) BETWEEN ? AND ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $estadisticas = [
        'visitas_activas' => 0,
        'visitas_total' => 0,
        'visitas_finalizadas' => 0
    ];
    
    foreach ($resultados as $row) {
        switch ($row['tipo']) {
            case 'activa':
                $estadisticas['visitas_activas'] = $row['total'];
                break;
            case 'total':
                $estadisticas['visitas_total'] = $row['total'];
                break;
            case 'finalizada':
                $estadisticas['visitas_finalizadas'] = $row['total'];
                break;
        }
    }
    
    return $estadisticas;
}

// Función para obtener estadísticas por empresa
function getEstadisticasPorEmpresa($db, $fecha_inicio, $fecha_fin) {
    $sql = "SELECT 
                e.nombre as empresa,
                COUNT(v.id) as total_visitas,
                COUNT(CASE WHEN v.estado = 'activa' THEN 1 END) as visitas_activas,
                COUNT(CASE WHEN v.estado = 'finalizada' THEN 1 END) as visitas_finalizadas
            FROM visitas v 
            JOIN trabajadores t ON v.trabajador_id = t.id 
            JOIN empresas e ON t.empresa_id = e.id
            WHERE DATE(v.fecha_ingreso) BETWEEN ? AND ?
            GROUP BY e.id, e.nombre
            ORDER BY total_visitas DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener estadísticas por día
function getEstadisticasPorDia($db, $fecha_inicio, $fecha_fin) {
    $sql = "SELECT 
                DATE(v.fecha_ingreso) as fecha,
                COUNT(v.id) as total_visitas,
                COUNT(CASE WHEN v.estado = 'activa' THEN 1 END) as visitas_activas,
                COUNT(CASE WHEN v.estado = 'finalizada' THEN 1 END) as visitas_finalizadas
            FROM visitas v 
            WHERE DATE(v.fecha_ingreso) BETWEEN ? AND ?
            GROUP BY DATE(v.fecha_ingreso)
            ORDER BY fecha";
    $stmt = $db->prepare($sql);
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener estadísticas por motivo
function getEstadisticasPorMotivo($db, $fecha_inicio, $fecha_fin) {
    $sql = "SELECT 
                v.motivo_visita,
                COUNT(v.id) as total_visitas
            FROM visitas v 
            WHERE DATE(v.fecha_ingreso) BETWEEN ? AND ?
            GROUP BY v.motivo_visita
            ORDER BY total_visitas DESC
            LIMIT 10";
    $stmt = $db->prepare($sql);
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Exportar a CSV
if (isset($_GET['exportar']) && $_GET['exportar'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reporte_visitas_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Fecha Ingreso', 'Fecha Salida', 'Tarjeta', 'Visitante', 'Empresa', 'Trabajador Visitado', 'Motivo', 'Estado']);
    
    $reporte = getReporteVisitas($db, $fecha_inicio, $fecha_fin);
    foreach ($reporte as $row) {
        fputcsv($output, [
            date('d/m/Y H:i', strtotime($row['fecha_ingreso'])),
            $row['fecha_salida'] ? date('d/m/Y H:i', strtotime($row['fecha_salida'])) : '-',
            $row['numero_tarjeta'] ?: '-',
            $row['a_quien_visita'] ?: '-',
            $row['empresa_nombre'] ?: '-',
            ($row['nombre'] . ' ' . $row['apellido']) ?: '-',
            $row['motivo_visita'] ?: '-',
            $row['estado'] ?: '-'
        ]);
    }
    fclose($output);
    exit;
}

// Obtener datos según tipo de reporte
switch ($tipo_reporte) {
    case 'por_visitante':
        $query = "SELECT 
                    v.a_quien_visita as visitante,
                    COUNT(v.id) as total_visitas,
                    COUNT(CASE WHEN v.estado = 'activa' THEN 1 END) as visitas_activas,
                    COUNT(CASE WHEN v.estado = 'finalizada' THEN 1 END) as visitas_finalizadas
                  FROM visitas v
                  WHERE DATE(v.fecha_ingreso) BETWEEN ? AND ?
                  GROUP BY v.a_quien_visita
                  ORDER BY total_visitas DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$fecha_inicio, $fecha_fin]);
        $reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'por_motivo':
        $query = "SELECT 
                    v.motivo_visita,
                    COUNT(v.id) as total_visitas
                  FROM visitas v
                  WHERE DATE(v.fecha_ingreso) BETWEEN ? AND ?
                  GROUP BY v.motivo_visita
                  ORDER BY total_visitas DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$fecha_inicio, $fecha_fin]);
        $reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'por_hora':
        $query = "SELECT 
                    HOUR(v.fecha_ingreso) as hora,
                    COUNT(v.id) as total_visitas
                  FROM visitas v
                  WHERE DATE(v.fecha_ingreso) BETWEEN ? AND ?
                  GROUP BY HOUR(v.fecha_ingreso)
                  ORDER BY hora";
        $stmt = $db->prepare($query);
        $stmt->execute([$fecha_inicio, $fecha_fin]);
        $reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    default: // general
        $reporte = getReporteVisitas($db, $fecha_inicio, $fecha_fin);
        break;
}

// Obtener estadísticas generales
$estadisticas = getEstadisticasConFechas($db, $fecha_inicio, $fecha_fin);

// Obtener estadísticas adicionales para gráficos
$estadisticas_empresa = getEstadisticasPorEmpresa($db, $fecha_inicio, $fecha_fin);
$estadisticas_dia = getEstadisticasPorDia($db, $fecha_inicio, $fecha_fin);
$estadisticas_motivo = getEstadisticasPorMotivo($db, $fecha_inicio, $fecha_fin);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - SGV</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link rel="stylesheet" href="includes/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Estilos específicos para gráficos en reportes */
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
                <!-- Gestión Section -->
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
                        <a class="nav-link submenu" href="usuarios.php">
                            <i class="fas fa-user-cog"></i> Usuarios
                        </a>
                    </div>
                </div>
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
                <a class="nav-link active" href="reportes.php">
                    <i class="fas fa-chart-bar"></i> Reportes
                </a>
                <a class="nav-link" href="auditoria.php">
                    <i class="fas fa-history"></i> Auditoría
                </a>
            </nav>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-chart-bar"></i> Reportes de Visitas</h2>
                    <div>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['exportar' => 'csv'])); ?>" class="btn btn-success">
                            <i class="fas fa-download"></i> Exportar CSV
                        </a>
                        <button onclick="window.print()" class="btn btn-secondary">
                            <i class="fas fa-print"></i> Imprimir
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
                                <label for="tipo_reporte" class="form-label">Tipo de Reporte</label>
                                <select id="tipo_reporte" name="tipo_reporte" class="form-select">
                                    <option value="general" <?php if($tipo_reporte=='general') echo 'selected'; ?>>General</option>
                                    <option value="por_visitante" <?php if($tipo_reporte=='por_visitante') echo 'selected'; ?>>Por Visitante o Contratista</option>
                                    <option value="por_motivo" <?php if($tipo_reporte=='por_motivo') echo 'selected'; ?>>Por Motivo</option>
                                    <option value="por_hora" <?php if($tipo_reporte=='por_hora') echo 'selected'; ?>>Por Hora</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Estadísticas Generales -->
                <div class="row mb-4 g-3">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x mb-2"></i>
                                <h4><?php echo $estadisticas['visitas_total']; ?></h4>
                                <p class="mb-0">Total Visitas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card success">
                            <div class="card-body text-center">
                                <i class="fas fa-user-check fa-2x mb-2"></i>
                                <h4><?php echo $estadisticas['visitas_activas']; ?></h4>
                                <p class="mb-0">Visitas Activas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card warning">
                            <div class="card-body text-center">
                                <i class="fas fa-user-clock fa-2x mb-2"></i>
                                <h4><?php echo $estadisticas['visitas_finalizadas']; ?></h4>
                                <p class="mb-0">Visitas Finalizadas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card info">
                            <div class="card-body text-center">
                                <i class="fas fa-building fa-2x mb-2"></i>
                                <h4><?php echo count($estadisticas_empresa); ?></h4>
                                <p class="mb-0">Empresas con Visitas</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Gráficos -->
                <div class="row mb-4 g-3">
                    <!-- Gráfico de Torta - Estado de Visitas -->
                    <div class="col-md-6">
                        <div class="card chart-card">
                            <div class="card-header chart-header">
                                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Estado de Visitas</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="estadoVisitasChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gráfico de Torta - Top Empresas -->
                    <div class="col-md-6">
                        <div class="card chart-card">
                            <div class="card-header chart-header">
                                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Top Empresas por Visitas</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="empresasChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Gráfico de Columnas - Visitas por Día -->
                <div class="row mb-4 g-3">
                    <div class="col-12">
                        <div class="card chart-card">
                            <div class="card-header chart-header">
                                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Visitas por Día</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="visitasPorDiaChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Gráfico de Columnas - Top Motivos -->
                <div class="row mb-4 g-3">
                    <div class="col-12">
                        <div class="card chart-card">
                            <div class="card-header chart-header">
                                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Top Motivos de Visita</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="motivosChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabla de resultados -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-table"></i> 
                            <?php 
                            switch($tipo_reporte) {
                                case 'por_visitante': echo 'Reporte por Visitante o Contratista'; break;
                                case 'por_motivo': echo 'Reporte por Motivo'; break;
                                case 'por_hora': echo 'Reporte por Hora'; break;
                                default: echo 'Reporte General'; break;
                            }
                            ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <?php if ($tipo_reporte == 'por_visitante'): ?>
                                        <tr>
                                            <th>Visitante</th>
                                            <th>Identificación</th>
                                            <th>Total Visitas</th>
                                            <th>Visitas Activas</th>
                                            <th>Visitas Finalizadas</th>
                                        </tr>
                                    <?php elseif ($tipo_reporte == 'por_motivo'): ?>
                                        <tr>
                                            <th>Motivo</th>
                                            <th>Total Visitas</th>
                                            <th>Visitantes Únicos</th>
                                        </tr>
                                    <?php elseif ($tipo_reporte == 'por_hora'): ?>
                                        <tr>
                                            <th>Hora</th>
                                            <th>Total Visitas</th>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <th>Ingreso</th>
                                            <th>Salida</th>
                                            <th>Tarjeta</th>
                                            <th>Visitante</th>
                                            <th>Empresa</th>
                                            <th>Trabajador Visitado</th>
                                            <th>Motivo</th>
                                            <th>Estado</th>
                                        </tr>
                                    <?php endif; ?>
                                </thead>
                                <tbody>
                                    <?php if (!empty($reporte)): ?>
                                        <?php foreach ($reporte as $row): ?>
                                            <?php if ($tipo_reporte == 'por_visitante'): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($row['visitante'] ?? '-'); ?></strong></td>
                                                    <td>-</td>
                                                    <td><span class="badge bg-primary fs-6"><?php echo htmlspecialchars($row['total_visitas'] ?? '-'); ?></span></td>
                                                    <td><span class="badge bg-success"><?php echo htmlspecialchars($row['visitas_activas'] ?? '-'); ?></span></td>
                                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['visitas_finalizadas'] ?? '-'); ?></span></td>
                                                </tr>
                                            <?php elseif ($tipo_reporte == 'por_motivo'): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($row['motivo_visita'] ?? '-'); ?></strong></td>
                                                    <td><span class="badge bg-primary fs-6"><?php echo htmlspecialchars($row['total_visitas'] ?? '-'); ?></span></td>
                                                    <td><span class="badge bg-info">-</span></td>
                                                </tr>
                                            <?php elseif ($tipo_reporte == 'por_hora'): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($row['hora'] ?? '-') . ':00'; ?></strong></td>
                                                    <td><span class="badge bg-primary fs-6"><?php echo htmlspecialchars($row['total_visitas'] ?? '-'); ?></span></td>
                                                </tr>
                                            <?php else: ?>
                                                <tr>
                                                    <td><?php echo $row['fecha_ingreso'] ? formatDate($row['fecha_ingreso']) : '-'; ?></td>
                                                    <td><?php echo $row['fecha_salida'] ? formatDate($row['fecha_salida']) : '-'; ?></td>
                                                    <td><span class="badge bg-info"><?php echo htmlspecialchars($row['numero_tarjeta'] ?? '-'); ?></span></td>
                                                    <td><strong><?php echo htmlspecialchars($row['a_quien_visita'] ?? '-'); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($row['empresa_nombre'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars(($row['nombre'] ?? '') . ' ' . ($row['apellido'] ?? '')); ?></td>
                                                    <td><?php echo htmlspecialchars($row['motivo_visita'] ?? '-'); ?></td>
                                                    <td>
                                                        <?php if ($row['estado'] == 'activa'): ?>
                                                            <span class="badge bg-success">Activa</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Finalizada</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="<?php echo $tipo_reporte == 'por_visitante' ? '5' : ($tipo_reporte == 'por_motivo' ? '3' : ($tipo_reporte == 'por_hora' ? '2' : '9')); ?>" class="text-center text-muted">
                                                <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                                <br>No se encontraron datos para el período seleccionado
                                            </td>
                                        </tr>
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
        }
        
        // Datos para los gráficos
        const estadisticasVisitas = {
            activas: <?php echo $estadisticas['visitas_activas']; ?>,
            finalizadas: <?php echo $estadisticas['visitas_finalizadas']; ?>
        };
        
        const estadisticasEmpresas = <?php echo json_encode(array_slice($estadisticas_empresa, 0, 5)); ?>;
        const estadisticasDia = <?php echo json_encode($estadisticas_dia); ?>;
        const estadisticasMotivo = <?php echo json_encode(array_slice($estadisticas_motivo, 0, 8)); ?>;
        
        // Gráfico de Torta - Estado de Visitas
        const ctxEstadoVisitas = document.getElementById('estadoVisitasChart');
        if (ctxEstadoVisitas) {
            new Chart(ctxEstadoVisitas, {
                type: 'doughnut',
                data: {
                    labels: ['Visitas Activas', 'Visitas Finalizadas'],
                    datasets: [{
                        data: [estadisticasVisitas.activas, estadisticasVisitas.finalizadas],
                        backgroundColor: ['#28a745', '#ffc107'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        // Gráfico de Torta - Top Empresas
        const ctxEmpresas = document.getElementById('empresasChart');
        if (ctxEmpresas) {
            new Chart(ctxEmpresas, {
                type: 'doughnut',
                data: {
                    labels: estadisticasEmpresas.map(item => item.empresa),
                    datasets: [{
                        data: estadisticasEmpresas.map(item => item.total_visitas),
                        backgroundColor: [
                            '#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1',
                            '#fd7e14', '#20c997', '#e83e8c', '#6c757d', '#17a2b8'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 10
                            }
                        }
                    }
                }
            });
        }
        
        // Gráfico de Columnas - Visitas por Día
        const ctxVisitasPorDia = document.getElementById('visitasPorDiaChart');
        if (ctxVisitasPorDia) {
            new Chart(ctxVisitasPorDia, {
                type: 'bar',
                data: {
                    labels: estadisticasDia.map(item => {
                        const fecha = new Date(item.fecha);
                        return fecha.toLocaleDateString('es-ES', { 
                            day: '2-digit', 
                            month: '2-digit' 
                        });
                    }),
                    datasets: [{
                        label: 'Total Visitas',
                        data: estadisticasDia.map(item => item.total_visitas),
                        backgroundColor: '#007bff',
                        borderColor: '#0056b3',
                        borderWidth: 1
                    }, {
                        label: 'Visitas Activas',
                        data: estadisticasDia.map(item => item.visitas_activas),
                        backgroundColor: '#28a745',
                        borderColor: '#1e7e34',
                        borderWidth: 1
                    }, {
                        label: 'Visitas Finalizadas',
                        data: estadisticasDia.map(item => item.visitas_finalizadas),
                        backgroundColor: '#ffc107',
                        borderColor: '#d39e00',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
        }
        
        // Gráfico de Columnas - Top Motivos
        const ctxMotivos = document.getElementById('motivosChart');
        if (ctxMotivos) {
            new Chart(ctxMotivos, {
                type: 'bar',
                data: {
                    labels: estadisticasMotivo.map(item => {
                        // Truncar motivos muy largos
                        const motivo = item.motivo_visita;
                        return motivo.length > 30 ? motivo.substring(0, 30) + '...' : motivo;
                    }),
                    datasets: [{
                        label: 'Total Visitas',
                        data: estadisticasMotivo.map(item => item.total_visitas),
                        backgroundColor: '#6f42c1',
                        borderColor: '#5a2d91',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                afterLabel: function(context) {
                                    const motivo = estadisticasMotivo[context.dataIndex].motivo_visita;
                                    if (motivo.length > 30) {
                                        return 'Motivo completo: ' + motivo;
                                    }
                                    return '';
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html> 