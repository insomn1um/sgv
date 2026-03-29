<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Visitante.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$tipo_identificacion = trim($_POST['tipo_identificacion'] ?? '');
$numero_identificacion = trim($_POST['numero_identificacion'] ?? '');

if ($tipo_identificacion === '') {
    echo json_encode(['error' => 'Tipo de identificación requerido']);
    exit;
}

if ($numero_identificacion === '') {
    echo json_encode(['error' => 'Número de identificación requerido']);
    exit;
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    $visitante = new Visitante($db);

    $visitante_encontrado = $visitante->findByTipoYNumero($tipo_identificacion, $numero_identificacion);
    
    if ($visitante_encontrado) {
        echo json_encode([
            'exists' => true,
            'visitante' => [
                'id' => $visitante_encontrado['id'],
                'nombre' => $visitante_encontrado['nombre'],
                'apellido' => $visitante_encontrado['apellido'],
                'tipo_identificacion' => $visitante_encontrado['tipo_identificacion'],
                'numero_identificacion' => $visitante_encontrado['numero_identificacion'],
                'empresa_representa' => $visitante_encontrado['empresa_representa'],
                'condicion' => $visitante_encontrado['condicion'],
                'fecha_registro' => $visitante_encontrado['fecha_registro']
            ]
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Error interno del servidor']);
}
?> 