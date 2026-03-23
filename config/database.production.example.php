<?php
/**
 * Configuración de Base de Datos para PRODUCCIÓN
 * 
 * IMPORTANTE: 
 * - Renombra este archivo a database.production.php
 * - Actualiza las credenciales con los datos reales del servidor
 * - NO subas este archivo con credenciales reales al repositorio
 */

class Database {
    private static $instance = null;
    
    // ⚠️ ACTUALIZA ESTOS VALORES CON LOS DE TU SERVIDOR
    private $host = 'localhost'; // O la IP/hostname de tu servidor MySQL
    private $db_name = 'sgv'; // Nombre de la base de datos en producción
    private $username = 'usuario_produccion'; // Usuario de MySQL en producción
    private $password = 'password_produccion'; // Contraseña de MySQL en producción
    private $conn;

    private function __construct() {}

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        // Reutilizar conexión existente si ya está establecida
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false
                ]
            );
        } catch(PDOException $exception) {
            error_log("Error de conexión: " . $exception->getMessage());
            throw $exception;
        }

        return $this->conn;
    }
}



