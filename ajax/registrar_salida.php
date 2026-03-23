<?php
session_start();

// IMPORTANTE: No incluir nada antes del header
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/database.php';

// Capturar cualquier salida no deseada
ob_start();

try {
    // Verificar que el usuario esté logueado
    if (!isset($_SESSION['user_id'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'No autorizado - Sesión no iniciada']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    if (!isset($_POST['visita_id'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'ID de visita no proporcionado']);
        exit;
    }

    $visita_id = intval($_POST['visita_id']);
    
    if ($visita_id <= 0) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'ID de visita inválido']);
        exit;
    }
    
    // Reutilizar conexión central para evitar credenciales hardcodeadas
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    // Obtener información de la visita
    $stmt = $pdo->prepare("SELECT id, estado, a_quien_visita, fecha_ingreso FROM visitas WHERE id = ?");
    $stmt->execute([$visita_id]);
    $visita_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$visita_info) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Visita no encontrada con ID: ' . $visita_id]);
        exit;
    }
    
    // Verificar que la visita esté activa
    if ($visita_info['estado'] !== 'activa') {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'La visita ya no está activa. Estado actual: ' . $visita_info['estado']]);
        exit;
    }
    
    // Registrar salida
    $stmt = $pdo->prepare("UPDATE visitas SET fecha_salida = NOW(), estado = 'finalizada' WHERE id = ?");
    $resultado = $stmt->execute([$visita_id]);
    
    if ($resultado) {
        // Intentar registrar auditoría (si falla, no importa)
        try {
            $stmt = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, tabla, descripcion, datos_nuevos, fecha) VALUES (?, 'update', 'visitas', 'Salida registrada para visita', ?, NOW())");
            $datos_auditoria = json_encode([
                'visita_id' => $visita_id,
                'visitante' => $visita_info['a_quien_visita'],
                'fecha_ingreso' => $visita_info['fecha_ingreso'],
                'fecha_salida' => date('Y-m-d H:i:s')
            ]);
            $stmt->execute([$_SESSION['user_id'], $datos_auditoria]);
        } catch (Exception $e) {
            // Auditoría falló, pero la salida se registró correctamente
            error_log("Error en auditoría: " . $e->getMessage());
        }
        
        // Limpiar cualquier output buffer
        ob_end_clean();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Salida registrada correctamente',
            'visita_id' => $visita_id,
            'visitante' => $visita_info['a_quien_visita'],
            'fecha_salida' => date('Y-m-d H:i:s')
        ]);
    } else {
        ob_end_clean();
        echo json_encode([
            'success' => false, 
            'message' => 'No se pudo registrar la salida en la base de datos',
            'visita_id' => $visita_id
        ]);
    }
    
} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false, 
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false, 
        'message' => 'Error del servidor: ' . $e->getMessage(),
        'type' => get_class($e)
    ]);
}
exit;
