<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Configuración de Base de Datos SGV</h1>";

// Configuración de la base de datos
$host = 'localhost';
$username = 'root';
$password = '';
$db_name = 'sgv';

try {
    // Conectar sin especificar base de datos
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexión a MySQL exitosa<br>";
    
    // Crear base de datos si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de datos '$db_name' creada/verificada<br>";
    
    // Seleccionar la base de datos
    $pdo->exec("USE `$db_name`");
    echo "✅ Base de datos seleccionada<br>";
    
    // Crear tabla usuarios
    $sql_usuarios = "CREATE TABLE IF NOT EXISTS `usuarios` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL UNIQUE,
        `password` varchar(255) NOT NULL,
        `nombre` varchar(100) NOT NULL,
        `apellido` varchar(100) NOT NULL,
        `email` varchar(100) DEFAULT NULL,
        `rol` enum('admin','supervisor','operador') NOT NULL DEFAULT 'operador',
        `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
        `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `ultimo_acceso` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_usuarios);
    echo "✅ Tabla 'usuarios' creada/verificada<br>";
    
    // Crear tabla empresas
    $sql_empresas = "CREATE TABLE IF NOT EXISTS `empresas` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `nombre` varchar(200) NOT NULL,
        `razon_social` varchar(200) DEFAULT NULL,
        `rut` varchar(20) NOT NULL UNIQUE,
        `direccion` text DEFAULT NULL,
        `telefono` varchar(20) DEFAULT NULL,
        `email` varchar(100) DEFAULT NULL,
        `estado` enum('activa','suspendida','bloqueada') NOT NULL DEFAULT 'activa',
        `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `registrado_por` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `fk_empresas_usuario` (`registrado_por`),
        CONSTRAINT `fk_empresas_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_empresas);
    echo "✅ Tabla 'empresas' creada/verificada<br>";
    
    // Crear tabla trabajadores
    $sql_trabajadores = "CREATE TABLE IF NOT EXISTS `trabajadores` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `empresa_id` int(11) NOT NULL,
        `nombre` varchar(100) NOT NULL,
        `apellido` varchar(100) NOT NULL,
        `numero_identificacion` varchar(20) NOT NULL,
        `tipo_identificacion` enum('rut','pasaporte','otro') NOT NULL DEFAULT 'rut',
        `cargo` varchar(100) DEFAULT NULL,
        `telefono` varchar(20) DEFAULT NULL,
        `email` varchar(100) DEFAULT NULL,
        `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
        `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `registrado_por` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `fk_trabajadores_empresa` (`empresa_id`),
        KEY `fk_trabajadores_usuario` (`registrado_por`),
        CONSTRAINT `fk_trabajadores_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_trabajadores_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_trabajadores);
    echo "✅ Tabla 'trabajadores' creada/verificada<br>";
    
    // Crear tabla visitas
    $sql_visitas = "CREATE TABLE IF NOT EXISTS `visitas` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `trabajador_id` int(11) NOT NULL,
        `fecha_ingreso` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `fecha_salida` timestamp NULL DEFAULT NULL,
        `motivo` text DEFAULT NULL,
        `estado` enum('activa','finalizada','cancelada') NOT NULL DEFAULT 'activa',
        `registrado_por` int(11) DEFAULT NULL,
        `qr_code` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `fk_visitas_trabajador` (`trabajador_id`),
        KEY `fk_visitas_usuario` (`registrado_por`),
        CONSTRAINT `fk_visitas_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_visitas_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_visitas);
    echo "✅ Tabla 'visitas' creada/verificada<br>";
    
    // Crear tabla auditoria
    $sql_auditoria = "CREATE TABLE IF NOT EXISTS `auditoria` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `usuario_id` int(11) DEFAULT NULL,
        `accion` varchar(50) NOT NULL,
        `tabla` varchar(50) NOT NULL,
        `descripcion` text NOT NULL,
        `datos_anteriores` json DEFAULT NULL,
        `datos_nuevos` json DEFAULT NULL,
        `ip_address` varchar(45) DEFAULT NULL,
        `user_agent` text DEFAULT NULL,
        `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `fk_auditoria_usuario` (`usuario_id`),
        KEY `idx_fecha` (`fecha`),
        KEY `idx_accion` (`accion`),
        KEY `idx_tabla` (`tabla`),
        CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_auditoria);
    echo "✅ Tabla 'auditoria' creada/verificada<br>";
    
    // Crear usuario administrador por defecto
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE username = 'admin'");
    $stmt->execute();
    $admin_exists = $stmt->fetchColumn();
    
    if (!$admin_exists) {
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, nombre, apellido, email, rol) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['admin', $password_hash, 'Administrador', 'Sistema', 'admin@sgv.com', 'admin']);
        echo "✅ Usuario administrador creado (admin/admin123)<br>";
    } else {
        echo "✅ Usuario administrador ya existe<br>";
    }
    
    echo "<br><h2>🎉 Configuración completada exitosamente</h2>";
    echo "<p>La base de datos y las tablas han sido creadas correctamente.</p>";
    echo "<p><strong>Credenciales de acceso:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Usuario:</strong> admin</li>";
    echo "<li><strong>Contraseña:</strong> admin123</li>";
    echo "</ul>";
    echo "<p><a href='index.php' class='btn btn-primary'>Ir al Sistema</a></p>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?> 