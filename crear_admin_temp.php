<?php
// Script temporal para crear un usuario administrador en SGV
require_once 'config/database.php';

$usuario = 'nuevoadmin2';
$contrasena = 'admin';
$email = 'nuevoadmin2@sgv.com';
$nombre = 'Nuevo';
$apellido = 'Administrador';
$rol = 'administrador';
$activo = 1;

$database = Database::getInstance();
$db = $database->getConnection();

// Verificar si ya existe el usuario
$stmt = $db->prepare("SELECT id FROM usuarios WHERE username = ?");
$stmt->execute([$usuario]);
if ($stmt->fetch()) {
    echo "<h3>El usuario ya existe.</h3>";
    exit;
}

$hash = password_hash($contrasena, PASSWORD_DEFAULT);
$sql = "INSERT INTO usuarios (username, password, email, nombre, apellido, rol, activo)
        VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $db->prepare($sql);
$ok = $stmt->execute([$usuario, $hash, $email, $nombre, $apellido, $rol, $activo]);

if ($ok) {
    echo "<h3>Usuario administrador creado correctamente.</h3>";
    echo "<b>Usuario:</b> $usuario<br><b>Contraseña:</b> $contrasena";
} else {
    echo "<h3>Error al crear el usuario.</h3>";
}

// IMPORTANTE: Borra este archivo después de usarlo por seguridad. 