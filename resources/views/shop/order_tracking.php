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
    <title>Seguimiento de Pedidos - <?php echo APP_NAME; ?></title>
    <meta name="description" content="Rastrea el estado de tu pedido en JK Grupo Textil. Ingresa tu número de pedido para conocer el estado actual de tu compra.">

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
                        Seguimiento de Pedidos
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
                    <h1 class="display-4 fw-bold mb-4">Seguimiento de Pedidos</h1>
                    <p class="lead mb-4">
                        Conoce el progreso de tu pedido textil personalizado. Todos nuestros pedidos
                        se gestionan a través de WhatsApp para brindarte atención personalizada.
                    </p>
                </div>
                <div class="col-lg-4 text-center">
                    <i class="fas fa-clipboard-list fa-5x text-warning"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Order Process Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-dark mb-3">Proceso de tu Pedido</h2>
                <div class="mx-auto bg-warning" style="width: 80px; height: 4px; border-radius: 2px;"></div>
                <p class="lead text-muted mt-3">Así es como manejamos tu pedido desde el inicio hasta la entrega</p>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning">
                                <i class="fas fa-comments text-dark"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="fw-bold text-dark mb-2">1. Contacto Inicial</h5>
                                        <p class="text-muted mb-0">Te contactas con nosotros vía WhatsApp para solicitar tu cotización con todos los detalles de tu proyecto.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning">
                                <i class="fas fa-calculator text-dark"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="fw-bold text-dark mb-2">2. Cotización Personalizada</h5>
                                        <p class="text-muted mb-0">Preparamos una cotización detallada basada en tus especificaciones, cantidad, diseño y tipo de personalización.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning">
                                <i class="fas fa-handshake text-dark"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="fw-bold text-dark mb-2">3. Confirmación del Pedido</h5>
                                        <p class="text-muted mb-0">Una vez aprobada la cotización, confirmas tu pedido y realizas el pago según las condiciones acordadas.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning">
                                <i class="fas fa-cogs text-dark"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="fw-bold text-dark mb-2">4. Producción</h5>
                                        <p class="text-muted mb-0">Iniciamos la confección y personalización de tus prendas. Te mantenemos informado del progreso vía WhatsApp.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning">
                                <i class="fas fa-check-circle text-dark"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="fw-bold text-dark mb-2">5. Control de Calidad</h5>
                                        <p class="text-muted mb-0">Revisamos cada prenda para asegurar que cumple con nuestros estándares de calidad antes del envío.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-marker bg-success">
                                <i class="fas fa-shipping-fast text-white"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="fw-bold text-dark mb-2">6. Envío y Entrega</h5>
                                        <p class="text-muted mb-0">Enviamos tu pedido y te proporcionamos el número de seguimiento para que rastrees la entrega.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline Styles -->
    <style>
        .timeline {
            position: relative;
            padding-left: 0;
        }

        .timeline-item {
            position: relative;
            padding-left: 80px;
            margin-bottom: 40px;
        }

        .timeline-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: 25px;
            top: 60px;
            width: 2px;
            height: calc(100% + 10px);
            background: #d4af37;
        }

        .timeline-marker {
            position: absolute;
            left: 0;
            top: 0;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            z-index: 2;
        }

        .timeline-content {
            flex: 1;
        }

        @media (max-width: 768px) {
            .timeline-item {
                padding-left: 60px;
            }

            .timeline-marker {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .timeline-item:not(:last-child)::before {
                left: 20px;
            }
        }
    </style>


    <!-- Order Status Info -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-3">Estados de Pedido</h2>
                <p class="text-muted">Conoce qué significa cada estado de tu pedido</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="fas fa-clock fa-3x text-warning"></i>
                            </div>
                            <h5 class="fw-bold">Pendiente</h5>
                            <p class="text-muted">Tu pedido ha sido recibido y está siendo procesado</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="fas fa-cogs fa-3x text-info"></i>
                            </div>
                            <h5 class="fw-bold">En Producción</h5>
                            <p class="text-muted">Estamos confeccionando tu producto personalizado</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="fas fa-truck fa-3x text-primary"></i>
                            </div>
                            <h5 class="fw-bold">En Camino</h5>
                            <p class="text-muted">Tu pedido está siendo enviado a tu dirección</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="fas fa-check-circle fa-3x text-success"></i>
                            </div>
                            <h5 class="fw-bold">Entregado</h5>
                            <p class="text-muted">Tu pedido ha sido entregado exitosamente</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Support -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="card bg-dark text-white">
                        <div class="card-body text-center py-5">
                            <h3 class="fw-bold mb-3">¿Necesitas Ayuda?</h3>
                            <p class="mb-4">Si tienes problemas para rastrear tu pedido o necesitas más información, contáctanos</p>
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

    <script>
        document.getElementById('trackingForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const orderNumber = document.getElementById('orderNumber').value;
            const customerEmail = document.getElementById('customerEmail').value;

            if (!orderNumber || !customerEmail) {
                alert('Por favor completa todos los campos');
                return;
            }

            // Simulate tracking (in real implementation, this would call an API)
            alert('Función de rastreo en desarrollo. Por favor contacta por WhatsApp para consultar el estado de tu pedido.');

            // Redirect to WhatsApp with tracking request
            const message = `Hola, quiero consultar el estado de mi pedido:\n\nNúmero de pedido: ${orderNumber}\nEmail: ${customerEmail}`;
            const encodedMessage = encodeURIComponent(message);
            window.open(`https://wa.me/51999977257?text=${encodedMessage}`, '_blank');
        });
    </script>
</body>

</html>