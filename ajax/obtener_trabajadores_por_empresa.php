<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Trabajador.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar si está logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar si es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener empresa_id del POST
$empresa_id = isset($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : 0;

if (!$empresa_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de empresa requerido']);
    exit;
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Verificar que la empresa existe y está aprobada
    $stmt = $db->prepare("SELECT id, nombre, condicion FROM empresas WHERE id = ?");
    $stmt->execute([$empresa_id]);
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$empresa) {
        http_response_code(404);
        echo json_encode(['error' => 'Empresa no encontrada']);
        exit;
    }
    
    // Solo permitir si la empresa está aprobada
    if ($empresa['condicion'] !== 'aprobada') {
        http_response_code(403);
        echo json_encode([
            'error' => 'Empresa no aprobada',
            'empresa' => $empresa['nombre'],
            'condicion' => $empresa['condicion']
        ]);
        exit;
    }
    
    // Obtener trabajadores de la empresa
    $stmt = $db->prepare("
        SELECT t.id, t.nombre, t.apellido, t.cargo, t.estado
        FROM trabajadores t
        WHERE t.empresa_id = ? AND t.estado = 'activo'
        ORDER BY t.nombre, t.apellido
    ");
    $stmt->execute([$empresa_id]);
    $trabajadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Preparar respuesta
    $response = [
        'success' => true,
        'empresa' => [
            'id' => $empresa['id'],
            'nombre' => $empresa['nombre'],
            'condicion' => $empresa['condicion']
        ],
        'trabajadores' => $trabajadores,
        'total' => count($trabajadores)
    ];
    
    // Registrar auditoría
    registrarAuditoria('consulta', 'trabajadores', 'Consulta de trabajadores por empresa', [
        'empresa_id' => $empresa_id,
        'empresa_nombre' => $empresa['nombre'],
        'total_trabajadores' => count($trabajadores)
    ]);
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno del servidor',
        'message' => $e->getMessage()
    ]);
}
?>
