<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['resultados' => [], 'error' => 'No autorizado']);
    exit;
}

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode(['resultados' => []]);
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    $resultados = [];
    $vistos = [];

    // 1. Buscar en visitantes (datos completos)
    try {
        $stmt = $db->prepare("
            SELECT id, nombre, apellido, tipo_identificacion, numero_identificacion, 
                   numero_contacto, email, empresa_representa, patente_vehiculo
            FROM visitantes 
            WHERE nombre LIKE :q OR apellido LIKE :q OR numero_identificacion LIKE :q 
               OR empresa_representa LIKE :q OR CONCAT(nombre,' ',apellido) LIKE :q
               OR CONCAT(apellido,' ',nombre) LIKE :q
            ORDER BY fecha_registro DESC
            LIMIT 20
        ");
        $term = '%' . $q . '%';
        $stmt->bindParam(':q', $term);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = $row['numero_identificacion'] . '|' . ($row['nombre'] ?? '') . '|' . ($row['apellido'] ?? '');
            if (isset($vistos[$key])) continue;
            $vistos[$key] = true;

            $resultados[] = [
                'origen' => 'visitantes',
                'nombre' => $row['nombre'] ?? '',
                'apellido' => $row['apellido'] ?? '',
                'tipo_identificacion' => $row['tipo_identificacion'] ?? 'rut',
                'numero_identificacion' => $row['numero_identificacion'] ?? '',
                'numero_contacto' => $row['numero_contacto'] ?? $row['telefono'] ?? '',
                'email' => $row['email'] ?? '',
                'empresa_visitante' => $row['empresa_representa'] ?? '',
                'patente' => $row['patente_vehiculo'] ?? '',
                'tipo_vehiculo' => '',
                'numero_tarjeta' => ''
            ];
        }
    } catch (PDOException $e) {
        // Tabla visitantes puede no existir
    }

    // 2. Si hay pocos resultados, buscar en visitas (visitas previas)
    if (count($resultados) < 15) {
        try {
            $stmt = $db->prepare("
                SELECT a_quien_visita, empresa_visitante, patente, tipo_vehiculo, numero_tarjeta
                FROM visitas 
                WHERE a_quien_visita LIKE :q OR empresa_visitante LIKE :q
                ORDER BY fecha_ingreso DESC
                LIMIT 25
            ");
            $term = '%' . $q . '%';
            $stmt->bindParam(':q', $term);
            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $nombre_completo = trim($row['a_quien_visita'] ?? '');
                if (empty($nombre_completo)) continue;

                $partes = preg_split('/\s+/', $nombre_completo, 2);
                $nombre = $partes[0] ?? '';
                $apellido = $partes[1] ?? '';
                $key = $nombre_completo;
                if (isset($vistos[$key])) continue;
                $vistos[$key] = true;

                $resultados[] = [
                    'origen' => 'visitas',
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'tipo_identificacion' => '',
                    'numero_identificacion' => '',
                    'numero_contacto' => '',
                    'email' => '',
                    'empresa_visitante' => $row['empresa_visitante'] ?? '',
                    'patente' => $row['patente'] ?? '',
                    'tipo_vehiculo' => $row['tipo_vehiculo'] ?? '',
                    'numero_tarjeta' => $row['numero_tarjeta'] ?? ''
                ];
            }
        } catch (PDOException $e) {
            // Ignorar
        }
    }

    echo json_encode(['resultados' => array_slice($resultados, 0, 15)]);

} catch (Exception $e) {
    echo json_encode(['resultados' => [], 'error' => $e->getMessage()]);
}
