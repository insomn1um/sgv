<?php
session_start();
require_once '../includes/functions.php';
require_once '../classes/Empresa.php';

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Obtener datos del formulario
$empresa_id = $_POST['empresa_id'] ?? null;
$nueva_condicion = $_POST['nueva_condicion'] ?? null;

// Validar datos
if (!$empresa_id || !$nueva_condicion) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

// Validar condición
$condiciones_validas = ['aprobada', 'pendiente', 'denegada'];
if (!in_array($nueva_condicion, $condiciones_validas)) {
    echo json_encode(['success' => false, 'message' => 'Condición no válida']);
    exit();
}

try {
    $empresa = new Empresa();
    
    // Obtener datos actuales de la empresa
    $empresa_actual = $empresa->obtenerPorId($empresa_id);
    if (!$empresa_actual) {
        echo json_encode(['success' => false, 'message' => 'Empresa no encontrada']);
        exit();
    }
    
    // Actualizar condición
    if ($empresa->actualizarCondicion($empresa_id, $nueva_condicion, $_SESSION['usuario_id'])) {
        // Registrar en auditoría
        registrarAuditoria('update', 'empresas', 'Condición de empresa actualizada', [
            'empresa_id' => $empresa_id,
            'empresa_nombre' => $empresa_actual['nombre'],
            'condicion_anterior' => $empresa_actual['condicion'],
            'condicion_nueva' => $nueva_condicion
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Condición actualizada exitosamente',
            'empresa' => [
                'id' => $empresa_id,
                'nombre' => $empresa_actual['nombre'],
                'condicion' => $nueva_condicion
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la condición']);
    }
    
} catch (Exception $e) {
    error_log('Error en editar_condicion_empresa.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?> 