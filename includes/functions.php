<?php

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para verificar el rol del usuario
function getUserRole() {
    return $_SESSION['rol'] ?? null;
}

// Función para verificar si el usuario tiene permisos de administrador
function isAdmin() {
    return getUserRole() === 'admin';
}

// Función para verificar si el usuario tiene permisos de supervisor
function isSupervisor() {
    return getUserRole() === 'supervisor' || getUserRole() === 'admin';
}

// Función para verificar si el usuario es operador
function isOperador() {
    return getUserRole() === 'operador';
}

// Función para registrar auditoría (estructura antigua - mantener para compatibilidad)
function logAuditoria($accion, $tabla, $registro_id = null, $datos_anteriores = null, $datos_nuevos = null) {
    if (!isLoggedIn()) return;
    
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $query = "INSERT INTO auditoria (usuario_id, accion, tabla, descripcion, datos_anteriores, datos_nuevos, ip_address) 
                  VALUES (:usuario_id, :accion, :tabla, :descripcion, :datos_anteriores, :datos_nuevos, :ip_address)";
        
        $stmt = $conn->prepare($query);
        
        // Preparar los valores antes de bindParam
        $descripcion = $accion . ' en ' . $tabla;
        $datos_anteriores_json = $datos_anteriores ? json_encode($datos_anteriores) : null;
        $datos_nuevos_json = $datos_nuevos ? json_encode($datos_nuevos) : null;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        $stmt->bindParam(':usuario_id', $_SESSION['user_id']);
        $stmt->bindParam(':accion', $accion);
        $stmt->bindParam(':tabla', $tabla);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':datos_anteriores', $datos_anteriores_json);
        $stmt->bindParam(':datos_nuevos', $datos_nuevos_json);
        $stmt->bindParam(':ip_address', $ip_address);
        
        $stmt->execute();
        return true;
    } catch (Exception $e) {
        error_log("Error en logAuditoria: " . $e->getMessage());
        return false;
    }
}

// Nueva función para registrar auditoría con la estructura actualizada
function registrarAuditoria($tipo_accion, $modulo, $descripcion, $datos_adicionales = null) {
    if (!isLoggedIn()) return;
    
    try {
        $database = Database::getInstance();
        $db = $database->getConnection();
        
        $query = "INSERT INTO auditoria (usuario_id, accion, tabla, descripcion, datos_anteriores, ip_address) 
                  VALUES (:usuario_id, :accion, :tabla, :descripcion, :datos_anteriores, :ip_address)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':usuario_id' => $_SESSION['user_id'],
            ':accion' => $tipo_accion,
            ':tabla' => $modulo,
            ':descripcion' => $descripcion,
            ':datos_anteriores' => $datos_adicionales ? json_encode($datos_adicionales) : null,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error al registrar auditoría: " . $e->getMessage());
        return false;
    }
}

// Función para generar código QR único
function generateQRCode() {
    return uniqid('QR_', true) . '_' . time();
}

// Función para validar RUT chileno
function validarRUT($rut) {
    $rut = preg_replace('/[^k0-9]/i', '', $rut);
    $dv = substr($rut, -1);
    $numero = substr($rut, 0, strlen($rut) - 1);
    $i = 2;
    $suma = 0;
    
    foreach (array_reverse(str_split($numero)) as $v) {
        if ($i == 8) $i = 2;
        $suma += $v * $i;
        ++$i;
    }
    
    $dvEsperado = 11 - ($suma % 11);
    
    if ($dvEsperado == 11) $dvEsperado = '0';
    if ($dvEsperado == 10) $dvEsperado = 'K';
    
    return strtoupper($dv) == $dvEsperado;
}

/**
 * Normaliza el número de identificación para guardar/buscar de forma consistente.
 * Para RUT: solo dígitos + dígito verificador (K mayúscula), sin puntos ni guion.
 */
function normalizarNumeroIdentificacion($tipo_identificacion, $numero) {
    $tipo = strtolower(trim((string) $tipo_identificacion));
    $numero = trim((string) $numero);
    if ($tipo === 'rut') {
        $limpio = preg_replace('/[^0-9kK]/', '', $numero);
        return $limpio === '' ? '' : strtoupper($limpio);
    }
    return $numero;
}

// Función para formatear fecha
function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

// Función para obtener el nombre del rol
function getRoleName($role) {
    $roles = [
        'admin' => 'Administrador',
        'supervisor' => 'Supervisor',
        'operador' => 'Operador'
    ];
    return $roles[$role] ?? $role;
}

// Función para obtener el nombre del perfil (alias de getRoleName para compatibilidad)
function getProfileName($profile) {
    return getRoleName($profile);
}

// Función para redireccionar
function redirect($url) {
    header("Location: $url");
    exit();
}

// Función para mostrar mensajes de alerta
function showAlert($message, $type = 'info') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Función para obtener y limpiar alertas
function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
} 