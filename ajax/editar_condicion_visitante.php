<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Visitante.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$visitante_id = intval($_POST['visitante_id'] ?? 0);
$condicion = trim($_POST['condicion'] ?? '');

if ($visitante_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de visitante inválido']);
    exit;
}

if (!in_array($condicion, ['aprobada', 'pendiente', 'denegada'])) {
    echo json_encode(['success' => false, 'message' => 'Condición inválida']);
    exit;
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    $visitante = new Visitante($db);
    
    // Cargar el visitante
    $visitante->id = $visitante_id;
    if (!$visitante->readOne()) {
        echo json_encode(['success' => false, 'message' => 'Visitante no encontrado']);
        exit;
    }
    
    // Guardar la condición anterior para auditoría
    $condicion_anterior = $visitante->condicion;
    
    // Actualizar la condición usando la función específica
    $resultado = $visitante->cambiarCondicion($condicion);
    if ($resultado) {
        // Registrar auditoría
        registrarAuditoria('update', 'visitantes', 'Condición de visitante actualizada', [
            'visitante_id' => $visitante_id,
            'visitante_nombre' => $visitante->nombre . ' ' . $visitante->apellido,
            'condicion_anterior' => $condicion_anterior,
            'condicion_nueva' => $condicion,
            'empresa' => $visitante->empresa_representa
        ]);
        echo json_encode(['success' => true, 'message' => 'Condición actualizada exitosamente', 'debug' => 'Resultado cambiarCondicion: ' . var_export($resultado, true)]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la condición', 'debug' => 'Resultado cambiarCondicion: ' . var_export($resultado, true)]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?> 