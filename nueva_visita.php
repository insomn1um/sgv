<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Visita.php';
require_once 'classes/Visitante.php';
require_once 'classes/Trabajador.php';
require_once 'classes/Empresa.php';
require_once 'includes/functions.php';

// Verificar si está logueado
if (!isLoggedIn()) {
    redirect('index.php');
}

$database = Database::getInstance();
$db = $database->getConnection();

$visita = new Visita($db);
$visitante = new Visitante($db);
$trabajador = new Trabajador($db);
$empresa = new Empresa($db);

$mensaje = '';
$tipo_mensaje = '';
$errores = [];

// Obtener trabajador_id si se pasa como parámetro
$trabajador_id = isset($_GET['trabajador_id']) ? (int)$_GET['trabajador_id'] : 0;
$trabajador_seleccionado = null;

if ($trabajador_id) {
    $trabajador_seleccionado = $trabajador->obtenerPorId($trabajador_id);
}

// Obtener empresa_id si se pasa como parámetro para preseleccionar
$empresa_preseleccionada = isset($_GET['empresa_id']) ? (int)$_GET['empresa_id'] : 0;

// Obtener empresas y trabajadores para el formulario
$empresas = $empresa->obtenerTodas();
$trabajadores = $trabajador->obtenerTodos();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Campos obligatorios según la base de datos
    // Obtener empresa_id del campo hidden (si el select está deshabilitado) o del select
    $empresa_id = trim($_POST['empresa_id'] ?? $_POST['empresa_id_select'] ?? '');
    $usar_por_defecto = isset($_POST['usar_por_defecto']) ? true : false;
    $trabajador_id = trim($_POST['trabajador_id'] ?? '');
    $nombre_visitante = trim($_POST['nombre_visitante'] ?? '');
    $apellido_visitante = trim($_POST['apellido_visitante'] ?? '');
    $tipo_identificacion = trim($_POST['tipo_identificacion'] ?? '');
    $numero_identificacion = trim($_POST['numero_identificacion'] ?? '');
    $motivo = trim($_POST['motivo'] ?? '');
    
    // Campos opcionales
    $patente = trim($_POST['patente'] ?? '');
    $tipo_vehiculo = trim($_POST['tipo_vehiculo'] ?? '');
    $numero_tarjeta = trim($_POST['numero_tarjeta'] ?? '');
    $fecha_ingreso = $_POST['fecha_ingreso'] ?? '';
    $hora_ingreso = $_POST['hora_ingreso'] ?? '';
    $numero_contacto = trim($_POST['numero_contacto'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $empresa_visitante = trim($_POST['empresa_visitante'] ?? '');
    
    // Nuevos campos contractuales y de seguridad
    $contrato = !empty($_POST['contrato']) ? trim($_POST['contrato']) : null;
    $tipo_contrato = !empty($_POST['tipo_contrato']) ? trim($_POST['tipo_contrato']) : null;
    $contrato_vigente = !empty($_POST['contrato_vigente']) ? trim($_POST['contrato_vigente']) : null;
    $registro_epp = !empty($_POST['registro_epp']) ? $_POST['registro_epp'] : null;
    $registro_riohs = !empty($_POST['registro_riohs']) ? $_POST['registro_riohs'] : null;
    $registro_induccion = !empty($_POST['registro_induccion']) ? $_POST['registro_induccion'] : null;
    $examenes_ocupacionales = !empty($_POST['examenes_ocupacionales']) ? trim($_POST['examenes_ocupacionales']) : null;
    $observaciones = trim($_POST['observaciones'] ?? '');
    
    // Validación de campos obligatorios
    if (empty($empresa_id)) {
        $errores['empresa_id'] = 'La empresa es obligatoria';
    }
    
    // Si se marca "usar por defecto", usar la empresa seleccionada si está aprobada, sino la primera aprobada
    if ($usar_por_defecto) {
        // Verificar si la empresa seleccionada está aprobada
        $empresa_seleccionada_aprobada = false;
        if (!empty($empresa_id)) {
            foreach ($empresas as $emp) {
                if ($emp['id'] == $empresa_id && $emp['condicion'] === 'aprobada') {
                    $empresa_seleccionada_aprobada = true;
                    break;
                }
            }
        }
        
        // Si la empresa seleccionada no está aprobada o no hay empresa seleccionada, buscar la primera aprobada
        if (!$empresa_seleccionada_aprobada) {
            foreach ($empresas as $emp) {
                if ($emp['condicion'] === 'aprobada') {
                    $empresa_id = $emp['id'];
                    break;
                }
            }
        }
        // Si no hay empresas aprobadas, mantener el valor original o vacío
    }
    
    if (empty($trabajador_id)) {
        $errores['trabajador_id'] = 'El trabajador visitado es obligatorio';
    }
    
    if (empty($nombre_visitante)) {
        $errores['nombre_visitante'] = 'El nombre del visitante es obligatorio';
    } elseif (strlen($nombre_visitante) < 2) {
        $errores['nombre_visitante'] = 'El nombre debe tener al menos 2 caracteres';
    }
    
    if (empty($apellido_visitante)) {
        $errores['apellido_visitante'] = 'El apellido del visitante es obligatorio';
    } elseif (strlen($apellido_visitante) < 2) {
        $errores['apellido_visitante'] = 'El apellido debe tener al menos 2 caracteres';
    }
    
    if (empty($tipo_identificacion)) {
        $errores['tipo_identificacion'] = 'El tipo de identificación es obligatorio';
    }
    
    if (empty($numero_identificacion)) {
        $errores['numero_identificacion'] = 'El número de identificación es obligatorio';
    } else {
        // Validación específica según tipo de identificación
        if ($tipo_identificacion === 'rut') {
            if (!validarRUT($numero_identificacion)) {
                $errores['numero_identificacion'] = 'El RUT ingresado no es válido';
            }
        } elseif ($tipo_identificacion === 'pasaporte') {
            if (strlen($numero_identificacion) < 5) {
                $errores['numero_identificacion'] = 'El pasaporte debe tener al menos 5 caracteres';
            }
        } else {
            if (strlen($numero_identificacion) < 3) {
                $errores['numero_identificacion'] = 'La identificación debe tener al menos 3 caracteres';
            }
        }
    }
    
    if (empty($motivo)) {
        $errores['motivo'] = 'El motivo de la visita es obligatorio';
    } elseif (strlen($motivo) < 10) {
        $errores['motivo'] = 'El motivo debe tener al menos 10 caracteres';
    }
    
    // Validación de fecha y hora
    if (empty($fecha_ingreso)) {
        $errores['fecha_ingreso'] = 'La fecha de ingreso es obligatoria';
    } else {
        $fecha_actual = date('Y-m-d');
        if ($fecha_ingreso < $fecha_actual) {
            $errores['fecha_ingreso'] = 'La fecha de ingreso no puede ser anterior a hoy';
        }
    }
    
    if (empty($hora_ingreso)) {
        $errores['hora_ingreso'] = 'La hora de ingreso es obligatoria';
    }
    
    // Validación de patente (si se ingresa)
    if (!empty($patente)) {
        $patente = strtoupper($patente);
        
        // Validación según tipo de identificación
        if ($tipo_identificacion === 'dni') {
            // Formato argentino: 2 letras + 3 números + 2 letras (ej: AB123CD)
            if (!preg_match('/^[A-Z]{2}[0-9]{3}[A-Z]{2}$/', $patente)) {
                $errores['patente'] = 'Formato de patente argentina inválido (ej: AB123CD)';
            }
        } else {
            // Formato chileno: 4 letras + 2 números (ej: ABCD12)
            if (!preg_match('/^[A-Z]{2}[A-Z0-9]{2}[0-9]{2}$/', $patente)) {
                $errores['patente'] = 'Formato de patente chilena inválido (ej: ABCD12)';
            }
        }
    }
    
    // Validación de número de tarjeta (si se ingresa)
    if (!empty($numero_tarjeta)) {
        // Solo permitir letras, números y guiones
        if (!preg_match('/^[A-Za-z0-9\-]+$/', $numero_tarjeta)) {
            $errores['numero_tarjeta'] = 'El número de tarjeta solo puede contener letras, números y guiones';
        }
        // Validar longitud mínima
        if (strlen($numero_tarjeta) < 2) {
            $errores['numero_tarjeta'] = 'El número de tarjeta debe tener al menos 2 caracteres';
        }
    }
    
    // Validación de email (si se ingresa)
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'El formato del email no es válido';
    }
    
    // Validación de teléfono (si se ingresa)
    if (!empty($numero_contacto)) {
        $numero_contacto = preg_replace('/[^0-9+]/', '', $numero_contacto);
        if (strlen($numero_contacto) < 8) {
            $errores['numero_contacto'] = 'El número de contacto debe tener al menos 8 dígitos';
        }
    }
    
    // Si no hay errores, procesar la visita
    if (empty($errores)) {
        try {
            // Crear o verificar visitante
            $visitante->nombre = $nombre_visitante;
            $visitante->apellido = $apellido_visitante;
            $visitante->tipo_identificacion = $tipo_identificacion;
            $visitante->numero_identificacion = $numero_identificacion;
            $visitante->numero_contacto = $numero_contacto;
            $visitante->email = $email;
            $visitante->a_quien_visita = $trabajador_id; // ID del trabajador
            $visitante->motivo_visita = $motivo;
            $visitante->patente_vehiculo = $patente;
            $visitante->registrado_por = $_SESSION['user_id'];
            
            $visitante_id = $visitante->create();
            
            if ($visitante_id) {
                // Crear la visita
                $fecha_hora_ingreso = $fecha_ingreso . ' ' . $hora_ingreso;
                
                $sql = "INSERT INTO visitas (trabajador_id, patente, tipo_vehiculo, numero_tarjeta, fecha_ingreso, estado, a_quien_visita, motivo_visita, observaciones, contrato, tipo_contrato, contrato_vigente, registro_epp, registro_riohs, registro_induccion, examenes_ocupacionales, empresa_visitante, registrado_por) 
                        VALUES (?, ?, ?, ?, ?, 'activa', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                
                if ($stmt->execute([$trabajador_id, $patente, $tipo_vehiculo, $numero_tarjeta, $fecha_hora_ingreso, $nombre_visitante . ' ' . $apellido_visitante, $motivo, $observaciones, $contrato, $tipo_contrato, $contrato_vigente, $registro_epp, $registro_riohs, $registro_induccion, $examenes_ocupacionales, $empresa_visitante, $_SESSION['user_id']])) {
                    $visita_id = $db->lastInsertId();
                    
                    // Registrar auditoría
                    registrarAuditoria('create', 'visitas', 'Nueva visita creada', [
                        'visita_id' => $visita_id,
                        'visitante' => $nombre_visitante . ' ' . $apellido_visitante,
                        'empresa_id' => $empresa_id,
                        'trabajador_id' => $trabajador_id
                    ]);
                    
                    $mensaje = 'Visita registrada exitosamente.';
                    $tipo_mensaje = 'success';
                    
                    // Limpiar formulario
                    $_POST = array();
                    $trabajador_id = 0;
                    $trabajador_seleccionado = null;
                } else {
                    $mensaje = 'Error al registrar la visita.';
                    $tipo_mensaje = 'danger';
                }
            } else {
                $mensaje = 'Error al crear/verificar el visitante.';
                $tipo_mensaje = 'danger';
            }
        } catch (Exception $e) {
            $mensaje = 'Error: ' . $e->getMessage();
            $tipo_mensaje = 'danger';
        }
    } else {
        $mensaje = 'Por favor, corrija los errores en el formulario.';
        $tipo_mensaje = 'danger';
    }
}

// Obtener alerta si existe
$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Visita - SGV</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link rel="stylesheet" href="includes/main.css">
    <style>
        /* Estilos específicos para nueva_visita.php */
        /* Prevenir cualquier transición o colapso en submenús */
        #gestionSubmenu, #visitasSubmenu {
            display: block !important;
            height: auto !important;
            overflow: visible !important;
            transition: none !important;
        }
        
        .sidebar .fa-chevron-down {
            transition: transform 0.3s ease;
        }
        
        /* Estilos para el checkbox de empresa por defecto */
        .form-check-input:checked {
            background-color: #ffc107;
            border-color: #ffc107;
        }
        
        .form-check-input:focus {
            border-color: #ffc107;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
        }
        
        .form-check-label i {
            color: #ffc107;
        }
        
        /* Estilos para select deshabilitado cuando se usa por defecto */
        .form-select.text-muted {
            background-color: #f8f9fa;
            color: #6c757d;
            border-color: #dee2e6;
        }
        
        .form-select:disabled {
            background-color: #f8f9fa;
            color: #6c757d;
            border-color: #dee2e6;
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
                        <a class="nav-link submenu active" href="nueva_visita.php">
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
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0">
                            <i class="fas fa-plus-circle text-primary"></i> Crear Visita
                        </h1>
                        <a href="visitas.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a Visitas
                        </a>
                    </div>

                    <?php if ($mensaje): ?>
                        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                            <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo htmlspecialchars($mensaje); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($alert): ?>
                        <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($alert['message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle"></i> Información de la Visita
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="visitaForm" class="needs-validation" novalidate>
                                <div class="row">
                                    <!-- Columna Izquierda: Información de la Empresa y Trabajador -->
                                    <div class="col-md-6">
                                        <h5 class="mb-3 text-primary">
                                            <i class="fas fa-building"></i> Información de la Empresa
                                        </h5>
                                        
                                        <div class="mb-3">
                                            <label for="empresa_id" class="form-label field-required">
                                                <i class="fas fa-building"></i> Empresa
                                            </label>
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <!-- Campo hidden para mantener el valor cuando el select está deshabilitado -->
                                                    <input type="hidden" name="empresa_id" id="empresa_id_hidden" value="<?php echo isset($_POST['empresa_id']) ? htmlspecialchars($_POST['empresa_id']) : ($empresa_preseleccionada ? $empresa_preseleccionada : ''); ?>">
                                                    <select name="empresa_id_select" id="empresa_id" class="form-select <?php echo isset($errores['empresa_id']) ? 'is-invalid' : ''; ?>" required>
                                                        <option value="">Seleccione una empresa</option>
                                                        <?php 
                                                        $empresa_por_defecto_id = null;
                                                        foreach ($empresas as $emp) {
                                                            if ($emp['condicion'] === 'aprobada' && !$empresa_por_defecto_id) {
                                                                $empresa_por_defecto_id = $emp['id'];
                                                            }
                                                        }
                                                        ?>
                                                        <?php foreach ($empresas as $emp): ?>
                                                            <option value="<?php echo $emp['id']; ?>" 
                                                                    data-condicion="<?php echo htmlspecialchars($emp['condicion']); ?>"
                                                                    <?php 
                                                                        if (isset($_POST['empresa_id']) && $_POST['empresa_id'] == $emp['id']) {
                                                                            echo 'selected';
                                                                        } elseif (!$empresa_preseleccionada && $emp['id'] == $empresa_por_defecto_id) {
                                                                            echo 'selected'; // Primera empresa aprobada como por defecto
                                                                        } elseif ($empresa_preseleccionada && $emp['id'] == $empresa_preseleccionada) {
                                                                            echo 'selected';
                                                                        }
                                                                    ?>>
                                                                <?php echo htmlspecialchars($emp['nombre']); ?> 
                                                                (<?php echo ucfirst($emp['condicion']); ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-check mt-2">
                                                        <input class="form-check-input" type="checkbox" id="usar_por_defecto" name="usar_por_defecto" 
                                                               <?php echo (isset($_POST['usar_por_defecto']) && $_POST['usar_por_defecto']) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="usar_por_defecto">
                                                            <i class="fas fa-star"></i> Usar por defecto
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if (isset($errores['empresa_id'])): ?>
                                                <div class="invalid-feedback"><?php echo $errores['empresa_id']; ?></div>
                                            <?php endif; ?>
                                            <div class="help-text" id="help-empresa">
                                                <i class="fas fa-info-circle"></i> 
                                                Seleccione una empresa o marque "Usar por defecto" para usar la empresa predeterminada
                                            </div>
                                            
                                            <!-- Indicador de estado de la empresa -->
                                            <div id="empresa-status" class="mt-2" style="display: none;">
                                                <div class="alert alert-info py-2 mb-0" role="alert">
                                                    <i class="fas fa-building"></i>
                                                    <strong id="empresa-nombre"></strong>
                                                    <span class="badge ms-2" id="empresa-condicion-badge"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="trabajador_id" class="form-label field-required">
                                                <i class="fas fa-user"></i> Trabajador Visitado
                                            </label>
                                            <select name="trabajador_id" id="trabajador_id" class="form-select <?php echo isset($errores['trabajador_id']) ? 'is-invalid' : ''; ?>" required disabled>
                                                <option value="">Primero seleccione una empresa aprobada</option>
                                            </select>
                                            <?php if (isset($errores['trabajador_id'])): ?>
                                                <div class="invalid-feedback"><?php echo $errores['trabajador_id']; ?></div>
                                            <?php endif; ?>
                                            <div class="help-text" id="help-trabajador">
                                                <i class="fas fa-info-circle"></i> 
                                                El trabajador se habilitará automáticamente al seleccionar una empresa aprobada
                                            </div>
                                            
                                            <!-- Indicador de carga -->
                                            <div id="trabajadores-loading" class="mt-2" style="display: none;">
                                                <div class="alert alert-warning py-2 mb-0" role="alert">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                    Cargando trabajadores...
                                                </div>
                                            </div>
                                            
                                            <!-- Mensaje de empresa no aprobada -->
                                            <div id="empresa-no-aprobada" class="mt-2" style="display: none;">
                                                <div class="alert alert-warning py-2 mb-0" role="alert">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    <strong id="empresa-no-aprobada-nombre"></strong> no está aprobada
                                                    <span class="badge ms-2" id="empresa-no-aprobada-condicion-badge"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="motivo" class="form-label field-required">
                                                <i class="fas fa-comment"></i> Motivo de la Visita
                                            </label>
                                            <textarea name="motivo" id="motivo" class="form-control <?php echo isset($errores['motivo']) ? 'is-invalid' : ''; ?>" rows="3" 
                                                      placeholder="Describa detalladamente el motivo de la visita..." required><?php echo htmlspecialchars($_POST['motivo'] ?? ''); ?></textarea>
                                            <?php if (isset($errores['motivo'])): ?>
                                                <div class="invalid-feedback"><?php echo $errores['motivo']; ?></div>
                                            <?php endif; ?>
                                            <div class="help-text">Mínimo 10 caracteres. Sea específico sobre el propósito de la visita.</div>
                                        </div>
                                    </div>

                                    <!-- Columna Derecha: Información del Visitante -->
                                    <div class="col-md-6">
                                        <h5 class="mb-3 text-primary">
                                            <i class="fas fa-user"></i> Información del Visitante
                                        </h5>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="nombre_visitante" class="form-label field-required">
                                                        <i class="fas fa-user"></i> Nombre
                                                    </label>
                                                    <input type="text" name="nombre_visitante" id="nombre_visitante" class="form-control <?php echo isset($errores['nombre_visitante']) ? 'is-invalid' : ''; ?>" 
                                                           value="<?php echo htmlspecialchars($_POST['nombre_visitante'] ?? ''); ?>" 
                                                           placeholder="Nombre completo" required>
                                                    <?php if (isset($errores['nombre_visitante'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errores['nombre_visitante']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="apellido_visitante" class="form-label field-required">
                                                        <i class="fas fa-user"></i> Apellido
                                                    </label>
                                                    <input type="text" name="apellido_visitante" id="apellido_visitante" class="form-control <?php echo isset($errores['apellido_visitante']) ? 'is-invalid' : ''; ?>" 
                                                           value="<?php echo htmlspecialchars($_POST['apellido_visitante'] ?? ''); ?>" 
                                                           placeholder="Apellido completo" required>
                                                    <?php if (isset($errores['apellido_visitante'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errores['apellido_visitante']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="empresa_visitante" class="form-label">
                                                        <i class="fas fa-building"></i> Empresa del Visitante
                                                    </label>
                                                    <input type="text" name="empresa_visitante" id="empresa_visitante" class="form-control" 
                                                           value="<?php echo htmlspecialchars($_POST['empresa_visitante'] ?? ''); ?>" 
                                                           placeholder="Nombre de la empresa que representa el visitante">
                                                    <div class="help-text">Opcional: Empresa o institución que representa el visitante</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="tipo_identificacion" class="form-label field-required">
                                                        <i class="fas fa-id-card"></i> Tipo de Identificación
                                                    </label>
                                                    <select name="tipo_identificacion" id="tipo_identificacion" class="form-select <?php echo isset($errores['tipo_identificacion']) ? 'is-invalid' : ''; ?>" required>
                                                        <option value="">Seleccione tipo</option>
                                                        <option value="rut" <?php echo ($_POST['tipo_identificacion'] ?? '') == 'rut' ? 'selected' : ''; ?>>RUT Chileno</option>
                                                        <option value="pasaporte" <?php echo ($_POST['tipo_identificacion'] ?? '') == 'pasaporte' ? 'selected' : ''; ?>>Pasaporte</option>
                                                        <option value="dni" <?php echo ($_POST['tipo_identificacion'] ?? '') == 'dni' ? 'selected' : ''; ?>>DNI</option>
                                                        <option value="otro" <?php echo ($_POST['tipo_identificacion'] ?? '') == 'otro' ? 'selected' : ''; ?>>Otro</option>
                                                    </select>
                                                    <?php if (isset($errores['tipo_identificacion'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errores['tipo_identificacion']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="numero_identificacion" class="form-label field-required">
                                                        <i class="fas fa-hashtag"></i> Número de Identificación
                                                    </label>
                                                    <input type="text" name="numero_identificacion" id="numero_identificacion" class="form-control <?php echo isset($errores['numero_identificacion']) ? 'is-invalid' : ''; ?>" 
                                                           value="<?php echo htmlspecialchars($_POST['numero_identificacion'] ?? ''); ?>" 
                                                           placeholder="Ej: 12345678-9" required>
                                                    <?php if (isset($errores['numero_identificacion'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errores['numero_identificacion']; ?></div>
                                                    <?php endif; ?>
                                                    <div class="help-text" id="help-identificacion">Ingrese el número según el tipo seleccionado</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Campos de Contacto (Opcionales) -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="numero_contacto" class="form-label">
                                                        <i class="fas fa-phone"></i> Teléfono de Contacto
                                                        <span class="field-optional">(Opcional)</span>
                                                    </label>
                                                    <input type="tel" name="numero_contacto" id="numero_contacto" class="form-control <?php echo isset($errores['numero_contacto']) ? 'is-invalid' : ''; ?>" 
                                                           value="<?php echo htmlspecialchars($_POST['numero_contacto'] ?? ''); ?>" 
                                                           placeholder="+56 9 1234 5678">
                                                    <?php if (isset($errores['numero_contacto'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errores['numero_contacto']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="email" class="form-label">
                                                        <i class="fas fa-envelope"></i> Email
                                                        <span class="field-optional">(Opcional)</span>
                                                    </label>
                                                    <input type="email" name="email" id="email" class="form-control <?php echo isset($errores['email']) ? 'is-invalid' : ''; ?>" 
                                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                                           placeholder="visitante@email.com">
                                                    <?php if (isset($errores['email'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errores['email']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Campos de Vehículo (Opcionales) -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="patente" class="form-label">
                                                        <i class="fas fa-car"></i> Patente del Vehículo
                                                        <span class="field-optional">(Opcional)</span>
                                                    </label>
                                                    <input type="text" name="patente" id="patente" class="form-control <?php echo isset($errores['patente']) ? 'is-invalid' : ''; ?>" 
                                                           value="<?php echo htmlspecialchars($_POST['patente'] ?? ''); ?>" 
                                                           placeholder="Ej: ABCD12" maxlength="7">
                                                    <?php if (isset($errores['patente'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errores['patente']; ?></div>
                                                    <?php endif; ?>
                                                    <div class="help-text" id="help-patente">Formato chileno: 4 letras + 2 números</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="tipo_vehiculo" class="form-label">
                                                        <i class="fas fa-truck"></i> Tipo de Vehículo
                                                        <span class="field-optional">(Opcional)</span>
                                                    </label>
                                                    <select name="tipo_vehiculo" id="tipo_vehiculo" class="form-select">
                                                        <option value="">Seleccione un tipo</option>
                                                        <option value="auto" <?php echo ($_POST['tipo_vehiculo'] ?? '') == 'auto' ? 'selected' : ''; ?>>Auto</option>
                                                        <option value="camioneta" <?php echo ($_POST['tipo_vehiculo'] ?? '') == 'camioneta' ? 'selected' : ''; ?>>Camioneta</option>
                                                        <option value="camion" <?php echo ($_POST['tipo_vehiculo'] ?? '') == 'camion' ? 'selected' : ''; ?>>Camión</option>
                                                        <option value="moto" <?php echo ($_POST['tipo_vehiculo'] ?? '') == 'moto' ? 'selected' : ''; ?>>Moto</option>
                                                        <option value="furgon" <?php echo ($_POST['tipo_vehiculo'] ?? '') == 'furgon' ? 'selected' : ''; ?>>Furgón</option>
                                                        <option value="van" <?php echo ($_POST['tipo_vehiculo'] ?? '') == 'van' ? 'selected' : ''; ?>>Van</option>
                                                        <option value="otro" <?php echo ($_POST['tipo_vehiculo'] ?? '') == 'otro' ? 'selected' : ''; ?>>Otro</option>
                                                    </select>
                                                    <div class="help-text">Tipo de vehículo con el que ingresa</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Campo de Tarjeta -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="numero_tarjeta" class="form-label">
                                                        <i class="fas fa-credit-card"></i> Número de Tarjeta del Visitante
                                                        <span class="field-optional">(Opcional)</span>
                                                    </label>
                                                    <input type="text" name="numero_tarjeta" id="numero_tarjeta" class="form-control <?php echo isset($errores['numero_tarjeta']) ? 'is-invalid' : ''; ?>" 
                                                           value="<?php echo htmlspecialchars($_POST['numero_tarjeta'] ?? ''); ?>" 
                                                           placeholder="Ej: T001, V123, etc." maxlength="20">
                                                    <?php if (isset($errores['numero_tarjeta'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errores['numero_tarjeta']; ?></div>
                                                    <?php endif; ?>
                                                    <div class="help-text">Identificador único de la tarjeta asignada al visitante</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Fecha y Hora de Ingreso -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="fecha_ingreso" class="form-label field-required">
                                                        <i class="fas fa-calendar"></i> Fecha de Ingreso
                                                    </label>
                                                    <input type="date" name="fecha_ingreso" id="fecha_ingreso" class="form-control <?php echo isset($errores['fecha_ingreso']) ? 'is-invalid' : ''; ?>" 
                                                           value="<?php echo $_POST['fecha_ingreso'] ?? date('Y-m-d'); ?>" required>
                                                    <?php if (isset($errores['fecha_ingreso'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errores['fecha_ingreso']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="hora_ingreso" class="form-label field-required">
                                                        <i class="fas fa-clock"></i> Hora de Ingreso
                                                    </label>
                                                    <input type="time" name="hora_ingreso" id="hora_ingreso" class="form-control <?php echo isset($errores['hora_ingreso']) ? 'is-invalid' : ''; ?>" 
                                                           value="<?php echo $_POST['hora_ingreso'] ?? date('H:i'); ?>" required>
                                                    <?php if (isset($errores['hora_ingreso'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errores['hora_ingreso']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Nueva Sección: Información Contractual y de Seguridad (Solo para Supervisores y Administradores) -->
                                <?php if (!isOperador()): ?>
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header bg-info text-white">
                                                <h5 class="mb-0">
                                                    <i class="fas fa-file-contract"></i> Información Contractual y de Seguridad
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <!-- Información Contractual -->
                                                    <div class="col-md-6">
                                                        <h6 class="text-primary mb-3">
                                                            <i class="fas fa-handshake"></i> Información Contractual
                                                        </h6>
                                                        
                                                        <div class="mb-3">
                                                            <label for="contrato" class="form-label">
                                                                <i class="fas fa-file-signature"></i> ¿Tiene Contrato?
                                                                <span class="field-optional">(Opcional)</span>
                                                            </label>
                                                            <select name="contrato" id="contrato" class="form-select">
                                                                <option value="">Seleccione una opción</option>
                                                                <option value="si" <?php echo ($_POST['contrato'] ?? '') == 'si' ? 'selected' : ''; ?>>Sí</option>
                                                                <option value="no" <?php echo ($_POST['contrato'] ?? '') == 'no' ? 'selected' : ''; ?>>No</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="tipo_contrato" class="form-label">
                                                                <i class="fas fa-calendar-alt"></i> Tipo de Contrato
                                                                <span class="field-optional">(Opcional)</span>
                                                            </label>
                                                            <select name="tipo_contrato" id="tipo_contrato" class="form-select">
                                                                <option value="">Seleccione tipo de contrato</option>
                                                                <option value="indefinido" <?php echo ($_POST['tipo_contrato'] ?? '') == 'indefinido' ? 'selected' : ''; ?>>Indefinido</option>
                                                                <option value="plazo_fijo" <?php echo ($_POST['tipo_contrato'] ?? '') == 'plazo_fijo' ? 'selected' : ''; ?>>Plazo Fijo</option>
                                                                <option value="por_faena" <?php echo ($_POST['tipo_contrato'] ?? '') == 'por_faena' ? 'selected' : ''; ?>>Por Faena</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="contrato_vigente" class="form-label">
                                                                <i class="fas fa-check-circle"></i> ¿Contrato Vigente?
                                                                <span class="field-optional">(Opcional)</span>
                                                            </label>
                                                            <select name="contrato_vigente" id="contrato_vigente" class="form-select">
                                                                <option value="">Seleccione una opción</option>
                                                                <option value="si" <?php echo ($_POST['contrato_vigente'] ?? '') == 'si' ? 'selected' : ''; ?>>Sí</option>
                                                                <option value="no" <?php echo ($_POST['contrato_vigente'] ?? '') == 'no' ? 'selected' : ''; ?>>No</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <!-- Información de Seguridad -->
                                                    <div class="col-md-6">
                                                        <h6 class="text-primary mb-3">
                                                            <i class="fas fa-hard-hat"></i> Documentación de Seguridad
                                                        </h6>
                                                        
                                                        <div class="mb-3">
                                                            <label for="registro_epp" class="form-label">
                                                                <i class="fas fa-hard-hat"></i> Registro Entrega de EPP
                                                                <span class="field-optional">(Opcional)</span>
                                                            </label>
                                                            <input type="date" name="registro_epp" id="registro_epp" class="form-control" 
                                                                   value="<?php echo $_POST['registro_epp'] ?? ''; ?>">
                                                            <div class="help-text">Fecha de entrega de Equipos de Protección Personal</div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="registro_riohs" class="form-label">
                                                                <i class="fas fa-shield-alt"></i> Registro Entrega de RIOHS
                                                                <span class="field-optional">(Opcional)</span>
                                                            </label>
                                                            <input type="date" name="registro_riohs" id="registro_riohs" class="form-control" 
                                                                   value="<?php echo $_POST['registro_riohs'] ?? ''; ?>">
                                                            <div class="help-text">Fecha de entrega de Reglamento Interno de Orden, Higiene y Seguridad</div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="registro_induccion" class="form-label">
                                                                <i class="fas fa-graduation-cap"></i> Registro de Inducción
                                                                <span class="field-optional">(Opcional)</span>
                                                            </label>
                                                            <input type="date" name="registro_induccion" id="registro_induccion" class="form-control" 
                                                                   value="<?php echo $_POST['registro_induccion'] ?? ''; ?>">
                                                            <div class="help-text">Fecha de realización de la inducción de seguridad</div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="examenes_ocupacionales" class="form-label">
                                                                <i class="fas fa-stethoscope"></i> Exámenes Ocupacionales
                                                                <span class="field-optional">(Opcional)</span>
                                                            </label>
                                                            <select name="examenes_ocupacionales" id="examenes_ocupacionales" class="form-select">
                                                                <option value="">Seleccione una opción</option>
                                                                <option value="si" <?php echo ($_POST['examenes_ocupacionales'] ?? '') == 'si' ? 'selected' : ''; ?>>Sí</option>
                                                                <option value="no" <?php echo ($_POST['examenes_ocupacionales'] ?? '') == 'no' ? 'selected' : ''; ?>>No</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Campo de Observaciones -->
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-3">
                                                            <label for="observaciones" class="form-label">
                                                                <i class="fas fa-comment-alt"></i> Observaciones
                                                                <span class="field-optional">(Opcional)</span>
                                                            </label>
                                                            <textarea name="observaciones" id="observaciones" class="form-control" rows="3" 
                                                                      placeholder="Observaciones adicionales sobre la visita, contratos, documentación de seguridad, etc..."><?php echo htmlspecialchars($_POST['observaciones'] ?? ''); ?></textarea>
                                                            <div class="help-text">Información adicional relevante para la visita</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Resumen de Campos -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-info-circle"></i> Resumen de Campos:</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong>Campos Obligatorios:</strong>
                                                    <ul class="mb-0">
                                                        <li>Empresa y Trabajador visitado</li>
                                                        <li>Nombre y Apellido del visitante</li>
                                                        <li>Tipo y Número de identificación</li>
                                                        <li>Motivo de la visita</li>
                                                        <li>Fecha y hora de ingreso</li>
                                                    </ul>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Campos Opcionales:</strong>
                                                    <ul class="mb-0">
                                                        <li>Teléfono de contacto</li>
                                                        <li>Email</li>
                                                        <li>Patente y tipo de vehículo</li>
                                                        <li>Número de tarjeta del visitante</li>
                                                        <li>Información contractual (contrato, tipo, vigencia)</li>
                                                        <li>Documentación de seguridad (EPP, RIOHS, inducción)</li>
                                                        <li>Exámenes ocupacionales</li>
                                                        <li>Observaciones adicionales</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="visitas.php" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Registrar Visita
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        
        // Cerrar sidebar al hacer clic fuera en móvil
        document.addEventListener('click', function(event) {
            if (window.innerWidth < 992) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target) && sidebar.classList.contains('show')) {
                    closeSidebar();
                }
            }
        });
        
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                closeSidebar();
            }
        });
        
        // Auto-seleccionar empresa si se pasa trabajador_id o empresa_id
        <?php if ($trabajador_seleccionado || $empresa_preseleccionada): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const empresaSelect = document.getElementById('empresa_id');
            const trabajadorSelect = document.getElementById('trabajador_id');
            
            if (empresaSelect && trabajadorSelect) {
                let empresaId;
                
                <?php if ($trabajador_seleccionado): ?>
                // Si se pasa trabajador_id, usar su empresa
                empresaId = '<?php echo $trabajador_seleccionado['empresa_id']; ?>';
                <?php elseif ($empresa_preseleccionada): ?>
                // Si se pasa empresa_id, usar esa empresa
                empresaId = '<?php echo $empresa_preseleccionada; ?>';
                <?php endif; ?>
                
                if (empresaId) {
                    // Actualizar el campo hidden con el valor de la empresa
                    const empresaIdHidden = document.getElementById('empresa_id_hidden');
                    if (empresaIdHidden) {
                        empresaIdHidden.value = empresaId;
                    }
                    // Simular cambio de empresa para activar el filtrado
                    empresaSelect.value = empresaId;
                    
                    // Disparar el evento change para cargar trabajadores
                    const event = new Event('change');
                    empresaSelect.dispatchEvent(event);
                    
                    <?php if ($trabajador_seleccionado): ?>
                    // Después de cargar trabajadores, seleccionar el trabajador
                    setTimeout(() => {
                        if (!trabajadorSelect.disabled) {
                            trabajadorSelect.value = '<?php echo $trabajador_seleccionado['id']; ?>';
                        }
                    }, 1000); // Esperar 1 segundo para que se carguen los trabajadores
                    <?php endif; ?>
                }
            }
        });
        <?php endif; ?>
        
        // Validación del formulario mejorada
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('visitaForm');
            const empresaSelect = document.getElementById('empresa_id');
            const trabajadorSelect = document.getElementById('trabajador_id');
            const tipoIdentificacion = document.getElementById('tipo_identificacion');
            const numeroIdentificacion = document.getElementById('numero_identificacion');
            const helpIdentificacion = document.getElementById('help-identificacion');
            const patente = document.getElementById('patente');
            
            // Elementos de UI para estado de empresa
            const empresaStatus = document.getElementById('empresa-status');
            const empresaNombre = document.getElementById('empresa-nombre');
            const empresaCondicionBadge = document.getElementById('empresa-condicion-badge');
            const helpEmpresa = document.getElementById('help-empresa');
            const helpTrabajador = document.getElementById('help-trabajador');
            const trabajadoresLoading = document.getElementById('trabajadores-loading');
            const empresaNoAprobada = document.getElementById('empresa-no-aprobada');
            const empresaNoAprobadaNombre = document.getElementById('empresa-no-aprobada-nombre');
            const empresaNoAprobadaCondicionBadge = document.getElementById('empresa-no-aprobada-condicion-badge');
            
            // Función para limpiar y deshabilitar trabajador
            function limpiarTrabajador() {
                trabajadorSelect.innerHTML = '<option value="">Primero seleccione una empresa aprobada</option>';
                trabajadorSelect.disabled = true;
                trabajadorSelect.value = '';
                trabajadorSelect.classList.remove('is-valid', 'is-invalid');
                
                // Ocultar todos los indicadores
                empresaStatus.style.display = 'none';
                trabajadoresLoading.style.display = 'none';
                empresaNoAprobada.style.display = 'none';
                
                // Restaurar ayuda original
                helpTrabajador.innerHTML = '<i class="fas fa-info-circle"></i> El trabajador se habilitará automáticamente al seleccionar una empresa aprobada';
                helpEmpresa.innerHTML = '<i class="fas fa-info-circle"></i> Seleccione una empresa o marque "Usar por defecto" para habilitar la selección de trabajadores';
                
                // Restaurar estado del select de empresa
                empresaSelect.disabled = false;
                empresaSelect.classList.remove('text-muted');
            }
            
            // Función para obtener trabajadores por empresa
            async function obtenerTrabajadoresPorEmpresa(empresaId, preservarTrabajador = false) {
                try {
                    // Guardar trabajador seleccionado si se debe preservar
                    const trabajadorSeleccionado = preservarTrabajador ? trabajadorSelect.value : null;
                    
                    // Mostrar indicador de carga
                    trabajadoresLoading.style.display = 'block';
                    empresaStatus.style.display = 'none';
                    empresaNoAprobada.style.display = 'none';
                    
                    const formData = new FormData();
                    formData.append('empresa_id', empresaId);
                    
                    const response = await fetch('ajax/obtener_trabajadores_por_empresa.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    // Ocultar indicador de carga
                    trabajadoresLoading.style.display = 'none';
                    
                    if (!response.ok) {
                        throw new Error(`${response.status}: ${data.error || 'Error en la respuesta'}`);
                    }
                    
                    if (data.success) {
                        // Empresa aprobada - mostrar trabajadores
                        const trabajadores = data.trabajadores;
                        
                        // Limpiar y poblar combobox de trabajadores
                        trabajadorSelect.innerHTML = '<option value="">Seleccione un trabajador</option>';
                        
                        if (trabajadores.length > 0) {
                            trabajadores.forEach(trabajador => {
                                const option = document.createElement('option');
                                option.value = trabajador.id;
                                option.textContent = `${trabajador.nombre} ${trabajador.apellido} - ${trabajador.cargo}`;
                                trabajadorSelect.appendChild(option);
                            });
                            
                            // Restaurar trabajador seleccionado si se debe preservar y existe en la lista
                            if (preservarTrabajador && trabajadorSeleccionado) {
                                const trabajadorExiste = trabajadores.some(t => t.id == trabajadorSeleccionado);
                                if (trabajadorExiste) {
                                    trabajadorSelect.value = trabajadorSeleccionado;
                                }
                            }
                            
                            // Habilitar combobox de trabajadores
                            trabajadorSelect.disabled = false;
                            
                            // Mostrar estado de empresa aprobada
                            empresaNombre.textContent = data.empresa.nombre;
                            empresaCondicionBadge.textContent = data.empresa.condicion;
                            empresaCondicionBadge.className = 'badge bg-success ms-2';
                            empresaStatus.style.display = 'block';
                            
                            // Actualizar ayuda
                            helpTrabajador.innerHTML = `<i class="fas fa-check-circle text-success"></i> ${trabajadores.length} trabajador(es) disponible(s) en ${data.empresa.nombre}`;
                            helpEmpresa.innerHTML = `<i class="fas fa-check-circle text-success"></i> Empresa ${data.empresa.nombre} seleccionada y aprobada`;
                            
                        } else {
                            // No hay trabajadores
                            trabajadorSelect.innerHTML = '<option value="">No hay trabajadores disponibles en esta empresa</option>';
                            trabajadorSelect.disabled = true;
                            
                            helpTrabajador.innerHTML = `<i class="fas fa-exclamation-triangle text-warning"></i> No hay trabajadores activos en ${data.empresa.nombre}`;
                        }
                        
                    } else {
                        // Error en la respuesta
                        throw new Error(data.error || 'Error desconocido');
                    }
                    
                } catch (error) {
                    console.error('Error al obtener trabajadores:', error);
                    
                    // Ocultar indicador de carga
                    trabajadoresLoading.style.display = 'none';
                    
                    // Mostrar mensaje de error más específico
                    let errorMessage = 'Error al cargar trabajadores';
                    if (error.message.includes('404')) {
                        errorMessage = 'Empresa no encontrada';
                    } else if (error.message.includes('403')) {
                        errorMessage = 'Empresa no aprobada';
                    } else if (error.message.includes('500')) {
                        errorMessage = 'Error interno del servidor';
                    }
                    
                    trabajadorSelect.innerHTML = `<option value="">${errorMessage}</option>`;
                    trabajadorSelect.disabled = true;
                    
                    helpTrabajador.innerHTML = `<i class="fas fa-exclamation-triangle text-danger"></i> ${errorMessage}`;
                }
            }
            
            // Obtener referencia al checkbox
            const checkboxPorDefecto = document.getElementById('usar_por_defecto');
            
            // Función para manejar el cambio de empresa o checkbox
            function manejarCambioEmpresa() {
                const empresaId = empresaSelect.value;
                const usarPorDefecto = checkboxPorDefecto.checked;
                const selectedOption = empresaSelect.options[empresaSelect.selectedIndex];
                
                if (!empresaId && !usarPorDefecto) {
                    // No se seleccionó empresa ni se marcó por defecto
                    limpiarTrabajador();
                    return;
                }
                
                // Si se marca "usar por defecto", usar la empresa seleccionada si está aprobada, sino la primera aprobada
                if (usarPorDefecto) {
                    let empresaPorDefecto = null;
                    let empresaPorDefectoNombre = '';
                    let empresaCambio = false; // Indica si la empresa cambió
                    
                    // Guardar la empresa actual antes de cambiar
                    const empresaActual = empresaId;
                    
                    // Primero verificar si la empresa actualmente seleccionada está aprobada
                    if (empresaId) {
                        const selectedOption = empresaSelect.options[empresaSelect.selectedIndex];
                        const condicionSeleccionada = selectedOption.getAttribute('data-condicion');
                        if (condicionSeleccionada === 'aprobada') {
                            // Usar la empresa ya seleccionada si está aprobada
                            empresaPorDefecto = empresaId;
                            empresaPorDefectoNombre = selectedOption.textContent.split(' (')[0];
                        } else {
                            // La empresa seleccionada no está aprobada, buscar otra
                            empresaCambio = true;
                        }
                    } else {
                        // No hay empresa seleccionada, buscar la primera aprobada
                        empresaCambio = true;
                    }
                    
                    // Si no hay empresa seleccionada aprobada, buscar la primera empresa aprobada
                    if (!empresaPorDefecto) {
                        for (let i = 0; i < empresaSelect.options.length; i++) {
                            const option = empresaSelect.options[i];
                            const condicion = option.getAttribute('data-condicion');
                            if (condicion === 'aprobada' && option.value) {
                                empresaPorDefecto = option.value;
                                empresaPorDefectoNombre = option.textContent.split(' (')[0];
                                // Verificar si la empresa cambió
                                if (empresaActual && empresaActual !== empresaPorDefecto) {
                                    empresaCambio = true;
                                }
                                break;
                            }
                        }
                    }
                    
                    if (empresaPorDefecto) {
                        // Actualizar el select para mostrar la empresa por defecto seleccionada
                        empresaSelect.value = empresaPorDefecto;
                        
                        // Actualizar el campo hidden con el valor de la empresa por defecto
                        const empresaIdHidden = document.getElementById('empresa_id_hidden');
                        if (empresaIdHidden) {
                            empresaIdHidden.value = empresaPorDefecto;
                        }
                        
                        // Actualizar el texto de ayuda para indicar que está usando por defecto
                        helpEmpresa.innerHTML = `<i class="fas fa-star text-warning"></i> Usando empresa por defecto: ${empresaPorDefectoNombre}`;
                        
                        // Preservar trabajador solo si la empresa no cambió
                        const preservarTrabajador = !empresaCambio && trabajadorSelect.value;
                        obtenerTrabajadoresPorEmpresa(empresaPorDefecto, preservarTrabajador);
                    } else {
                        // No hay empresas aprobadas
                        trabajadorSelect.innerHTML = '<option value="">No hay empresas aprobadas disponibles</option>';
                        trabajadorSelect.disabled = true;
                        helpTrabajador.innerHTML = '<i class="fas fa-exclamation-triangle text-danger"></i> No hay empresas aprobadas disponibles';
                        helpEmpresa.innerHTML = '<i class="fas fa-exclamation-triangle text-danger"></i> No hay empresas aprobadas para usar por defecto';
                    }
                    return;
                }
                
                const condicion = selectedOption.getAttribute('data-condicion');
                
                if (condicion === 'aprobada') {
                    // Actualizar el campo hidden con el valor de la empresa
                    const empresaIdHidden = document.getElementById('empresa_id_hidden');
                    if (empresaIdHidden) {
                        empresaIdHidden.value = empresaId;
                    }
                    // Empresa aprobada - obtener trabajadores
                    obtenerTrabajadoresPorEmpresa(empresaId);
                } else {
                    // Empresa no aprobada - mostrar mensaje
                    trabajadorSelect.innerHTML = '<option value="">Empresa no aprobada</option>';
                    trabajadorSelect.disabled = true;
                    trabajadorSelect.value = '';
                    
                    // Ocultar otros indicadores
                    empresaStatus.style.display = 'none';
                    trabajadoresLoading.style.display = 'none';
                    
                    // Mostrar mensaje de empresa no aprobada
                    empresaNoAprobadaNombre.textContent = selectedOption.textContent.split(' (')[0];
                    empresaNoAprobadaCondicionBadge.textContent = condicion;
                    empresaNoAprobadaCondicionBadge.className = 'badge bg-warning ms-2';
                    empresaNoAprobada.style.display = 'block';
                    
                    // Actualizar ayuda
                    helpTrabajador.innerHTML = `<i class="fas fa-exclamation-triangle text-warning"></i> La empresa seleccionada no está aprobada`;
                    helpEmpresa.innerHTML = `<i class="fas fa-exclamation-triangle text-warning"></i> Solo empresas aprobadas pueden recibir visitas`;
                }
            }
            
            // Event listeners para cambio de empresa y checkbox
            empresaSelect.addEventListener('change', function() {
                // Actualizar el campo hidden cuando cambia el select
                const empresaIdHidden = document.getElementById('empresa_id_hidden');
                if (empresaIdHidden) {
                    empresaIdHidden.value = empresaSelect.value;
                }
                manejarCambioEmpresa();
            });
            checkboxPorDefecto.addEventListener('change', function() {
                if (this.checked) {
                    // Si se marca por defecto, mantener la empresa seleccionada pero deshabilitar el select
                    // Actualizar el campo hidden con el valor actual del select
                    const empresaIdHidden = document.getElementById('empresa_id_hidden');
                    if (empresaIdHidden && empresaSelect.value) {
                        empresaIdHidden.value = empresaSelect.value;
                    }
                    empresaSelect.disabled = true;
                    empresaSelect.classList.add('text-muted');
                } else {
                    // Si se desmarca por defecto, habilitar el select
                    empresaSelect.disabled = false;
                    empresaSelect.classList.remove('text-muted');
                }
                manejarCambioEmpresa();
            });
            
            // Cambiar placeholder y ayuda según tipo de identificación
            tipoIdentificacion.addEventListener('change', function() {
                const tipo = this.value;
                const numero = numeroIdentificacion;
                const patente = document.getElementById('patente');
                const helpPatente = document.getElementById('help-patente');
                
                switch(tipo) {
                    case 'rut':
                        numero.placeholder = 'Ej: 12345678-9';
                        helpIdentificacion.textContent = 'Formato: 12345678-9 (con guión)';
                        patente.placeholder = 'Ej: ABCD12';
                        patente.maxLength = 6;
                        helpPatente.textContent = 'Formato chileno: 4 letras + 2 números';
                        break;
                    case 'pasaporte':
                        numero.placeholder = 'Ej: A12345678';
                        helpIdentificacion.textContent = 'Ingrese el número de pasaporte completo';
                        patente.placeholder = 'Ej: ABCD12';
                        patente.maxLength = 6;
                        helpPatente.textContent = 'Formato chileno: 4 letras + 2 números';
                        break;
                    case 'dni':
                        numero.placeholder = 'Ej: 12345678';
                        helpIdentificacion.textContent = 'Ingrese el número de DNI';
                        patente.placeholder = 'Ej: AB123CD';
                        patente.maxLength = 7;
                        helpPatente.textContent = 'Formato argentino: 2 letras + 3 números + 2 letras';
                        break;
                    default:
                        numero.placeholder = 'Ej: 12345678';
                        helpIdentificacion.textContent = 'Ingrese el número de identificación';
                        patente.placeholder = 'Ej: ABCD12';
                        patente.maxLength = 6;
                        helpPatente.textContent = 'Formato chileno: 4 letras + 2 números';
                }
            });
            
            // Formatear patente automáticamente
            patente.addEventListener('input', function() {
                let value = this.value.toUpperCase();
                // Solo permitir letras y números
                value = value.replace(/[^A-Z0-9]/g, '');
                
                // Limitar según el tipo de identificación
                const tipo = tipoIdentificacion.value;
                const maxLength = tipo === 'dni' ? 7 : 6;
                
                if (value.length > maxLength) {
                    value = value.substring(0, maxLength);
                }
                this.value = value;
            });
            
            // Validación en tiempo real
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                field.addEventListener('blur', function() {
                    validateField(this);
                });
                
                field.addEventListener('input', function() {
                    if (this.classList.contains('is-invalid')) {
                        validateField(this);
                    }
                });
            });
            
            function validateField(field) {
                const value = field.value.trim();
                let isValid = true;
                let errorMessage = '';
                
                // Validaciones específicas por campo
                switch(field.name) {
                    case 'nombre_visitante':
                    case 'apellido_visitante':
                        if (value.length < 2) {
                            isValid = false;
                            errorMessage = 'Debe tener al menos 2 caracteres';
                        }
                        break;
                    case 'motivo':
                        if (value.length < 10) {
                            isValid = false;
                            errorMessage = 'Debe tener al menos 10 caracteres';
                        }
                        break;
                    case 'numero_identificacion':
                        const tipo = tipoIdentificacion.value;
                        if (tipo === 'rut' && !isValidRUT(value)) {
                            isValid = false;
                            errorMessage = 'RUT inválido';
                        } else if (tipo === 'pasaporte' && value.length < 5) {
                            isValid = false;
                            errorMessage = 'Pasaporte debe tener al menos 5 caracteres';
                        } else if (value.length < 3) {
                            isValid = false;
                            errorMessage = 'Debe tener al menos 3 caracteres';
                        }
                        break;
                    case 'fecha_ingreso':
                        const fechaActual = new Date().toISOString().split('T')[0];
                        if (value < fechaActual) {
                            isValid = false;
                            errorMessage = 'La fecha no puede ser anterior a hoy';
                        }
                        break;
                }
                
                // Aplicar validación
                if (isValid) {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                } else {
                    field.classList.remove('is-valid');
                    field.classList.add('is-invalid');
                    
                    // Mostrar mensaje de error personalizado
                    let feedback = field.parentNode.querySelector('.invalid-feedback');
                    if (!feedback) {
                        feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        field.parentNode.appendChild(feedback);
                    }
                    feedback.textContent = errorMessage;
                }
                
                // Retornar el resultado de la validación
                return isValid;
            }
            
            // Validación del formulario al enviar
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!validateField(field)) {
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    // Mostrar mensaje de error general
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <i class="fas fa-exclamation-triangle"></i>
                        Por favor, corrija los errores en el formulario antes de continuar.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    form.parentNode.insertBefore(alertDiv, form);
                    
                    // Scroll al primer error
                    const firstError = form.querySelector('.is-invalid');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
            
            // Función para validar RUT chileno
            function isValidRUT(rut) {
                // Limpiar RUT
                rut = rut.replace(/[.-]/g, '');
                
                if (rut.length < 7) return false;
                
                const dv = rut.slice(-1);
                const numero = rut.slice(0, -1);
                
                if (!/^\d+$/.test(numero)) return false;
                
                let suma = 0;
                let multiplicador = 2;
                
                for (let i = numero.length - 1; i >= 0; i--) {
                    suma += parseInt(numero[i]) * multiplicador;
                    multiplicador = multiplicador === 7 ? 2 : multiplicador + 1;
                }
                
                const dvEsperado = 11 - (suma % 11);
                let dvCalculado;
                
                if (dvEsperado === 11) dvCalculado = '0';
                else if (dvEsperado === 10) dvCalculado = 'K';
                else dvCalculado = dvEsperado.toString();
                
                return dv.toUpperCase() === dvCalculado;
            }
            
            // Inicializar formato de patente según tipo de identificación seleccionado
            function inicializarFormatoPatente() {
                const tipo = tipoIdentificacion.value;
                const patente = document.getElementById('patente');
                const helpPatente = document.getElementById('help-patente');
                
                if (tipo === 'dni') {
                    patente.placeholder = 'Ej: AB123CD';
                    patente.maxLength = 7;
                    helpPatente.textContent = 'Formato argentino: 2 letras + 3 números + 2 letras';
                } else {
                    patente.placeholder = 'Ej: ABCD12';
                    patente.maxLength = 6;
                    helpPatente.textContent = 'Formato chileno: 4 letras + 2 números';
                }
            }
            
            // Ejecutar al cargar la página
            inicializarFormatoPatente();
            
            // Inicializar el estado del checkbox si hay una empresa preseleccionada
            function inicializarCheckboxPorDefecto() {
                const empresaSeleccionada = empresaSelect.value;
                if (empresaSeleccionada) {
                    // Si hay una empresa seleccionada, verificar si es la primera empresa aprobada
                    const primeraEmpresaAprobada = empresaSelect.options[1]; // La primera empresa después de "Seleccione una empresa"
                    if (primeraEmpresaAprobada && primeraEmpresaAprobada.value === empresaSeleccionada) {
                        // Si es la primera empresa aprobada, marcar el checkbox por defecto
                        checkboxPorDefecto.checked = true;
                        empresaSelect.disabled = true;
                        empresaSelect.classList.add('text-muted');
                        helpEmpresa.innerHTML = `<i class="fas fa-star text-warning"></i> Usando empresa por defecto: ${primeraEmpresaAprobada.textContent.split(' (')[0]}`;
                    }
                }
            }
            
            // Ejecutar inicialización del checkbox
            inicializarCheckboxPorDefecto();
        });
    </script>
</body>
</html> 