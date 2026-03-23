<?php
require_once 'config/database.php';

class Visita {
    private $db;
    
    public function __construct($db = null) {
        if ($db) {
            $this->db = $db;
        } else {
            $this->db = Database::getInstance()->getConnection();
        }
    }
    
    public function crear($trabajador_id, $numero_tarjeta, $patente, $a_quien_visita, $motivo_visita, $observaciones, $registrado_por, $contrato = null, $tipo_contrato = null, $contrato_vigente = null, $registro_epp = null, $registro_riohs = null, $registro_induccion = null, $examenes_ocupacionales = null, $empresa_visitante = null, $tipo_vehiculo = null) {
        $sql = "INSERT INTO visitas (trabajador_id, numero_tarjeta, patente, tipo_vehiculo, a_quien_visita, motivo_visita, observaciones, estado, registrado_por, contrato, tipo_contrato, contrato_vigente, registro_epp, registro_riohs, registro_induccion, examenes_ocupacionales, empresa_visitante) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'activa', ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$trabajador_id, $numero_tarjeta, $patente, $tipo_vehiculo, $a_quien_visita, $motivo_visita, $observaciones, $registrado_por, $contrato, $tipo_contrato, $contrato_vigente, $registro_epp, $registro_riohs, $registro_induccion, $examenes_ocupacionales, $empresa_visitante]);
    }
    
