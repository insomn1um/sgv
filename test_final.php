<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

echo "<h1>Test Final del Sistema SGV</h1>";

try {
    // 1. Verificar conexión a base de datos
    echo "<h2>1. Conexión a base de datos</h2>";
    require_once 'config/database.php';
    $database = Database::getInstance();
    $db = $database->getConnection();
    echo "✅ Conexión exitosa<br>";
    
    // 2. Verificar clase Usuario
    echo "<h2>2. Clase Usuario</h2>";
    require_once 'classes/Usuario.php';
    $usuario = new Usuario($db);
    echo "✅ Clase Usuario creada<br>";
    
    // 3. Verificar funciones
    echo "<h2>3. Funciones</h2>";
    require_once 'includes/functions.php';
    echo "✅ Funciones cargadas<br>";
    
    // 4. Probar login
    echo "<h2>4. Test de Login</h2>";
    $username = 'admin';
    $password = 'admin123';
    
    if ($usuario->login($username, $password)) {
        echo "✅ Login exitoso<br>";
        echo "Datos de sesión:<br>";
        echo "- user_id: " . $_SESSION['user_id'] . "<br>";
        echo "- username: " . $_SESSION['username'] . "<br>";
        echo "- nombre: " . $_SESSION['nombre'] . "<br>";
        echo "- apellido: " . $_SESSION['apellido'] . "<br>";
        echo "- rol: " . $_SESSION['rol'] . "<br>";
        
        // 5. Verificar funciones de sesión
        echo "<h2>5. Funciones de sesión</h2>";
        echo "isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "<br>";
        echo "isAdmin(): " . (isAdmin() ? 'true' : 'false') . "<br>";
        echo "isSupervisor(): " . (isSupervisor() ? 'true' : 'false') . "<br>";
        
        // 6. Probar auditoría
        echo "<h2>6. Test de Auditoría</h2>";
        if (registrarAuditoria('test', 'sistema', 'Test de auditoría desde script de prueba', ['test' => true])) {
            echo "✅ Auditoría registrada correctamente<br>";
        } else {
            echo "❌ Error al registrar auditoría<br>";
        }
        
    } else {
        echo "❌ Login fallido<br>";
    }
    
    echo "<h2>🎉 Test completado</h2>";
    echo "<p>Si todos los tests muestran ✅, el sistema está funcionando correctamente.</p>";
    echo "<p><a href='index.php'>Ir al Sistema</a></p>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString();
}
?> 