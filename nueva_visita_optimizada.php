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

// Obtener empresas y trabajadores para el formulario
$empresas = $empresa->obtenerTodas();
$trabajadores = $trabajador->obtenerTodos();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Campos obligatorios según la base de datos
    $empresa_id = trim($_POST['empresa_id'] ?? '');
    $trabajador_id = trim($_POST['trabajador_id'] ?? '');
    $nombre_visitante = trim($_POST['nombre_visitante'] ?? '');
    $apellido_visitante = trim($_POST['apellido_visitante'] ?? '');
    $tipo_identificacion = trim($_POST['tipo_identificacion'] ?? '');
    $numero_identificacion = trim($_POST['numero_identificacion'] ?? '');
    $motivo = trim($_POST['motivo'] ?? '');
    
    // Campos opcionales
    $patente = trim($_POST['patente'] ?? '');
    $tipo_vehiculo = trim($_POST['tipo_vehiculo'] ?? '');
    $fecha_ingreso = $_POST['fecha_ingreso'] ?? '';
    $hora_ingreso = $_POST['hora_ingreso'] ?? '';
    $numero_contacto = trim($_POST['numero_contacto'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Validación de campos obligatorios
    if (empty($empresa_id)) {
        $errores['empresa_id'] = 'La empresa es obligatoria';
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
                
                $sql = "INSERT INTO visitas (trabajador_id, patente, tipo_vehiculo, fecha_ingreso, estado, a_quien_visita, motivo_visita, registrado_por) 
                        VALUES (?, ?, ?, ?, 'activa', ?, ?, ?)";
                $stmt = $db->prepare($sql);
                
                if ($stmt->execute([$trabajador_id, $patente, $tipo_vehiculo, $fecha_hora_ingreso, $nombre_visitante . ' ' . $apellido_visitante, $motivo, $_SESSION['user_id']])) {
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
    <title>Nueva Visita - SGV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/sidebar.css">
    <style>
        .field-required::after {
            content: " *";
            color: #dc3545;
            font-weight: bold;
        }
        .field-optional {
            color: #6c757d;
            font-size: 0.875em;
        }
        .help-text {
            font-size: 0.875em;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        .form-control.is-invalid, .form-select.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-plus-circle text-primary"></i> Nueva Visita - Optimizada
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

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle"></i> Formulario Optimizado de Nueva Visita
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
                                        <select name="empresa_id" id="empresa_id" class="form-select <?php echo isset($errores['empresa_id']) ? 'is-invalid' : ''; ?>" required>
                                            <option value="">Seleccione una empresa</option>
                                            <?php foreach ($empresas as $emp): ?>
                                                <option value="<?php echo $emp['id']; ?>" 
                                                        <?php echo ($_POST['empresa_id'] ?? '') == $emp['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($emp['nombre']); ?> 
                                                    (<?php echo ucfirst($emp['condicion']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (isset($errores['empresa_id'])): ?>
                                            <div class="invalid-feedback"><?php echo $errores['empresa_id']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <label for="trabajador_id" class="form-label field-required">
                                            <i class="fas fa-user"></i> Trabajador Visitado
                                        </label>
                                        <select name="trabajador_id" id="trabajador_id" class="form-select <?php echo isset($errores['trabajador_id']) ? 'is-invalid' : ''; ?>" required>
                                            <option value="">Seleccione un trabajador</option>
                                            <?php foreach ($trabajadores as $trab): ?>
                                                <option value="<?php echo $trab['id']; ?>" 
                                                        <?php echo ($_POST['trabajador_id'] ?? $trabajador_id) == $trab['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($trab['nombre'] . ' ' . $trab['apellido']); ?> 
                                                    - <?php echo htmlspecialchars($trab['empresa_nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (isset($errores['trabajador_id'])): ?>
                                            <div class="invalid-feedback"><?php echo $errores['trabajador_id']; ?></div>
                                        <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación del formulario mejorada
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('visitaForm');
            const tipoIdentificacion = document.getElementById('tipo_identificacion');
            const numeroIdentificacion = document.getElementById('numero_identificacion');
            const helpIdentificacion = document.getElementById('help-identificacion');
            const patente = document.getElementById('patente');
            
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
            }
            
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
        });
    </script>
</body>
</html>
