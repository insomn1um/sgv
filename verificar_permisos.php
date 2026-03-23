<?php
/**
 * Script de Verificación de Permisos por Rol
 * Este archivo verifica que todos los archivos tengan las validaciones correctas
 */

session_start();
require_once 'includes/functions.php';

// Simular sesión si no está iniciada
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['nombre'] = 'Test';
    $_SESSION['apellido'] = 'User';
}

// Cambiar el rol para pruebas
$rol_prueba = $_GET['rol'] ?? 'operador';
$_SESSION['rol'] = $rol_prueba;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Permisos - SGV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .page-item {
            margin: 5px 0;
            padding: 10px;
            border-radius: 5px;
        }
        .acceso-permitido {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .acceso-denegado {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .btn-rol {
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card mt-4">
            <div class="card-header bg-primary text-white text-center">
                <h2><i class="fas fa-shield-alt"></i> Verificación de Permisos por Rol</h2>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <h5>Selecciona un Rol para Probar:</h5>
                    <a href="?rol=admin" class="btn btn-danger btn-rol <?php echo $rol_prueba === 'admin' ? 'active' : ''; ?>">
                        <i class="fas fa-user-shield"></i> Administrador
                    </a>
                    <a href="?rol=supervisor" class="btn btn-warning btn-rol <?php echo $rol_prueba === 'supervisor' ? 'active' : ''; ?>">
                        <i class="fas fa-user-tie"></i> Supervisor
                    </a>
                    <a href="?rol=operador" class="btn btn-info btn-rol <?php echo $rol_prueba === 'operador' ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i> Operador
                    </a>
                </div>
                
                <div class="alert alert-info text-center">
                    <h5>
                        <i class="fas fa-user-circle"></i> Probando como: 
                        <strong><?php echo strtoupper($rol_prueba); ?></strong>
                    </h5>
                </div>

                <?php
                // Definir páginas y sus restricciones
                $paginas = [
                    // Páginas para todos
                    ['archivo' => 'dashboard.php', 'nombre' => 'Dashboard', 'todos' => true],
                    ['archivo' => 'visitas.php', 'nombre' => 'Lista de Visitas', 'todos' => true],
                    ['archivo' => 'nueva_visita.php', 'nombre' => 'Nueva Visita', 'todos' => true],
                    ['archivo' => 'ver_visita.php', 'nombre' => 'Ver Visita', 'todos' => true],
                    ['archivo' => 'cambiar_contrasena.php', 'nombre' => 'Cambiar Contraseña', 'todos' => true],
                    
                    // Solo Admin
                    ['archivo' => 'usuarios.php', 'nombre' => 'Gestión de Usuarios', 'admin' => true],
                    ['archivo' => 'crear_usuario.php', 'nombre' => 'Crear Usuario', 'admin' => true],
                    ['archivo' => 'editar_usuario.php', 'nombre' => 'Editar Usuario', 'admin' => true],
                    ['archivo' => 'auditoria.php', 'nombre' => 'Auditoría', 'admin' => true],
                    
                    // Admin y Supervisor
                    ['archivo' => 'gestion_empresas.php', 'nombre' => 'Gestión de Empresas', 'supervisor' => true],
                    ['archivo' => 'empresas.php', 'nombre' => 'Lista de Empresas', 'supervisor' => true],
                    ['archivo' => 'nueva_empresa.php', 'nombre' => 'Nueva Empresa', 'supervisor' => true],
                    ['archivo' => 'editar_empresa.php', 'nombre' => 'Editar Empresa', 'supervisor' => true],
                    ['archivo' => 'ver_empresa.php', 'nombre' => 'Ver Empresa', 'supervisor' => true],
                    ['archivo' => 'trabajadores.php', 'nombre' => 'Lista de Trabajadores', 'supervisor' => true],
                    ['archivo' => 'nuevo_trabajador.php', 'nombre' => 'Nuevo Trabajador', 'supervisor' => true],
                    ['archivo' => 'editar_trabajador.php', 'nombre' => 'Editar Trabajador', 'supervisor' => true],
                    ['archivo' => 'ver_trabajador.php', 'nombre' => 'Ver Trabajador', 'supervisor' => true],
                    ['archivo' => 'reportes.php', 'nombre' => 'Reportes', 'supervisor' => true],
                ];

                function tieneAcceso($pagina, $rol) {
                    // Páginas para todos
                    if (isset($pagina['todos']) && $pagina['todos']) {
                        return true;
                    }
                    
                    // Solo admin
                    if (isset($pagina['admin']) && $pagina['admin']) {
                        return $rol === 'admin';
                    }
                    
                    // Admin y supervisor
                    if (isset($pagina['supervisor']) && $pagina['supervisor']) {
                        return $rol === 'admin' || $rol === 'supervisor';
                    }
                    
                    return false;
                }

                // Agrupar por categoría
                $categorias = [
                    'Acceso Universal (Todos los Roles)' => [],
                    'Solo Administrador' => [],
                    'Administrador y Supervisor' => []
                ];

                foreach ($paginas as $pagina) {
                    if (isset($pagina['todos']) && $pagina['todos']) {
                        $categorias['Acceso Universal (Todos los Roles)'][] = $pagina;
                    } elseif (isset($pagina['admin']) && $pagina['admin']) {
                        $categorias['Solo Administrador'][] = $pagina;
                    } elseif (isset($pagina['supervisor']) && $pagina['supervisor']) {
                        $categorias['Administrador y Supervisor'][] = $pagina;
                    }
                }

                foreach ($categorias as $categoria => $paginas_cat) {
                    if (empty($paginas_cat)) continue;
                    
                    echo "<h5 class='mt-4 mb-3'><i class='fas fa-folder-open'></i> $categoria</h5>";
                    
                    foreach ($paginas_cat as $pagina) {
                        $tiene_acceso = tieneAcceso($pagina, $rol_prueba);
                        $clase = $tiene_acceso ? 'acceso-permitido' : 'acceso-denegado';
                        $icono = $tiene_acceso ? 'check-circle text-success' : 'times-circle text-danger';
                        $texto = $tiene_acceso ? 'Acceso Permitido' : 'Acceso Denegado';
                        
                        echo "<div class='page-item $clase'>";
                        echo "<div class='d-flex justify-content-between align-items-center'>";
                        echo "<div>";
                        echo "<strong><i class='fas fa-file'></i> " . htmlspecialchars($pagina['nombre']) . "</strong><br>";
                        echo "<small class='text-muted'>" . htmlspecialchars($pagina['archivo']) . "</small>";
                        echo "</div>";
                        echo "<div>";
                        echo "<span class='badge " . ($tiene_acceso ? 'bg-success' : 'bg-danger') . "'>";
                        echo "<i class='fas fa-$icono'></i> $texto";
                        echo "</span>";
                        echo "</div>";
                        echo "</div>";
                        echo "</div>";
                    }
                }
                ?>

                <div class="alert alert-warning mt-4">
                    <h6><i class="fas fa-info-circle"></i> Resumen de Permisos:</h6>
                    <ul class="mb-0">
                        <li><strong>Operador:</strong> Solo Dashboard y Visitas</li>
                        <li><strong>Supervisor:</strong> Dashboard, Visitas, Gestión (Empresas/Trabajadores) y Reportes</li>
                        <li><strong>Administrador:</strong> Acceso completo a todo el sistema</li>
                    </ul>
                </div>

                <div class="card mt-4">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="fas fa-code"></i> Información de Sesión</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <th>Usuario ID:</th>
                                <td><?php echo $_SESSION['user_id']; ?></td>
                            </tr>
                            <tr>
                                <th>Nombre:</th>
                                <td><?php echo $_SESSION['nombre'] . ' ' . $_SESSION['apellido']; ?></td>
                            </tr>
                            <tr>
                                <th>Rol Actual:</th>
                                <td><span class="badge bg-primary"><?php echo strtoupper($rol_prueba); ?></span></td>
                            </tr>
                            <tr>
                                <th>isAdmin():</th>
                                <td><?php echo isAdmin() ? '✅ true' : '❌ false'; ?></td>
                            </tr>
                            <tr>
                                <th>isSupervisor():</th>
                                <td><?php echo isSupervisor() ? '✅ true' : '❌ false'; ?></td>
                            </tr>
                            <tr>
                                <th>isOperador():</th>
                                <td><?php echo isOperador() ? '✅ true' : '❌ false'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Ir al Dashboard
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


