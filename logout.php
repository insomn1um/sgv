<?php
session_start();

// Registrar auditoría antes de destruir la sesión
if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    require_once 'includes/functions.php';
    
    try {
        registrarAuditoria('logout', 'sistema', 'Cierre de sesión', [
            'username' => $_SESSION['username'] ?? 'Unknown',
            'session_duration' => time() - ($_SESSION['login_time'] ?? time())
        ]);
    } catch (Exception $e) {
        // Si hay error en la auditoría, continuar con el logout
        error_log("Error en auditoría de logout: " . $e->getMessage());
    }
}

// Destruir la sesión
session_unset();
session_destroy();

// Redirigir al login
header('Location: index.php');
exit(); 