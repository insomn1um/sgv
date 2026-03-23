<?php
require_once __DIR__ . '/../config/database.php';

class Visitante {
    private $conn;
    private $table_name = "visitantes";

    public $id;
    public $nombre;
    public $apellido;
    public $tipo_identificacion;
    public $numero_identificacion;
    public $numero_contacto;
    public $telefono;
    public $email;
    public $empresa_representa;
    public $a_quien_visita;
    public $motivo_visita;
    public $patente_vehiculo;
    public $foto_vehiculo;
    public $condicion;
    public $fecha_registro;
    public $registrado_por;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear nuevo visitante
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  (nombre, apellido, tipo_identificacion, numero_identificacion, 
                   numero_contacto, email, empresa_representa, a_quien_visita, motivo_visita, 
                   patente_vehiculo, foto_vehiculo, registrado_por)
                  VALUES (:nombre, :apellido, :tipo_identificacion, :numero_identificacion,
                          :numero_contacto, :email, :empresa_representa, :a_quien_visita, :motivo_visita,
                          :patente_vehiculo, :foto_vehiculo, :registrado_por)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":tipo_identificacion", $this->tipo_identificacion);
        $stmt->bindParam(":numero_identificacion", $this->numero_identificacion);
        $stmt->bindParam(":numero_contacto", $this->numero_contacto);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":empresa_representa", $this->empresa_representa);
        $stmt->bindParam(":a_quien_visita", $this->a_quien_visita);
        $stmt->bindParam(":motivo_visita", $this->motivo_visita);
        $stmt->bindParam(":patente_vehiculo", $this->patente_vehiculo);
        $stmt->bindParam(":foto_vehiculo", $this->foto_vehiculo);
        $stmt->bindParam(":registrado_por", $this->registrado_por);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    // Leer todos los visitantes
    public function read() {
        $query = "SELECT v.*, u.nombre as registrador_nombre, u.apellido as registrador_apellido
                  FROM " . $this->table_name . " v
                  LEFT JOIN usuarios u ON v.registrado_por = u.id
                  ORDER BY v.fecha_registro DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Leer un visitante específico
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->nombre = $row['nombre'];
            $this->apellido = $row['apellido'];
            $this->tipo_identificacion = $row['tipo_identificacion'];
            $this->numero_identificacion = $row['numero_identificacion'];
            $this->numero_contacto = $row['numero_contacto'];
            $this->email = $row['email'];
            $this->empresa_representa = $row['empresa_representa'];
            $this->a_quien_visita = $row['a_quien_visita'];
            $this->motivo_visita = $row['motivo_visita'];
            $this->patente_vehiculo = $row['patente_vehiculo'];
            $this->foto_vehiculo = $row['foto_vehiculo'];
            $this->condicion = $row['condicion'];
            $this->fecha_registro = $row['fecha_registro'];
            $this->registrado_por = $row['registrado_por'];
            return true;
        }
        
        return false;
    }

    // Buscar visitante por número de identificación
    public function findByIdentificacion($numero_identificacion) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE numero_identificacion = :numero_identificacion";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":numero_identificacion", $numero_identificacion);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar visitante
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET nombre = :nombre, apellido = :apellido, tipo_identificacion = :tipo_identificacion,
                      numero_identificacion = :numero_identificacion, numero_contacto = :numero_contacto,
                      email = :email, empresa_representa = :empresa_representa, a_quien_visita = :a_quien_visita,
                      motivo_visita = :motivo_visita, patente_vehiculo = :patente_vehiculo,
                      foto_vehiculo = :foto_vehiculo, condicion = :condicion
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":tipo_identificacion", $this->tipo_identificacion);
        $stmt->bindParam(":numero_identificacion", $this->numero_identificacion);
        $stmt->bindParam(":numero_contacto", $this->numero_contacto);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":empresa_representa", $this->empresa_representa);
        $stmt->bindParam(":a_quien_visita", $this->a_quien_visita);
        $stmt->bindParam(":motivo_visita", $this->motivo_visita);
        $stmt->bindParam(":patente_vehiculo", $this->patente_vehiculo);
        $stmt->bindParam(":foto_vehiculo", $this->foto_vehiculo);
        $stmt->bindParam(":condicion", $this->condicion);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    // Cambiar condición del visitante
    public function cambiarCondicion($condicion) {
        $query = "UPDATE " . $this->table_name . "
                  SET condicion = :condicion
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":condicion", $condicion);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    // Verificar si número de identificación existe
    public function identificacionExists($numero_identificacion, $exclude_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE numero_identificacion = :numero_identificacion";
        if ($exclude_id) {
            $query .= " AND id != :exclude_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":numero_identificacion", $numero_identificacion);
        if ($exclude_id) {
            $stmt->bindParam(":exclude_id", $exclude_id);
        }
        
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Obtener visitantes del día
    public function getVisitantesDelDia() {
        $query = "SELECT v.*, vi.numero_tarjeta, vi.fecha_ingreso, vi.fecha_salida, vi.estado
                  FROM " . $this->table_name . " v
                  INNER JOIN visitas vi ON v.id = vi.visitante_id
                  WHERE DATE(vi.fecha_ingreso) = CURDATE()
                  ORDER BY vi.fecha_ingreso DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Buscar visitantes
    public function search($search_term) {
        $query = "SELECT v.*, u.nombre as registrador_nombre, u.apellido as registrador_apellido
                  FROM " . $this->table_name . " v
                  LEFT JOIN usuarios u ON v.registrado_por = u.id
                  WHERE v.nombre LIKE :search OR v.apellido LIKE :search 
                        OR v.numero_identificacion LIKE :search OR v.empresa_representa LIKE :search
                  ORDER BY v.fecha_registro DESC";
        
        $stmt = $this->conn->prepare($query);
        $search_term = "%{$search_term}%";
        $stmt->bindParam(":search", $search_term);
        $stmt->execute();
        
        return $stmt;
    }

    // Obtener estadísticas de visitantes
    public function getEstadisticas() {
        $query = "SELECT 
                    COUNT(*) as total_visitantes,
                    COUNT(CASE WHEN condicion = 'permitida' THEN 1 END) as visitantes_aprobados,
                    COUNT(CASE WHEN condicion = 'pendiente' THEN 1 END) as visitantes_pendientes,
                    COUNT(CASE WHEN condicion = 'denegada' THEN 1 END) as visitantes_denegados,
                    COUNT(CASE WHEN DATE(fecha_registro) = CURDATE() THEN 1 END) as nuevos_hoy
                  FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Generar código QR para preregistro
    public function generarCodigoQR($visitante_id) {
        // Generar código único
        $codigo = generateQRCode();
        
        $query = "INSERT INTO codigos_qr (codigo, visitante_id, creado_por) 
                  VALUES (:codigo, :visitante_id, :creado_por)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":codigo", $codigo);
        $stmt->bindParam(":visitante_id", $visitante_id);
        $stmt->bindParam(":creado_por", $this->registrado_por);
        
        if ($stmt->execute()) {
            return $codigo;
        }
        
        return false;
    }

    // Obtener código QR de un visitante
    public function obtenerCodigoQR($visitante_id) {
        $query = "SELECT cq.* FROM codigos_qr cq 
                  WHERE cq.visitante_id = :visitante_id 
                  AND cq.usado = FALSE 
                  ORDER BY cq.fecha_creacion DESC 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":visitante_id", $visitante_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Marcar código QR como usado
    public function marcarCodigoQRUsado($codigo) {
        $query = "UPDATE codigos_qr 
                  SET usado = TRUE, fecha_uso = CURRENT_TIMESTAMP 
                  WHERE codigo = :codigo";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":codigo", $codigo);
        
        return $stmt->execute();
    }
}
?> 