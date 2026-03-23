<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'classes/Trabajador.php';
require_once 'classes/Empresa.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

// Solo administradores y supervisores pueden editar trabajadores
if (isOperador()) {
    showAlert('No tienes permisos para acceder a esta página. Solo administradores y supervisores pueden gestionar trabajadores.', 'danger');
    redirect('dashboard.php');
}

$database = Database::getInstance();
$db = $database->getConnection();
$trabajador = new Trabajador($db);
$empresa = new Empresa($db);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$datos = $trabajador->obtenerPorId($id);
if (!$datos) {
    showAlert('Trabajador no encontrado', 'danger');
    redirect('trabajadores.php');
}

$empresas = $empresa->obtenerTodas();
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_trabajador'])) {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $tipo_identificacion = strtolower(trim($_POST['tipo_identificacion']));
    $numero_identificacion = trim($_POST['numero_identificacion']);
    $cargo = trim($_POST['cargo']);
    $empresa_id = $_POST['empresa_id'];
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $estado = $_POST['estado'] ?? 'activo';

    if (empty($nombre) || empty($apellido) || empty($tipo_identificacion) || empty($numero_identificacion) || empty($empresa_id)) {
        $mensaje = 'Los campos marcados con * son obligatorios.';
        $tipo_mensaje = 'danger';
    } else {
        $ok = false;
        // Validar duplicidad de identificación
        if ($trabajador->existeIdentificacion($tipo_identificacion, $numero_identificacion, $id)) {
            $mensaje = 'Ya existe un trabajador con la misma identificación.';
            $tipo_mensaje = 'danger';
        } else {
            // Actualizar datos
            $sql = "UPDATE trabajadores SET nombre=?, apellido=?, tipo_identificacion=?, numero_identificacion=?, cargo=?, empresa_id=?, telefono=?, email=?, estado=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $ok = $stmt->execute([$nombre, $apellido, $tipo_identificacion, $numero_identificacion, $cargo, $empresa_id, $telefono ?: null, $email ?: null, $estado, $id]);
        }
        if ($ok) {
            registrarAuditoria('update', 'trabajadores', 'Trabajador actualizado', [
                'id' => $id,
                'nombre' => $nombre . ' ' . $apellido,
                'empresa_id' => $empresa_id
            ]);
            $mensaje = 'Trabajador actualizado correctamente.';
            $tipo_mensaje = 'success';
            // Refrescar datos
            $datos = $trabajador->obtenerPorId($id);
        } else {
            $mensaje = 'Error al actualizar el trabajador.';
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
    <title>Editar Trabajador - SGV</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link rel="stylesheet" href="includes/main.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top shadow" style="background-color: #3b82f6;">
        <div class="container-fluid">
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
    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0">
                                <i class="fas fa-user-edit"></i> Editar Trabajador
                            </h2>
                            <p class="text-muted">Modifica los datos del trabajador</p>
                        </div>
                        <a href="trabajadores.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a Trabajadores
                        </a>
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
                                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($datos['nombre'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="apellido" class="form-label">
                                            <i class="fas fa-user"></i> Apellido *
                                        </label>
                                        <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo htmlspecialchars($datos['apellido'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="tipo_identificacion" class="form-label">
                                            <i class="fas fa-id-card"></i> Tipo de Identificación *
                                        </label>
                                        <select class="form-select" id="tipo_identificacion" name="tipo_identificacion" required>
                                            <option value="">Seleccionar tipo...</option>
                                            <option value="rut" <?php if (strtolower($datos['tipo_identificacion'] ?? '') == 'rut') echo 'selected'; ?>>RUT</option>
                                            <option value="pasaporte" <?php if (strtolower($datos['tipo_identificacion'] ?? '') == 'pasaporte') echo 'selected'; ?>>Pasaporte</option>
                                            <option value="otro" <?php if (strtolower($datos['tipo_identificacion'] ?? '') == 'otro') echo 'selected'; ?>>Otro</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="numero_identificacion" class="form-label">
                                            <i class="fas fa-hashtag"></i> Número de Identificación *
                                        </label>
                                        <input type="text" class="form-control" id="numero_identificacion" name="numero_identificacion" value="<?php echo htmlspecialchars($datos['numero_identificacion'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="cargo" class="form-label">
                                            <i class="fas fa-briefcase"></i> Cargo
                                        </label>
                                        <input type="text" class="form-control" id="cargo" name="cargo" value="<?php echo htmlspecialchars($datos['cargo'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="empresa_id" class="form-label">
                                            <i class="fas fa-building"></i> Empresa *
                                        </label>
                                        <select class="form-select" id="empresa_id" name="empresa_id" required>
                                            <option value="">Seleccionar empresa...</option>
                                            <?php foreach ($empresas as $emp): ?>
                                                <option value="<?php echo $emp['id']; ?>" <?php if (($datos['empresa_id'] ?? '') == $emp['id']) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($emp['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="telefono" class="form-label">
                                            <i class="fas fa-phone"></i> Teléfono
                                        </label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($datos['telefono'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope"></i> Email
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($datos['email'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="estado" class="form-label">
                                        <i class="fas fa-toggle-on"></i> Estado
                                    </label>
                                    <select class="form-select" id="estado" name="estado">
                                        <option value="activo" <?php if (($datos['estado'] ?? '') == 'activo') echo 'selected'; ?>>Activo</option>
                                        <option value="inactivo" <?php if (($datos['estado'] ?? '') == 'inactivo') echo 'selected'; ?>>Inactivo</option>
                                    </select>
                                </div>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="trabajadores.php" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                    <button type="submit" name="actualizar_trabajador" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar Cambios
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
</body>
</html> 