<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
require_once '../classes/Empresa.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Log de depuración
// file_put_contents(__DIR__ . '/debug_cambiar_estado.log', json_encode($_POST) . PHP_EOL, FILE_APPEND);

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$empresa_id = intval($_POST['empresa_id'] ?? 0);
$nueva_condicion = trim($_POST['nuevo_estado'] ?? '');
$motivo = trim($_POST['motivo'] ?? '');

if (!$empresa_id || !$nueva_condicion || !$motivo) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
    exit;
}

if (!in_array($nueva_condicion, ['aprobada', 'pendiente', 'denegada'])) {
    echo json_encode(['success' => false, 'message' => 'Condición no válida']);
    exit;
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    $empresa = new Empresa($db);
    
    // Obtener información actual de la empresa
    $empresa_actual = $empresa->obtenerPorId($empresa_id);
    if (!$empresa_actual) {
        echo json_encode(['success' => false, 'message' => 'Empresa no encontrada']);
        exit;
    }
    
    $condicion_anterior = $empresa_actual['condicion'];
    
    // Cambiar condición
    if ($empresa->cambiarCondicion($empresa_id, $nueva_condicion)) {
        // Registrar auditoría
        registrarAuditoria('update', 'empresas', 'Cambio de condición de empresa', [
            'empresa_id' => $empresa_id,
            'empresa_nombre' => $empresa_actual['nombre'],
            'condicion_anterior' => $condicion_anterior,
            'condicion_nueva' => $nueva_condicion,
            'motivo' => $motivo,
            'usuario_id' => $_SESSION['user_id']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Condición actualizada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la condición']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor', 'error' => $e->getMessage()]);
}
?> 