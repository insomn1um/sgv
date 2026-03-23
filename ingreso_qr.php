<?php
require_once 'config/database.php';
require_once 'classes/Visita.php';
require_once 'classes/Visitante.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$database = Database::getInstance();
$db = $database->getConnection();
$visita = new Visita($db);
$visitante = new Visitante($db);

$error = '';
$success = '';
$visitante_info = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_qr = trim($_POST['codigo_qr']);
    
    if (!$codigo_qr) {
        $error = 'Por favor ingrese el código QR.';
    } else {
        // Buscar visitante por código QR
        $stmt = $db->prepare("SELECT v.* FROM visitantes v 
                             INNER JOIN codigos_qr qr ON v.id = qr.visitante_id 
                             WHERE qr.codigo = ? AND qr.usado = 0");
        $stmt->execute([$codigo_qr]);
        $visitante_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$visitante_info) {
            $error = 'Código QR no válido o ya utilizado.';
        } else {
            // Verificar si ya tiene una visita activa
            $stmt = $db->prepare("SELECT id FROM visitas WHERE visitante_id = ? AND estado = 'activa'");
            $stmt->execute([$visitante_info['id']]);
            if ($stmt->fetch()) {
                $error = 'El visitante ya tiene una visita activa.';
            } else {
                // Registrar entrada
                $visita->visitante_id = $visitante_info['id'];
                $visita->numero_tarjeta = generateTarjeta();
                $visita->registrado_por = $_SESSION['user_id'];
                $visita->estado = 'activa';
                
                if ($visita->create()) {
                    // Marcar código QR como usado
                    $stmt = $db->prepare("UPDATE codigos_qr SET usado = 1, fecha_uso = CURRENT_TIMESTAMP WHERE codigo = ?");
                    $stmt->execute([$codigo_qr]);
                    
                    $success = 'Ingreso registrado exitosamente. Tarjeta asignada: ' . $visita->numero_tarjeta;
                    $visitante_info = null; // Limpiar para nuevo ingreso
                } else {
                    $error = 'Error al registrar el ingreso.';
                }
            }
        }
    }
}

function generateTarjeta() {
    return 'T' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso QR - SGV</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link rel="stylesheet" href="includes/main.css">
    <style>
        /* Estilos específicos para ingreso_qr.php */
        /* Prevenir cualquier transición o colapso en submenús */
        #gestionSubmenu, #visitasSubmenu {
            display: block !important;
            height: auto !important;
            overflow: visible !important;
            transition: none !important;
        }
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
                    <h2><i class="fas fa-qrcode"></i> Ingreso por Código QR</h2>
                    <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver al Dashboard</a>
                </div>
                
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Formulario de ingreso -->
                        <div class="card shadow-lg">
                            <div class="card-header " style="background-color: #3b82f6; color: white;">
                                <h4 class="mb-0"><i class="fas fa-qrcode"></i> Escanear Código QR</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="needs-validation" novalidate>
                                    <div class="text-center mb-4">
                                        <i class="fas fa-qrcode fa-4x text-primary mb-3"></i>
                                        <h5>Ingrese el código QR del visitante</h5>
                                        <p class="text-muted">Escaneé o ingrese manualmente el código QR</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-barcode"></i> Código QR *
                                        </label>
                                        <input type="text" name="codigo_qr" class="form-control qr-input" 
                                               placeholder="Ingrese el código QR aquí..." required autofocus>
                                        <div class="invalid-feedback">
                                            Por favor ingrese el código QR.
                                        </div>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle"></i> El código QR debe ser escaneado o ingresado manualmente
                                        </div>
                                    </div>
                                    
                                    <div class="text-center">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-sign-in-alt"></i> Registrar Ingreso
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Información del visitante (si se encontró) -->
                        <?php if ($visitante_info): ?>
                            <div class="card mt-4 shadow-lg">
                                <div class="card-header bg-success text-white">
                                    <h4 class="mb-0"><i class="fas fa-user-check"></i> Información del Visitante</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Nombre:</label>
                                            <p><?php echo $visitante_info['nombre'] . ' ' . $visitante_info['apellido']; ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Identificación:</label>
                                            <p><?php echo $visitante_info['tipo_identificacion'] . ' ' . $visitante_info['numero_identificacion']; ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Empresa:</label>
                                            <p><?php echo $visitante_info['empresa_representa'] ?: 'No especificada'; ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Teléfono:</label>
                                            <p><?php echo $visitante_info['telefono'] ?: 'No especificado'; ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">A quien visita:</label>
                                            <p><?php echo $visitante_info['a_quien_visita']; ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Motivo:</label>
                                            <p><?php echo $visitante_info['motivo_visita']; ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="text-center mt-4">
                                        <form method="POST">
                                            <input type="hidden" name="codigo_qr" value="<?php echo $_POST['codigo_qr']; ?>">
                                            <button type="submit" class="btn btn-success btn-lg">
                                                <i class="fas fa-check"></i> Confirmar Ingreso
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Instrucciones -->
                        <div class="card mt-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Instrucciones</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-list-ol"></i> Pasos a seguir:</h6>
                                        <ol>
                                            <li>Escaneé el código QR del visitante</li>
                                            <li>Verifique la información mostrada</li>
                                            <li>Confirme el ingreso</li>
                                            <li>Asigne la tarjeta de acceso</li>
                                        </ol>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-exclamation-triangle"></i> Notas importantes:</h6>
                                        <ul>
                                            <li>El código QR solo puede usarse una vez</li>
                                            <li>Verifique que el visitante no tenga visitas activas</li>
                                            <li>Asigne una tarjeta de acceso única</li>
                                            <li>Registre la salida cuando corresponda</li>
                                        </ul>
        </div>
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
    <script src="js/sidebar-menu.js"></script>
    <script src="js/sidebar-pin.js"></script>
</body>
</html> 