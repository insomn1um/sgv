
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug de Errores</h1>";

// Verificar si hay errores en el log de PHP
$error_log_path = '/Applications/XAMPP/xamppfiles/logs/php_error_log';
if (file_exists($error_log_path)) {
    echo "<h2>Últimos errores en el log de PHP:</h2>";
    $log_content = file_get_contents($error_log_path);
    $lines = explode("\n", $log_content);
    $recent_lines = array_slice($lines, -20); // Últimas 20 líneas
    echo "<pre>" . implode("\n", $recent_lines) . "</pre>";
} else {
    echo "<p>No se encontró el archivo de log de PHP en: $error_log_path</p>";
}

// Verificar si hay errores en el log de Apache
$apache_log_path = '/Applications/XAMPP/xamppfiles/logs/error_log';
if (file_exists($apache_log_path)) {
    echo "<h2>Últimos errores en el log de Apache:</h2>";
    $log_content = file_get_contents($apache_log_path);
    $lines = explode("\n", $log_content);
    $recent_lines = array_slice($lines, -20); // Últimas 20 líneas
    echo "<pre>" . implode("\n", $recent_lines) . "</pre>";
} else {
    echo "<p>No se encontró el archivo de log de Apache en: $apache_log_path</p>";
}

// Verificar configuración de PHP
echo "<h2>Configuración de PHP:</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Display Errors: " . (ini_get('display_errors') ? 'On' : 'Off') . "</p>";
echo "<p>Error Reporting: " . ini_get('error_reporting') . "</p>";

// Verificar permisos de archivos
echo "<h2>Permisos de archivos importantes:</h2>";
$files_to_check = [
    'index.php',
    'config/database.php',
    'classes/Usuario.php',
    'includes/functions.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        $perms_octal = substr(sprintf('%o', $perms), -4);
        echo "<p>$file: $perms_octal (" . (is_readable($file) ? 'readable' : 'not readable') . ")</p>";
    } else {
        echo "<p>$file: No existe</p>";
    }
}
?> 