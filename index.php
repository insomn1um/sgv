<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'classes/Usuario.php';
require_once 'includes/functions.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_POST) {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    $usuario = new Usuario($db);
    
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor complete todos los campos.';
    } else {
        if ($usuario->login($username, $password)) {
            registrarAuditoria('login', 'sistema', 'Inicio de sesión exitoso', [
                'username' => $username,
                'browser' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
            redirect('dashboard.php');
        } else {
            registrarAuditoria('login', 'sistema', 'Intento de inicio de sesión fallido', [
                'username' => $username,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            ]);
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGV - Sistema de Gestión de Visitas</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/main.css">
    <link rel="stylesheet" href="includes/login.css">
    <style>
        /* Estilos específicos adicionales para index.php si son necesarios */
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-building fa-3x mb-3"></i>
            <h2>SGV</h2>
            <p class="mb-0">Sistema de Gestión de Visitas</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label visually-hidden">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" id="username" class="form-control" name="username" placeholder="Usuario" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label visually-hidden">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" id="password" class="form-control" name="password" placeholder="Contraseña" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>
            
            <div class="text-center mt-4">
                <a href="preregistro.php" class="text-decoration-none">
                    <i class="fas fa-qrcode"></i> Preregistro de Visitas
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 