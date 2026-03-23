<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Usuario.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$database = Database::getInstance();
$db = $database->getConnection();
$usuario = new Usuario($db);
$usuario->id = $_SESSION['user_id'];
$usuario->readOne();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual = $_POST['actual'] ?? '';
    $nueva = $_POST['nueva'] ?? '';
    $repetir = $_POST['repetir'] ?? '';
    if (!$actual || !$nueva || !$repetir) {
        $error = 'Todos los campos son obligatorios.';
    } elseif ($nueva !== $repetir) {
        $error = 'Las contraseñas nuevas no coinciden.';
    } else {
        // Verificar contraseña actual
        $stmt = $db->prepare('SELECT password FROM usuarios WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && password_verify($actual, $row['password'])) {
            $usuario->changePassword($nueva);
            showAlert('Contraseña cambiada exitosamente.','success');
            redirect('dashboard.php');
        } else {
            $error = 'La contraseña actual es incorrecta.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - SGV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-lg">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0"><i class="fas fa-key"></i> Cambiar Contraseña</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="actual" class="form-label">Contraseña Actual *</label>
                                <input type="password" id="actual" name="actual" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="nueva" class="form-label">Nueva Contraseña *</label>
                                <input type="password" id="nueva" name="nueva" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="repetir" class="form-label">Repetir Nueva Contraseña *</label>
                                <input type="password" id="repetir" name="repetir" class="form-control" required>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                                <button type="submit" class="btn btn-info"><i class="fas fa-save"></i> Cambiar Contraseña</button>
                            </div>
                        </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 