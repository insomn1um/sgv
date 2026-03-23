<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar que sea administrador
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$database = Database::getInstance();
$db = $database->getConnection();

try {
    // Limpiar toda la tabla de auditoría
    $sql = "TRUNCATE TABLE auditoria";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    // Registrar la acción de limpieza
    $sql_audit = "INSERT INTO auditoria (usuario_id, tipo_accion, modulo, descripcion, ip, fecha) VALUES (?, 'delete', 'auditoria', 'Tabla de auditoría limpiada completamente', ?, NOW())";
    $stmt_audit = $db->prepare($sql_audit);
    $stmt_audit->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
    
    echo json_encode(['success' => true, 'message' => 'Auditoría limpiada exitosamente']);
} catch (PDOException $e) {
    error_log("Error al limpiar auditoría: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al limpiar la auditoría: ' . $e->getMessage()]);
}
?>
