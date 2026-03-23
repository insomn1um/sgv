<?php
require_once __DIR__ . '/../config/database.php';

class Trabajador {
    private $db;
    
    public function __construct($db = null) {
        if ($db) {
            $this->db = $db;
        } else {
            $this->db = Database::getInstance()->getConnection();
        }
    }
    
    public function crear($nombre, $apellido, $numero_identificacion, $cargo, $empresa_id, $telefono = null, $email = null, $registrado_por = null) {
        // Si no se proporciona un usuario registrado, usar el administrador (ID 1) o NULL
        if ($registrado_por === null || $registrado_por <= 0) {
            // Verificar si existe el usuario administrador
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE id = 1");
            $stmt->execute();
            if ($stmt->fetch()) {
                $registrado_por = 1; // Usar el administrador
            } else {
                $registrado_por = null; // Usar NULL si no existe
            }
        }
        
        $sql = "INSERT INTO trabajadores (empresa_id, nombre, apellido, tipo_identificacion, numero_identificacion, cargo, telefono, email, estado, registrado_por) 
                VALUES (?, ?, ?, 'rut', ?, ?, ?, ?, 'activo', ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$empresa_id, $nombre, $apellido, $numero_identificacion, $cargo, $telefono, $email, $registrado_por]);
    }
    
    public function obtenerPorId($id) {
        // LEFT JOIN: si falta la empresa (FK roto o empresa eliminada), igual mostrar al trabajador
        $sql = "SELECT t.*, e.nombre as empresa_nombre, e.condicion as empresa_condicion 
                FROM trabajadores t 
                LEFT JOIN empresas e ON t.empresa_id = e.id 
                WHERE t.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function obtenerTodos() {
        $sql = "SELECT t.*, e.nombre as empresa_nombre, e.condicion as empresa_condicion, 
                       u.nombre as registrado_por_nombre, u.apellido as registrado_por_apellido 
                FROM trabajadores t 
                JOIN empresas e ON t.empresa_id = e.id 
                LEFT JOIN usuarios u ON t.registrado_por = u.id 
                ORDER BY t.fecha_registro DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerPorEmpresa($empresa_id) {
        $sql = "SELECT t.*, e.nombre as empresa_nombre, e.condicion as empresa_condicion 
                FROM trabajadores t 
                JOIN empresas e ON t.empresa_id = e.id 
                WHERE t.empresa_id = ? 
                ORDER BY t.nombre, t.apellido";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$empresa_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function actualizar($id, $nombre, $apellido, $cargo, $telefono, $email) {
        $sql = "UPDATE trabajadores SET nombre = ?, apellido = ?, cargo = ?, telefono = ?, email = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nombre, $apellido, $cargo, $telefono, $email, $id]);
    }
    
    public function cambiarEstado($id, $estado) {
        $sql = "UPDATE trabajadores SET estado = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$estado, $id]);
    }
    
    public function buscar($termino) {
        $sql = "SELECT t.*, e.nombre as empresa_nombre, e.condicion as empresa_condicion 
                FROM trabajadores t 
                JOIN empresas e ON t.empresa_id = e.id 
                WHERE t.nombre LIKE ? OR t.apellido LIKE ? OR t.numero_identificacion LIKE ? OR e.nombre LIKE ? 
                ORDER BY t.fecha_registro DESC";
        $stmt = $this->db->prepare($sql);
        $termino = "%$termino%";
        $stmt->execute([$termino, $termino, $termino, $termino]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerPorIdentificacion($tipo_identificacion, $numero_identificacion) {
        $sql = "SELECT t.*, e.nombre as empresa_nombre, e.condicion as empresa_condicion 
                FROM trabajadores t 
                JOIN empresas e ON t.empresa_id = e.id 
                WHERE t.tipo_identificacion = ? AND t.numero_identificacion = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tipo_identificacion, $numero_identificacion]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getEstadisticas() {
        $sql = "SELECT 
                    t.estado,
                    COUNT(*) as total
                FROM trabajadores t 
                GROUP BY t.estado";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convertir a formato esperado por el dashboard
        $estadisticas = [
            'total_trabajadores' => 0,
            'trabajadores_activos' => 0,
            'trabajadores_inactivos' => 0
        ];
        
        foreach ($resultados as $row) {
            $estadisticas['total_trabajadores'] += $row['total'];
            switch ($row['estado']) {
                case 'activo':
                    $estadisticas['trabajadores_activos'] = $row['total'];
                    break;
                case 'inactivo':
                    $estadisticas['trabajadores_inactivos'] = $row['total'];
                    break;
            }
        }
        
        return $estadisticas;
    }
    
    public function eliminar($id) {
        $sql = "DELETE FROM trabajadores WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    public function existeIdentificacion($tipo_identificacion, $numero_identificacion, $excluir_id = null) {
        $sql = "SELECT COUNT(*) FROM trabajadores WHERE tipo_identificacion = ? AND numero_identificacion = ?";
        $params = [$tipo_identificacion, $numero_identificacion];
        
        if ($excluir_id) {
            $sql .= " AND id != ?";
            $params[] = $excluir_id;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
} 