    public function obtenerPorId($id) {
        $sql = "SELECT v.*, t.nombre, t.apellido, t.tipo_identificacion, t.numero_identificacion, t.cargo,
                       e.nombre as empresa_nombre, e.condicion as empresa_condicion,
                       u.nombre as registrado_por_nombre, u.apellido as registrado_por_apellido
                FROM visitas v 
                JOIN trabajadores t ON v.trabajador_id = t.id 
                JOIN empresas e ON t.empresa_id = e.id
                LEFT JOIN usuarios u ON v.registrado_por = u.id 
                WHERE v.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function obtenerTodas() {
        $sql = "SELECT v.*, t.nombre, t.apellido, t.tipo_identificacion, t.numero_identificacion, t.cargo,
                       e.nombre as empresa_nombre, e.condicion as empresa_condicion,
                       u.nombre as registrado_por_nombre, u.apellido as registrado_por_apellido
                FROM visitas v 
                JOIN trabajadores t ON v.trabajador_id = t.id 
                JOIN empresas e ON t.empresa_id = e.id
                LEFT JOIN usuarios u ON v.registrado_por = u.id 
                ORDER BY v.fecha_ingreso DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerPorTrabajador($trabajador_id, $limit = null, $offset = 0) {
        $sql = "SELECT v.*, t.nombre, t.apellido, t.tipo_identificacion, t.numero_identificacion, t.cargo,
                       e.nombre as empresa_nombre, e.condicion as empresa_condicion
                FROM visitas v 
                JOIN trabajadores t ON v.trabajador_id = t.id 
                LEFT JOIN empresas e ON t.empresa_id = e.id
                WHERE v.trabajador_id = ? 
                ORDER BY v.fecha_ingreso DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$trabajador_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function contarPorTrabajador($trabajador_id) {
        $sql = "SELECT COUNT(*) FROM visitas WHERE trabajador_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$trabajador_id]);
        return (int)$stmt->fetchColumn();
    }
    
    public function obtenerPorEmpresa($empresa_id) {
        $sql = "SELECT v.*, t.nombre, t.apellido, t.tipo_identificacion, t.numero_identificacion, t.cargo,
                       e.nombre as empresa_nombre, e.condicion as empresa_condicion
                FROM visitas v 
                JOIN trabajadores t ON v.trabajador_id = t.id 
                JOIN empresas e ON t.empresa_id = e.id
                WHERE t.empresa_id = ? 
                ORDER BY v.fecha_ingreso DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$empresa_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerActivas() {
        $sql = "SELECT v.*, t.nombre, t.apellido, t.tipo_identificacion, t.numero_identificacion, t.cargo,
                       e.nombre as empresa_nombre, e.condicion as empresa_condicion
                FROM visitas v 
                JOIN trabajadores t ON v.trabajador_id = t.id 
                JOIN empresas e ON t.empresa_id = e.id
                WHERE v.estado = 'activa' 
                ORDER BY v.fecha_ingreso DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerDelDia() {
        $sql = "SELECT v.*, t.nombre, t.apellido, t.tipo_identificacion, t.numero_identificacion, t.cargo,
                       e.nombre as empresa_nombre, e.condicion as empresa_condicion
                FROM visitas v 
                JOIN trabajadores t ON v.trabajador_id = t.id 
                JOIN empresas e ON t.empresa_id = e.id
                WHERE DATE(v.fecha_ingreso) = CURDATE() 
                ORDER BY v.fecha_ingreso DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function registrarSalida($id) {
        $sql = "UPDATE visitas SET fecha_salida = NOW(), estado = 'finalizada' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    public function buscar($termino) {
        $sql = "SELECT v.*, t.nombre, t.apellido, t.tipo_identificacion, t.numero_identificacion, t.cargo,
                       e.nombre as empresa_nombre, e.condicion as empresa_condicion
                FROM visitas v 
                JOIN trabajadores t ON v.trabajador_id = t.id 
                JOIN empresas e ON t.empresa_id = e.id
                WHERE t.nombre LIKE ? OR t.apellido LIKE ? OR t.numero_identificacion LIKE ? 
                   OR e.nombre LIKE ? OR v.motivo_visita LIKE ?
                ORDER BY v.fecha_ingreso DESC";
        $stmt = $this->db->prepare($sql);
        $termino = "%$termino%";
        $stmt->execute([$termino, $termino, $termino, $termino, $termino]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getEstadisticas() {
        $sql = "SELECT 
                    'activa' as tipo,
                    COUNT(*) as total
                FROM visitas 
                WHERE estado = 'activa'
                UNION ALL
                SELECT 
                    'hoy' as tipo,
                    COUNT(*) as total
                FROM visitas 
                WHERE DATE(fecha_ingreso) = CURDATE()
                UNION ALL
                SELECT 
                    'mes' as tipo,
                    COUNT(*) as total
                FROM visitas 
                WHERE MONTH(fecha_ingreso) = MONTH(CURDATE()) 
                AND YEAR(fecha_ingreso) = YEAR(CURDATE())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convertir a formato esperado por el dashboard
        $estadisticas = [
            'visitas_activas' => 0,
            'visitas_hoy' => 0,
            'visitas_mes' => 0
        ];
        
        foreach ($resultados as $row) {
            switch ($row['tipo']) {
                case 'activa':
                    $estadisticas['visitas_activas'] = $row['total'];
                    break;
                case 'hoy':
                    $estadisticas['visitas_hoy'] = $row['total'];
                    break;
                case 'mes':
                    $estadisticas['visitas_mes'] = $row['total'];
                    break;
            }
        }
        
        return $estadisticas;
    }
    
    public function obtenerEstadisticasPorEmpresa($empresa_id) {
        $sql = "SELECT 
                    v.estado,
                    COUNT(*) as total
                FROM visitas v 
                JOIN trabajadores t ON v.trabajador_id = t.id 
                WHERE t.empresa_id = ? 
                GROUP BY v.estado";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$empresa_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerEstadisticasPorTrabajador($trabajador_id) {
        // Consulta optimizada que obtiene todas las estadísticas en una sola query
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'activa' THEN 1 ELSE 0 END) as activas,
                    SUM(CASE WHEN DATE(fecha_ingreso) = CURDATE() THEN 1 ELSE 0 END) as hoy,
                    SUM(CASE WHEN MONTH(fecha_ingreso) = MONTH(CURDATE()) 
                              AND YEAR(fecha_ingreso) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as mes
                FROM visitas 
                WHERE trabajador_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$trabajador_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Retornar en formato esperado por el frontend
        return [
            'total' => (int)($result['total'] ?? 0),
            'activas' => (int)($result['activas'] ?? 0),
            'hoy' => (int)($result['hoy'] ?? 0),
            'mes' => (int)($result['mes'] ?? 0)
        ];
    }
    
    public function eliminar($id) {
        $sql = "DELETE FROM visitas WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
} 