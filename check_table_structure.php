<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    echo "<h1>Verificando estructura de la tabla usuarios</h1>";
    
    // Verificar si la tabla existe
    $stmt = $db->query("SHOW TABLES LIKE 'usuarios'");
    if ($stmt->rowCount() == 0) {
        echo "❌ La tabla 'usuarios' no existe<br>";
        exit;
    }
    echo "✅ La tabla 'usuarios' existe<br>";
    
    // Mostrar estructura de la tabla
    echo "<h2>Estructura de la tabla usuarios:</h2>";
    $stmt = $db->query("DESCRIBE usuarios");
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Llave</th><th>Por defecto</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar si hay usuarios en la tabla
    echo "<h2>Usuarios en la tabla:</h2>";
    $stmt = $db->query("SELECT id, username, nombre, apellido, rol, estado FROM usuarios");
    if ($stmt->rowCount() == 0) {
        echo "❌ No hay usuarios en la tabla<br>";
    } else {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Username</th><th>Nombre</th><th>Apellido</th><th>Rol</th><th>Estado</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['username'] . "</td>";
            echo "<td>" . $row['nombre'] . "</td>";
            echo "<td>" . $row['apellido'] . "</td>";
            echo "<td>" . $row['rol'] . "</td>";
            echo "<td>" . $row['estado'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?> 