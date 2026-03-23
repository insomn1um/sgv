<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

if (!isAdmin()) {
    redirect('dashboard.php');
}

$database = Database::getInstance();
$db = $database->getConnection();

// Insertar algunos registros de auditoría de ejemplo
$registros_ejemplo = [
    [
        'usuario_id' => 1,
        'tipo_accion' => 'login',
        'modulo' => 'sistema',
        'descripcion' => 'Inicio de sesión exitoso',
        'ip' => '127.0.0.1'
    ],
    [
        'usuario_id' => 1,
        'tipo_accion' => 'create',
        'modulo' => 'empresas',
        'descripcion' => 'Creación de nueva empresa',
        'ip' => '127.0.0.1'
    ],
    [
        'usuario_id' => 1,
        'tipo_accion' => 'update',
        'modulo' => 'usuarios',
        'descripcion' => 'Actualización de perfil de usuario',
        'ip' => '127.0.0.1'
    ],
    [
        'usuario_id' => 1,
        'tipo_accion' => 'create',
        'modulo' => 'visitas',
        'descripcion' => 'Registro de nueva visita',
        'ip' => '127.0.0.1'
    ],
    [
        'usuario_id' => 1,
        'tipo_accion' => 'logout',
        'modulo' => 'sistema',
        'descripcion' => 'Cierre de sesión',
        'ip' => '127.0.0.1'
    ]
];

$sql = "INSERT INTO auditoria (usuario_id, tipo_accion, modulo, descripcion, ip, fecha) VALUES (?, ?, ?, ?, ?, NOW())";
$stmt = $db->prepare($sql);

$insertados = 0;
foreach ($registros_ejemplo as $registro) {
    try {
        $stmt->execute([
            $registro['usuario_id'],
            $registro['tipo_accion'],
            $registro['modulo'],
            $registro['descripcion'],
            $registro['ip']
        ]);
        $insertados++;
    } catch (PDOException $e) {
        echo "Error al insertar registro: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>Datos de Auditoría Insertados</h2>";
echo "<p>Se insertaron $insertados registros de auditoría de ejemplo.</p>";
echo "<p><a href='auditoria.php'>Ir a Auditoría</a></p>";
echo "<p><a href='dashboard.php'>Volver al Dashboard</a></p>";
?>
