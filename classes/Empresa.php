<?php
require_once __DIR__ . '/../config/database.php';

class Empresa {
    private $db;
    
    public function __construct($db = null) {
        if ($db) {
            $this->db = $db;
        } else {
            $this->db = Database::getInstance()->getConnection();
        }
    }
    
    public function crear($nombre, $rut, $razon_social = null, $direccion = null, $telefono = null, $email = null, $registrado_por = null) {
        // Verificar si el RUT ya existe
        if ($this->rutExiste($rut)) {
            return false; // RUT duplicado
        }
        
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
        
        $sql = "INSERT INTO empresas (nombre, razon_social, rut, direccion, telefono, email, condicion, registrado_por) 
                VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nombre, $razon_social, $rut, $direccion, $telefono, $email, $registrado_por]);
    }
    
    public function rutExiste($rut) {
        $sql = "SELECT COUNT(*) FROM empresas WHERE rut = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$rut]);
        return $stmt->fetchColumn() > 0;
    }
    
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM empresas WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function obtenerTodas() {
        $sql = "SELECT e.*, u.nombre as registrado_por_nombre, u.apellido as registrado_por_apellido 
                FROM empresas e 
                LEFT JOIN usuarios u ON e.registrado_por = u.id 
                ORDER BY e.fecha_registro DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function cambiarEstado($id, $estado) {
        $sql = "UPDATE empresas SET condicion = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$estado, $id]);
    }
    
    public function obtenerPorEstado($estado) {
        $sql = "SELECT e.*, u.nombre as registrado_por_nombre, u.apellido as registrado_por_apellido 
                FROM empresas e 
                LEFT JOIN usuarios u ON e.registrado_por = u.id 
                WHERE e.condicion = ? 
                ORDER BY e.fecha_registro DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$estado]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function buscar($termino) {
        $sql = "SELECT e.*, u.nombre as registrado_por_nombre, u.apellido as registrado_por_apellido 
                FROM empresas e 
                LEFT JOIN usuarios u ON e.registrado_por = u.id 
                WHERE e.nombre LIKE ? OR e.razon_social LIKE ? OR e.rut LIKE ? 
                ORDER BY e.fecha_registro DESC";
        $stmt = $this->db->prepare($sql);
        $termino = "%$termino%";
        $stmt->execute([$termino, $termino, $termino]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getEstadisticas() {
        $sql = "SELECT 
                    condicion,
                    COUNT(*) as total
                FROM empresas 
                GROUP BY condicion";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convertir a formato esperado por el dashboard
        $estadisticas = [
            'total_empresas' => 0,
            'empresas_activas' => 0,
            'empresas_aprobadas' => 0,
            'empresas_pendientes' => 0,
            'empresas_denegadas' => 0,
            'empresas_suspendidas' => 0,
            'empresas_bloqueadas' => 0
        ];
        
        foreach ($resultados as $row) {
            $estadisticas['total_empresas'] += $row['total'];
            switch ($row['condicion']) {
                case 'aprobada':
                    $estadisticas['empresas_aprobadas'] = $row['total'];
                    $estadisticas['empresas_activas'] = $row['total']; // Empresas aprobadas son activas
                    break;
                case 'pendiente':
                    $estadisticas['empresas_pendientes'] = $row['total'];
                    break;
                case 'denegada':
                    $estadisticas['empresas_denegadas'] = $row['total'];
                    break;
                case 'suspendida':
                    $estadisticas['empresas_suspendidas'] = $row['total'];
                    break;
                case 'bloqueada':
                    $estadisticas['empresas_bloqueadas'] = $row['total'];
                    break;
            }
        }
        
        return $estadisticas;
    }
    
    public function actualizar($id, $nombre, $razon_social, $rut, $direccion, $telefono, $email) {
        $sql = "UPDATE empresas SET nombre = ?, razon_social = ?, rut = ?, direccion = ?, telefono = ?, email = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nombre, $razon_social, $rut, $direccion, $telefono, $email, $id]);
    }
    
    public function eliminar($id) {
        $sql = "DELETE FROM empresas WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    public function cambiarCondicion($id, $condicion) {
        $sql = "UPDATE empresas SET condicion = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$condicion, $id]);
    }
    
    public function obtenerPorCondicion($condicion) {
        $sql = "SELECT e.*, u.nombre as registrado_por_nombre, u.apellido as registrado_por_apellido 
                FROM empresas e 
                LEFT JOIN usuarios u ON e.registrado_por = u.id 
                WHERE e.condicion = ? 
                ORDER BY e.fecha_registro DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$condicion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 