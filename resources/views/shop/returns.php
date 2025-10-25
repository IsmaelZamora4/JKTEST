<?php
require_once BASE_PATH . 'config/config.php';

// Helper para versionar imágenes locales y normalizar slashes
function asset_url_with_v($path)
{
    if (!$path) return '';
    $path = str_replace('\\', '/', $path);
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $fs = __DIR__ . '/' . ltrim($path, '/');
    if (file_exists($fs)) {
        return $path . '?v=' . filemtime($fs);
    }
    return $path;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambios y Devoluciones - <?php echo APP_NAME; ?></title>
    <meta name="description" content="Conoce nuestras políticas de cambios y devoluciones para productos textiles personalizados de JK Grupo Textil.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/variables.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    <link href="assets/css/components.css" rel="stylesheet">
    <link href="assets/css/layout.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <link href="assets/css/utilities.css" rel="stylesheet">
</head>

<body>
    <?php include COMPONENT_PATH . 'header.php'; ?>

    <!-- Breadcrumb -->
    <section class="py-4 bg-light">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb bg-transparent p-0 mb-0">
                    <li class="breadcrumb-item">
                        <a href="index.php" class="text-decoration-none text-secondary">
                            <i class="fas fa-home me-1 text-primary"></i>Inicio
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="#" class="text-decoration-none text-secondary">Ayuda</a>
                    </li>
                    <li class="breadcrumb-item active text-primary fw-medium" aria-current="page">
                        Cambios y Devoluciones
                    </li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Hero Section -->
    <section class="py-5 bg-dark text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-4">Cambios y Devoluciones</h1>
                    <p class="lead mb-4">
                        Tu satisfacción es nuestra prioridad. Conoce nuestras políticas de cambios y
                        devoluciones para productos textiles personalizados.
                    </p>
                </div>
                <div class="col-lg-4 text-center">
                    <i class="fas fa-undo fa-5x text-warning"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Return Policies -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <!-- Política General -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-shield-alt me-2"></i>Política General
                            </h4>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">En JK Grupo Textil garantizamos la calidad de nuestros productos. Ofrecemos cambios y devoluciones bajo las siguientes condiciones:</p>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Plazo máximo: 7 días calendario desde la recepción</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Producto en perfecto estado, sin uso</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Etiquetas originales intactas</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Empaque original conservado</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Productos Elegibles -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-check-circle me-2"></i>Productos Elegibles para Cambio/Devolución
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-success">✅ SÍ Aplica</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-tshirt me-2 text-muted"></i>Productos en stock sin personalizar</li>
                                        <li><i class="fas fa-exclamation-triangle me-2 text-muted"></i>Productos con defectos de fabricación</li>
                                        <li><i class="fas fa-shipping-fast me-2 text-muted"></i>Productos dañados en el envío</li>
                                        <li><i class="fas fa-times-circle me-2 text-muted"></i>Error en el pedido (nuestro error)</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-danger">❌ NO Aplica</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-paint-brush me-2 text-muted"></i>Productos personalizados/bordados</li>
                                        <li><i class="fas fa-palette me-2 text-muted"></i>Productos con diseños específicos</li>
                                        <li><i class="fas fa-user-edit me-2 text-muted"></i>Productos hechos a medida</li>
                                        <li><i class="fas fa-heart me-2 text-muted"></i>Cambio de opinión del cliente</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Proceso de Devolución -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-list-ol me-2"></i>Proceso de Devolución
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; flex-shrink: 0;">
                                            <span class="fw-bold">1</span>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold">Contacta con nosotros</h6>
                                            <p class="text-muted mb-0">Envía un mensaje por WhatsApp o email explicando el motivo</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; flex-shrink: 0;">
                                            <span class="fw-bold">2</span>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold">Evaluación</h6>
                                            <p class="text-muted mb-0">Revisamos tu solicitud y te proporcionamos una respuesta</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; flex-shrink: 0;">
                                            <span class="fw-bold">3</span>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold">Envío del producto</h6>
                                            <p class="text-muted mb-0">Empaca el producto y envíalo a nuestra dirección</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; flex-shrink: 0;">
                                            <span class="fw-bold">4</span>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold">Procesamiento</h6>
                                            <p class="text-muted mb-0">Procesamos tu devolución en 3-5 días hábiles</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Garantía de Calidad -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-warning text-dark">
                            <h4 class="mb-0">
                                <i class="fas fa-award me-2"></i>Garantía de Calidad
                            </h4>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">Ofrecemos garantía completa en los siguientes casos:</p>
                            <div class="row">
                                <div class="col-md-4 text-center mb-3">
                                    <i class="fas fa-tools fa-3x text-warning mb-2"></i>
                                    <h6 class="fw-bold">Defectos de Fabricación</h6>
                                    <p class="text-muted small">Costuras defectuosas, materiales deficientes</p>
                                </div>
                                <div class="col-md-4 text-center mb-3">
                                    <i class="fas fa-palette fa-3x text-info mb-2"></i>
                                    <h6 class="fw-bold">Error en Personalización</h6>
                                    <p class="text-muted small">Diseño incorrecto, colores equivocados</p>
                                </div>
                                <div class="col-md-4 text-center mb-3">
                                    <i class="fas fa-ruler fa-3x text-success mb-2"></i>
                                    <h6 class="fw-bold">Tallas Incorrectas</h6>
                                    <p class="text-muted small">Error en las medidas especificadas</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información Importante -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-secondary text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Información Importante
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <h6 class="fw-bold">⚠️ Productos Personalizados</h6>
                                <p class="mb-0">Los productos con diseños personalizados, bordados o impresiones específicas NO son elegibles para cambio por cambio de opinión, solo por defectos de calidad.</p>
                            </div>
                            <div class="alert alert-info">
                                <h6 class="fw-bold">💰 Reembolsos</h6>
                                <p class="mb-0">Los reembolsos se procesan en 5-7 días hábiles una vez aprobada la devolución. El costo de envío de devolución corre por cuenta del cliente, excepto en casos de error nuestro.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="card bg-dark text-white">
                        <div class="card-body text-center py-5">
                            <h3 class="fw-bold mb-3">¿Necesitas Procesar un Cambio o Devolución?</h3>
                            <p class="mb-4">Contáctanos y te ayudaremos con el proceso paso a paso</p>
                            <div class="d-flex justify-content-center gap-3 flex-wrap">
                                <a href="https://wa.me/51999977257?text=Hola,%20necesito%20procesar%20un%20cambio%20o%20devolución" target="_blank" class="btn btn-success btn-lg">
                                    <i class="fab fa-whatsapp me-2"></i>WhatsApp: 0
                                </a>
                                <a href="contact.php" class="btn btn-outline-light btn-lg">
                                    <i class="fas fa-envelope me-2"></i>Formulario de Contacto
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include COMPONENT_PATH . 'footer.php'; ?>
</body>

</html>