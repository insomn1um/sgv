<?php
/**
 * Funciones de Auditoría para SGV
 * Registra automáticamente las acciones importantes del sistema
 */

/**
 * Registra una acción en la tabla de auditoría
 */
function registrarAuditoria($tipo_accion, $modulo, $descripcion, $datos_adicionales = null, $usuario_id = null) {
    global $db;
    
    if (!$db) {
        $database = Database::getInstance();
        $db = $database->getConnection();
    }
    
    // Si no se especifica usuario, usar el de la sesión
    if ($usuario_id === null && isset($_SESSION['user_id'])) {
        $usuario_id = $_SESSION['user_id'];
    }
    
    // Obtener IP del usuario
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    try {
        $sql = "INSERT INTO auditoria (usuario_id, tipo_accion, modulo, descripcion, datos_adicionales, ip, fecha) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        
        $datos_json = $datos_adicionales ? json_encode($datos_adicionales) : null;
        
        $stmt->execute([
            $usuario_id,
            $tipo_accion,
            $modulo,
            $descripcion,
            $datos_json,
            $ip
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error al registrar auditoría: " . $e->getMessage());
        return false;
    }
}

/**
 * Registra inicio de sesión
 */
function registrarLogin($username, $usuario_id) {
    registrarAuditoria(
        'login',
        'sistema',
        "Inicio de sesión exitoso para usuario: $username",
        ['username' => $username],
        $usuario_id
    );
}

/**
 * Registra cierre de sesión
 */
function registrarLogout($usuario_id) {
    registrarAuditoria(
        'logout',
        'sistema',
        'Cierre de sesión',
        null,
        $usuario_id
    );
}

/**
 * Registra creación de registro
 */
function registrarCreacion($modulo, $descripcion, $datos = null, $usuario_id = null) {
    registrarAuditoria('create', $modulo, $descripcion, $datos, $usuario_id);
}

/**
 * Registra actualización de registro
 */
function registrarActualizacion($modulo, $descripcion, $datos = null, $usuario_id = null) {
    registrarAuditoria('update', $modulo, $descripcion, $datos, $usuario_id);
}

/**
 * Registra eliminación de registro
 */
function registrarEliminacion($modulo, $descripcion, $datos = null, $usuario_id = null) {
    registrarAuditoria('delete', $modulo, $descripcion, $datos, $usuario_id);
}
?>
