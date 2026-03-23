<?php
require_once 'config/database.php';
require_once 'classes/Visitante.php';
require_once 'includes/functions.php';

$database = Database::getInstance();
$db = $database->getConnection();
$visitante = new Visitante($db);

$error = '';
$success = '';
$codigo_qr = '';
$visitante_creado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitante->nombre = trim($_POST['nombre']);
    $visitante->apellido = trim($_POST['apellido']);
    $visitante->tipo_identificacion = $_POST['tipo_identificacion'];
    $visitante->numero_identificacion = trim($_POST['numero_identificacion']);
    $visitante->empresa_representa = trim($_POST['empresa_representa']);
    $visitante->numero_contacto = trim($_POST['telefono']); // Mapear telefono a numero_contacto
    $visitante->email = trim($_POST['email']);
    $visitante->a_quien_visita = trim($_POST['a_quien_visita']);
    $visitante->motivo_visita = trim($_POST['motivo_visita']);
    $visitante->fecha_visita = $_POST['fecha_visita'];
    $visitante->hora_visita = $_POST['hora_visita'];
    $visitante->registrado_por = null; // Preregistro sin usuario específico
    
    if (!$visitante->nombre || !$visitante->apellido || !$visitante->tipo_identificacion || 
        !$visitante->numero_identificacion || !$visitante->a_quien_visita || !$visitante->motivo_visita ||
        !$visitante->fecha_visita || !$visitante->hora_visita) {
        $error = 'Los campos marcados con * son obligatorios.';
    } elseif ($visitante->identificacionExists($visitante->numero_identificacion)) {
        $error = 'Ya existe un visitante con esta identificación.';
    } else {
        // Crear el visitante
        $visitante_id = $visitante->create();
        if ($visitante_id) {
            // Generar código QR
            $codigo_qr = $visitante->generarCodigoQR($visitante_id);
            if ($codigo_qr) {
                $success = 'Preregistro realizado exitosamente. Su código QR ha sido generado.';
                $visitante_creado = [
                    'id' => $visitante_id,
                    'nombre' => $visitante->nombre,
                    'apellido' => $visitante->apellido,
                    'codigo_qr' => $codigo_qr
                ];
            } else {
                $error = 'Error al generar el código QR.';
            }
        } else {
            $error = 'Error al realizar el preregistro.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preregistro de Visita - SGV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="includes/sidebar.css">
    <style>
        body { 
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            min-height: 100vh;
        }
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        .form-control:focus {
            border-color: #1e293b;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .form-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 2rem;
        }
        .header-section {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 2rem;
            text-align: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container-fluid py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="main-container">
                    <!-- Header -->
                    <div class="header-section">
                        <div class="row align-items-center">
                            <div class="col-md-8 text-md-start">
                                <h1 class="mb-2">
                                    <i class="fas fa-qrcode"></i> Preregistro de Visita
                                </h1>
                                <p class="mb-0">Complete el formulario para generar su código QR de acceso</p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <a href="index.php" class="btn btn-outline-light">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="needs-validation" novalidate>
                            <!-- Información Personal -->
                            <div class="form-section">
                                <div class="card-header " style="background-color: #3b82f6; color: white;">
                                    <h4 class="mb-0"><i class="fas fa-user"></i> Información Personal</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="nombre">
                                                <i class="fas fa-id-card"></i> Nombre *
                                            </label>
                                            <input type="text" name="nombre" id="nombre" class="form-control" required placeholder="Nombre del visitante o contratista">
                                            <div class="invalid-feedback">
                                                Por favor ingrese el nombre del visitante o contratista.
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="apellido">
                                                <i class="fas fa-id-card"></i> Apellido *
                                            </label>
                                            <input type="text" name="apellido" id="apellido" class="form-control" required placeholder="Apellido del visitante o contratista">
                                            <div class="invalid-feedback">
                                                Por favor ingrese el apellido del visitante o contratista.
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="tipo_identificacion">
                                                <i class="fas fa-id-badge"></i> Tipo de Identificación *
                                            </label>
                                            <select name="tipo_identificacion" id="tipo_identificacion" class="form-select" required>
                                                <option value="">Seleccione...</option>
                                                <option value="RUT">RUT</option>
                                                <option value="DNI">DNI</option>
                                            </select>
                                            <div class="invalid-feedback">
                                                Por favor seleccione el tipo de identificación del visitante o contratista.
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="numero_identificacion">
                                                <i class="fas fa-hashtag"></i> Número de Identificación *
                                            </label>
                                            <input type="text" name="numero_identificacion" id="numero_identificacion" class="form-control" required>
                                            <div class="invalid-feedback">
                                                Por favor ingrese el número de identificación del visitante o contratista.
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="empresa_representa">
                                                <i class="fas fa-building"></i> Empresa que Representa
                                            </label>
                                            <input type="text" name="empresa_representa" id="empresa_representa" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="telefono">
                                                <i class="fas fa-phone"></i> Teléfono
                                            </label>
                                            <input type="tel" name="telefono" id="telefono" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="email">
                                                <i class="fas fa-envelope"></i> Email
                                            </label>
                                            <input type="email" name="email" id="email" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Información de la Visita -->
                            <div class="form-section">
                                <div class="card-header bg-success text-white">
                                    <h4 class="mb-0"><i class="fas fa-calendar-check"></i> Información de la Visita</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="a_quien_visita">
                                                <i class="fas fa-user-tie"></i> A quien visita *
                                            </label>
                                            <input type="text" name="a_quien_visita" id="a_quien_visita" class="form-control" required>
                                            <div class="invalid-feedback">
                                                Por favor ingrese a quien visita.
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="fecha_visita">
                                                <i class="fas fa-calendar"></i> Fecha de Visita *
                                            </label>
                                            <input type="date" name="fecha_visita" id="fecha_visita" class="form-control" required>
                                            <div class="invalid-feedback">
                                                Por favor seleccione la fecha de visita.
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="hora_visita">
                                                <i class="fas fa-clock"></i> Hora de Visita *
                                            </label>
                                            <input type="time" name="hora_visita" id="hora_visita" class="form-control" required>
                                            <div class="invalid-feedback">
                                                Por favor seleccione la hora de visita.
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="motivo_visita">
                                                <i class="fas fa-comment"></i> Motivo de la visita *
                                            </label>
                                            <textarea name="motivo_visita" id="motivo_visita" class="form-control" rows="3" required></textarea>
                                            <div class="invalid-feedback">
                                                Por favor ingrese el motivo de la visita.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-qrcode"></i> Generar Código QR
                                </button>
                            </div>
                        </form>
                        
                        <div class="mt-4 text-center">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Al completar este formulario, se generará un código QR único que deberá presentar en recepción.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para mostrar el código QR -->
    <?php if ($visitante_creado): ?>
    <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="qrModalLabel">
                        <i class="fas fa-qrcode"></i> Código QR Generado
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <h6>Visitante o Contratista: <?php echo $visitante_creado['nombre'] . ' ' . $visitante_creado['apellido']; ?></h6>
                        <p class="text-muted">Código QR: <?php echo $visitante_creado['codigo_qr']; ?></p>
                    </div>
                    
                    <!-- Aquí se generará el código QR visual -->
                    <div id="qrcode" class="mb-3"></div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Instrucciones:</strong><br>
                        1. Guarde o imprima este código QR<br>
                        2. Preséntelo en recepción al momento de su visita<br>
                        3. El código es válido solo para la fecha programada
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="imprimirQR()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script>
        // Validación de formularios
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        // Establecer fecha mínima como hoy
        document.addEventListener('DOMContentLoaded', function() {
            const fechaInput = document.querySelector('input[name="fecha_visita"]');
            const today = new Date().toISOString().split('T')[0];
            fechaInput.setAttribute('min', today);
            
            // Mostrar modal de QR si existe
            <?php if ($visitante_creado): ?>
            const qrModal = new bootstrap.Modal(document.getElementById('qrModal'));
            qrModal.show();
            
            // Generar código QR visual
            const qrData = '<?php echo $visitante_creado['codigo_qr']; ?>';
            QRCode.toCanvas(document.getElementById('qrcode'), qrData, {
                width: 200,
                margin: 2,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                }
            }, function (error) {
                if (error) console.error(error);
            });
            <?php endif; ?>
        });
        
        // Función para imprimir QR
        function imprimirQR() {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Código QR - <?php echo $visitante_creado ? $visitante_creado['nombre'] . ' ' . $visitante_creado['apellido'] : ''; ?></title>
                    <style>
                        body { font-family: Arial, sans-serif; text-align: center; padding: 20px; }
                        .qr-container { margin: 20px 0; }
                        .qr-code { border: 2px solid #333; padding: 10px; display: inline-block; }
                        .info { margin: 20px 0; }
                    </style>
                </head>
                <body>
                    <h2>Código QR de Acceso</h2>
                    <div class="info">
                        <strong>Visitante o Contratista:</strong> <?php echo $visitante_creado ? $visitante_creado['nombre'] . ' ' . $visitante_creado['apellido'] : ''; ?><br>
                        <strong>Código:</strong> <?php echo $visitante_creado ? $visitante_creado['codigo_qr'] : ''; ?><br>
                        <strong>Fecha de generación:</strong> <?php echo date('d/m/Y H:i'); ?>
                    </div>
                    <div class="qr-container">
                        <div class="qr-code" id="printQR"></div>
                    </div>
                    <p><small>Presente este código en recepción al momento de su visita</small></p>
                </body>
                </html>
            `);
            
            // Generar QR en la ventana de impresión
            QRCode.toCanvas(printWindow.document.getElementById('printQR'), '<?php echo $visitante_creado ? $visitante_creado['codigo_qr'] : ''; ?>', {
                width: 200,
                margin: 2
            }, function (error) {
                if (error) console.error(error);
                else printWindow.print();
            });
        }
    </script>
</body>
</html> 