<?php
session_start();
require_once '../includes/functions.php';
require_once '../classes/Trabajador.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Obtener empresa_id
$empresa_id = $_GET['empresa_id'] ?? null;

// Validar datos
if (!$empresa_id) {
    echo json_encode(['success' => false, 'message' => 'ID de empresa requerido']);
    exit();
}

try {
    $trabajador = new Trabajador();
    $trabajadores = $trabajador->obtenerPorEmpresa($empresa_id);
    
    // Formatear datos para la respuesta
    $trabajadores_formateados = [];
    foreach ($trabajadores as $trab) {
        $trabajadores_formateados[] = [
            'id' => $trab['id'],
            'nombre' => $trab['nombre'],
            'apellido' => $trab['apellido'],
            'cargo' => $trab['cargo'],
            'tipo_identificacion' => $trab['tipo_identificacion'],
            'numero_identificacion' => $trab['numero_identificacion']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'trabajadores' => $trabajadores_formateados
    ]);
    
} catch (Exception $e) {
    error_log('Error en obtener_trabajadores.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?> 