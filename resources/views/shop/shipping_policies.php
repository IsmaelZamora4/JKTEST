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
    <title>Políticas de Envío - <?php echo APP_NAME; ?></title>
    <meta name="description" content="Conoce nuestras políticas de envío, tiempos de entrega y costos para productos textiles personalizados en todo el Perú.">

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
                        Políticas de Envío
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
                    <h1 class="display-4 fw-bold mb-4">Políticas de Envío</h1>
                    <p class="lead mb-4">
                        Conoce nuestras políticas de envío, tiempos de entrega y costos para productos
                        textiles personalizados en todo el Perú.
                    </p>
                </div>
                <div class="col-lg-4 text-center">
                    <i class="fas fa-shipping-fast fa-5x text-warning"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Shipping Information -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <!-- Envío Gratis -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-gift me-2"></i>Envío Gratis
                            </h4>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">Disfruta de envío gratuito en los siguientes casos:</p>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Pedidos mayores a S/ 150.00</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Pedidos mayoristas (12+ unidades)</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Lima Metropolitana y Callao</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Tiempos de Entrega -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-clock me-2"></i>Tiempos de Entrega
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-primary">Huancayo, Tambo & Chilca</h6>
                                    <ul class="list-unstyled mb-3">
                                        <li><i class="fas fa-truck me-2 text-muted"></i>Productos en stock: 1-2 días</li>
                                        <li><i class="fas fa-cogs me-2 text-muted"></i>Productos personalizados: 5-10 días</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-primary">Provincias</h6>
                                    <ul class="list-unstyled mb-3">
                                        <li><i class="fas fa-truck me-2 text-muted"></i>Productos en stock: 1-3 días</li>
                                        <li><i class="fas fa-cogs me-2 text-muted"></i>Productos personalizados: 5-10 días</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Los tiempos pueden variar según la complejidad del diseño y la temporada.
                            </div>
                        </div>
                    </div>

                    <!-- Costos de Envío -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-warning text-dark">
                            <h4 class="mb-0">
                                <i class="fas fa-calculator me-2"></i>Costos de Envío
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Destino</th>
                                            <th>Costo Regular</th>
                                            <th>Costo Express</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Lima Metropolitana</td>
                                            <td>S/ 15.00</td>
                                            <td>S/ 25.00</td>
                                        </tr>
                                        <tr>
                                            <td>Provincias (Costa)</td>
                                            <td>S/ 20.00</td>
                                            <td>S/ 35.00</td>
                                        </tr>
                                        <tr>
                                            <td>Provincias (Sierra/Selva)</td>
                                            <td>S/ 25.00</td>
                                            <td>S/ 40.00</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Métodos de Envío -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-box me-2"></i>Métodos de Envío
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-truck fa-2x text-primary me-3 mt-1"></i>
                                        <div>
                                            <h6 class="fw-bold">Courier Especializado</h6>
                                            <p class="text-muted mb-0">Envíos a todo el Perú (Shalom, Marvisur & Móvil bus y/o agencia de su preferencia).</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-store fa-2x text-success me-3 mt-1"></i>
                                        <div>
                                            <h6 class="fw-bold">Recojo en Tienda</h6>
                                            <p class="text-muted mb-0">Retira tu pedido directamente en nuestras instalaciones</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Condiciones Especiales -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-secondary text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>Condiciones Especiales
                            </h4>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-calendar-alt me-2 text-muted"></i>
                                    <strong>Pedidos Urgentes:</strong> Disponible con 48h de anticipación (costo adicional)
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-users me-2 text-muted"></i>
                                    <strong>Pedidos Corporativos:</strong> Condiciones especiales para empresas
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                    <strong>Zonas Remotas:</strong> Consultar disponibilidad y costos adicionales
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-weight-hanging me-2 text-muted"></i>
                                    <strong>Pedidos Voluminosos:</strong> Costo calculado según peso y dimensiones
                                </li>
                            </ul>
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
                            <h3 class="fw-bold mb-3">¿Tienes Preguntas sobre Envíos?</h3>
                            <p class="mb-4">Nuestro equipo está listo para ayudarte con cualquier consulta sobre envíos</p>
                            <div class="d-flex justify-content-center gap-3 flex-wrap">
                                <a href="https://wa.me/51999977257" target="_blank" class="btn btn-success btn-lg">
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