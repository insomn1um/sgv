<?php
require_once __DIR__ . '/../config/database.php';

class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $username;
    public $password;
    public $nombre;
    public $apellido;
    public $email;
    public $rol;
    public $estado;
    public $fecha_registro;
    public $ultimo_acceso;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Autenticar usuario
    public function login($username, $password) {
        $query = "SELECT id, username, password, nombre, apellido, email, rol, estado 
                  FROM " . $this->table_name . " 
                  WHERE username = :username AND estado = 'activo'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $row['password'])) {
                // Actualizar último acceso
                $this->updateLastAccess($row['id']);
                
                // Guardar datos en sesión
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['nombre'] = $row['nombre'];
                $_SESSION['apellido'] = $row['apellido'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['rol'] = $row['rol'];
                
                return true;
            }
        }
        
        return false;
    }

    // Actualizar último acceso
    private function updateLastAccess($user_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET ultimo_acceso = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();
    }

    // Crear nuevo usuario
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  (username, password, nombre, apellido, email, rol)
                  VALUES (:username, :password, :nombre, :apellido, :email, :rol)";
        
        $stmt = $this->conn->prepare($query);
        
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":rol", $this->rol);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    // Leer todos los usuarios
    public function read() {
        $query = "SELECT id, username, nombre, apellido, email, rol, 
                         estado, fecha_registro, ultimo_acceso
                  FROM " . $this->table_name . "
                  ORDER BY fecha_registro DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Leer un usuario específico
    public function readOne() {
        $query = "SELECT id, username, nombre, apellido, email, rol, estado, 
                         fecha_registro, ultimo_acceso
                  FROM " . $this->table_name . " 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->username = $row['username'];
            $this->nombre = $row['nombre'];
            $this->apellido = $row['apellido'];
            $this->email = $row['email'];
            $this->rol = $row['rol'];
            $this->estado = $row['estado'];
            $this->fecha_registro = $row['fecha_registro'];
            $this->ultimo_acceso = $row['ultimo_acceso'];
            return true;
        }
        
        return false;
    }

    // Actualizar usuario
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET nombre = :nombre, apellido = :apellido, email = :email, 
                      rol = :rol, estado = :estado
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":rol", $this->rol);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    // Cambiar contraseña
    public function changePassword($new_password) {
        $query = "UPDATE " . $this->table_name . "
                  SET password = :password
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    // Verificar si username existe
    public function usernameExists($username, $exclude_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username";
        if ($exclude_id) {
            $query .= " AND id != :exclude_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        if ($exclude_id) {
            $stmt->bindParam(":exclude_id", $exclude_id);
        }
        
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Verificar si email existe
    public function emailExists($email, $exclude_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        if ($exclude_id) {
            $query .= " AND id != :exclude_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        if ($exclude_id) {
            $stmt->bindParam(":exclude_id", $exclude_id);
        }
        
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
} 