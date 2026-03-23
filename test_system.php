<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test del Sistema SGV</h1>";

// Test 1: Verificar que PHP funcione
echo "<h2>1. PHP funciona correctamente</h2>";
echo "✅ PHP está funcionando<br>";

// Test 2: Verificar archivos requeridos
echo "<h2>2. Verificando archivos requeridos</h2>";
$files = [
    'config/database.php',
    'includes/functions.php',
    'classes/Empresa.php',
    'classes/Trabajador.php',
    'classes/Visita.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file existe<br>";
    } else {
        echo "❌ $file NO existe<br>";
    }
}

// Test 3: Verificar conexión a base de datos
echo "<h2>3. Verificando conexión a base de datos</h2>";
try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "✅ Conexión a base de datos exitosa<br>";
    
    // Verificar tablas
    $tables = ['usuarios', 'empresas', 'trabajadores', 'visitas', 'auditoria'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabla $table existe<br>";
        } else {
            echo "❌ Tabla $table NO existe<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
}

// Test 4: Verificar clases
echo "<h2>4. Verificando clases</h2>";
try {
    require_once 'classes/Empresa.php';
    require_once 'classes/Trabajador.php';
    require_once 'classes/Visita.php';
    
    $empresa = new Empresa();
    echo "✅ Clase Empresa cargada correctamente<br>";
    
    $trabajador = new Trabajador();
    echo "✅ Clase Trabajador cargada correctamente<br>";
    
    $visita = new Visita();
    echo "✅ Clase Visita cargada correctamente<br>";
    
} catch (Exception $e) {
    echo "❌ Error al cargar clases: " . $e->getMessage() . "<br>";
}

// Test 5: Verificar funciones
echo "<h2>5. Verificando funciones</h2>";
try {
    require_once 'includes/functions.php';
    echo "✅ Funciones cargadas correctamente<br>";
    echo "✅ Función isAdmin(): " . (function_exists('isAdmin') ? 'existe' : 'NO existe') . "<br>";
    echo "✅ Función isSupervisor(): " . (function_exists('isSupervisor') ? 'existe' : 'NO existe') . "<br>";
    echo "✅ Función registrarAuditoria(): " . (function_exists('registrarAuditoria') ? 'existe' : 'NO existe') . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error al cargar funciones: " . $e->getMessage() . "<br>";
}

echo "<h2>✅ Test completado</h2>";
echo "<p>Si todos los tests muestran ✅, el sistema debería funcionar correctamente.</p>";
echo "<p><a href='dashboard.php'>Ir al Dashboard</a></p>";
?> 