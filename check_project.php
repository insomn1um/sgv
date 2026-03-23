<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Verificación Completa del Proyecto SGV</h1>";

// 1. Verificar archivos de configuración
echo "<h2>📁 Verificación de Archivos</h2>";
$required_files = [
    'config/database.php',
    'classes/Trabajador.php',
    'classes/Empresa.php',
    'classes/Usuario.php',
    'includes/functions.php',
    'editar_trabajador.php',
    'trabajadores.php',
    'empresas.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✅ $file existe<br>";
    } else {
        echo "❌ $file NO existe<br>";
    }
}

// 2. Verificar sintaxis PHP
echo "<h2>🔧 Verificación de Sintaxis PHP</h2>";
$php_files = [
    'classes/Trabajador.php',
    'classes/Empresa.php',
    'editar_trabajador.php'
];

foreach ($php_files as $file) {
    $output = shell_exec("/Applications/XAMPP/xamppfiles/bin/php -l $file 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✅ $file - Sintaxis correcta<br>";
    } else {
        echo "❌ $file - Error de sintaxis: $output<br>";
    }
}

// 3. Verificar conexión a base de datos
echo "<h2>🗄️ Verificación de Base de Datos</h2>";
try {
    require_once 'config/database.php';
    $database = Database::getInstance();
    $db = $database->getConnection();
    echo "✅ Conexión a base de datos exitosa<br>";
    
    // Verificar tablas principales
    $tables = ['usuarios', 'empresas', 'trabajadores', 'visitas', 'auditoria'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabla '$table' existe<br>";
        } else {
            echo "❌ Tabla '$table' NO existe<br>";
        }
    }
    
    // Verificar estructura de tabla trabajadores
    echo "<h3>Estructura de tabla trabajadores:</h3>";
    $stmt = $db->query("DESCRIBE trabajadores");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background-color: #f0f0f0;'><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Llave</th><th>Por defecto</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar estructura de tabla empresas
    echo "<h3>Estructura de tabla empresas:</h3>";
    $stmt = $db->query("DESCRIBE empresas");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background-color: #f0f0f0;'><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Llave</th><th>Por defecto</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "❌ Error de base de datos: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// 4. Verificar clases y métodos
echo "<h2>🏗️ Verificación de Clases</h2>";
try {
    require_once 'classes/Trabajador.php';
    require_once 'classes/Empresa.php';
    
    // Verificar clase Trabajador
    if (class_exists('Trabajador')) {
        echo "✅ Clase Trabajador existe<br>";
        $trabajador = new Trabajador($db);
        
        $methods = ['obtenerPorId', 'obtenerTodos', 'crear', 'actualizar', 'existeIdentificacion'];
        foreach ($methods as $method) {
            if (method_exists($trabajador, $method)) {
                echo "✅ Método Trabajador::$method existe<br>";
            } else {
                echo "❌ Método Trabajador::$method NO existe<br>";
            }
        }
    } else {
        echo "❌ Clase Trabajador NO existe<br>";
    }
    
    // Verificar clase Empresa
    if (class_exists('Empresa')) {
        echo "✅ Clase Empresa existe<br>";
        $empresa = new Empresa($db);
        
        $methods = ['obtenerPorId', 'obtenerTodas', 'crear', 'actualizar'];
        foreach ($methods as $method) {
            if (method_exists($empresa, $method)) {
                echo "✅ Método Empresa::$method existe<br>";
            } else {
                echo "❌ Método Empresa::$method NO existe<br>";
            }
        }
    } else {
        echo "❌ Clase Empresa NO existe<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error al verificar clases: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// 5. Verificar funciones de utilidad
echo "<h2>🔧 Verificación de Funciones</h2>";
try {
    require_once 'includes/functions.php';
    
    $functions = ['isLoggedIn', 'redirect', 'showAlert', 'registrarAuditoria'];
    foreach ($functions as $function) {
        if (function_exists($function)) {
            echo "✅ Función $function existe<br>";
        } else {
            echo "❌ Función $function NO existe<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error al verificar funciones: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// 6. Verificar permisos de archivos
echo "<h2>📋 Verificación de Permisos</h2>";
$directories = ['uploads', 'uploads/fotos'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "✅ Directorio $dir es escribible<br>";
        } else {
            echo "⚠️ Directorio $dir NO es escribible<br>";
        }
    } else {
        echo "❌ Directorio $dir NO existe<br>";
    }
}

echo "<h2>🎯 Resumen de Verificación</h2>";
echo "<p>La verificación del proyecto ha sido completada. Revisa los resultados arriba para identificar cualquier problema.</p>";
echo "<p><strong>Recomendaciones:</strong></p>";
echo "<ul>";
echo "<li>Si hay archivos faltantes, ejecuta setup_database.php primero</li>";
echo "<li>Si hay errores de sintaxis, corrígelos antes de continuar</li>";
echo "<li>Si hay problemas de base de datos, verifica la configuración en config/database.php</li>";
echo "<li>Si hay directorios no escribibles, ajusta los permisos según sea necesario</li>";
echo "</ul>";
?>
