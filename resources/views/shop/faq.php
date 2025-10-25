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
    <title>Preguntas Frecuentes - <?php echo APP_NAME; ?></title>
    <meta name="description" content="Encuentra respuestas a las preguntas más frecuentes sobre productos textiles personalizados, envíos, pagos y más en JK Grupo Textil.">

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
                        Preguntas Frecuentes
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
                    <h1 class="display-4 fw-bold mb-4">Preguntas Frecuentes</h1>
                    <p class="lead mb-4">
                        Encuentra respuestas rápidas a las preguntas más comunes sobre nuestros productos
                        textiles personalizados, servicios y políticas.
                    </p>
                </div>
                <div class="col-lg-4 text-center">
                    <i class="fas fa-question-circle fa-5x text-warning"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <!-- Search FAQ -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="input-group">
                                <input type="text" class="form-control" id="faqSearch" placeholder="Buscar en preguntas frecuentes...">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Categories -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="btn-group-vertical d-md-none w-100 mb-3" role="group">
                                <button type="button" class="btn btn-outline-primary active" data-category="all">Todas</button>
                                <button type="button" class="btn btn-outline-primary" data-category="products">Productos</button>
                                <button type="button" class="btn btn-outline-primary" data-category="orders">Pedidos</button>
                                <button type="button" class="btn btn-outline-primary" data-category="shipping">Envíos</button>
                                <button type="button" class="btn btn-outline-primary" data-category="payments">Pagos</button>
                            </div>
                            <div class="btn-group d-none d-md-flex w-100" role="group">
                                <button type="button" class="btn btn-outline-primary active" data-category="all">Todas</button>
                                <button type="button" class="btn btn-outline-primary" data-category="products">Productos</button>
                                <button type="button" class="btn btn-outline-primary" data-category="orders">Pedidos</button>
                                <button type="button" class="btn btn-outline-primary" data-category="shipping">Envíos</button>
                                <button type="button" class="btn btn-outline-primary" data-category="payments">Pagos</button>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Accordion -->
                    <div class="accordion" id="faqAccordion">
                        <!-- Productos -->
                        <div class="accordion-item faq-item" data-category="products">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    <i class="fas fa-tshirt me-2 text-primary"></i>
                                    ¿Qué tipos de productos textiles ofrecen?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Ofrecemos una amplia gama de productos textiles personalizados incluyendo:
                                    <ul class="mt-2">
                                        <li>Poleras de diferentes materiales y cortes</li>
                                        <li>Casacas corporativas y deportivas</li>
                                        <li>Joggers y pantalones deportivos</li>
                                        <li>Polos empresariales y promocionales</li>
                                        <li>Hoodies y sudaderas personalizadas</li>
                                    </ul>
                                    Todos nuestros productos pueden ser personalizados con bordados, impresión DTF o sublimación.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item faq-item" data-category="products">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    <i class="fas fa-palette me-2 text-primary"></i>
                                    ¿Puedo personalizar los diseños y colores?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    ¡Por supuesto! Ofrecemos personalización completa:
                                    <ul class="mt-2">
                                        <li><strong>Diseños:</strong> Puedes enviar tu propio diseño o trabajamos contigo para crearlo</li>
                                        <li><strong>Colores:</strong> Amplia gama de colores disponibles para las prendas</li>
                                        <li><strong>Técnicas:</strong> Bordado, DTF, sublimación según el diseño</li>
                                        <li><strong>Posiciones:</strong> Pecho, espalda, mangas, según tus necesidades</li>
                                    </ul>
                                    Nuestro equipo de diseño te asesora para obtener el mejor resultado.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item faq-item" data-category="products">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    <i class="fas fa-ruler me-2 text-primary"></i>
                                    ¿Qué tallas están disponibles?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Manejamos un rango completo de tallas:
                                    <ul class="mt-2">
                                        <li><strong>Adultos:</strong> XS, S, M, L, XL, XXL, XXXL</li>
                                        <li><strong>Niños:</strong> 2, 4, 6, 8, 10, 12, 14, 16</li>
                                        <li><strong>Tallas especiales:</strong> Disponibles bajo pedido</li>
                                    </ul>
                                    También ofrecemos servicio de confección a medida para casos especiales.
                                </div>
                            </div>
                        </div>

                        <!-- Pedidos -->
                        <div class="accordion-item faq-item" data-category="orders">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    <i class="fas fa-shopping-cart me-2 text-primary"></i>
                                    ¿Cuál es la cantidad mínima para pedidos?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <ul>
                                        <li><strong>Productos en stock:</strong> Sin cantidad mínima</li>
                                        <li><strong>Productos personalizados:</strong> Mínimo 6 unidades</li>
                                        <li><strong>Pedidos mayoristas:</strong> A partir de 12 unidades (15% descuento)</li>
                                        <li><strong>Pedidos corporativos:</strong> A partir de 50 unidades (descuentos especiales)</li>
                                    </ul>
                                    Para cantidades menores en productos personalizados, consulta disponibilidad.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item faq-item" data-category="orders">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    <i class="fas fa-clock me-2 text-primary"></i>
                                    ¿Cuánto tiempo toma producir mi pedido?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Los tiempos de producción varían según el tipo de producto:
                                    <ul class="mt-2">
                                        <li><strong>Productos en stock:</strong> 1-2 días hábiles</li>
                                        <li><strong>Personalización básica:</strong> 3-5 días hábiles</li>
                                        <li><strong>Bordados complejos:</strong> 5-7 días hábiles</li>
                                        <li><strong>Pedidos grandes (+50 unidades):</strong> 7-10 días hábiles</li>
                                    </ul>
                                    Los tiempos pueden variar en temporadas altas. Te confirmamos el tiempo exacto al procesar tu pedido.
                                </div>
                            </div>
                        </div>

                        <!-- Envíos -->
                        <div class="accordion-item faq-item" data-category="shipping">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                    <i class="fas fa-truck me-2 text-primary"></i>
                                    ¿Hacen envíos a todo el Perú?
                                </button>
                            </h2>
                            <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Sí, realizamos envíos a todo el territorio nacional:
                                    <ul class="mt-2">
                                        <li><strong>Lima Metropolitana:</strong> 1-2 días hábiles</li>
                                        <li><strong>Provincias (Costa):</strong> 3-5 días hábiles</li>
                                        <li><strong>Provincias (Sierra/Selva):</strong> 4-7 días hábiles</li>
                                    </ul>
                                    <strong>Envío gratis</strong> en pedidos mayores a S/ 150 o pedidos mayoristas.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item faq-item" data-category="shipping">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                                    <i class="fas fa-search me-2 text-primary"></i>
                                    ¿Puedo rastrear mi pedido?
                                </button>
                            </h2>
                            <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Sí, ofrecemos seguimiento completo:
                                    <ul class="mt-2">
                                        <li>Número de seguimiento al momento del envío</li>
                                        <li>Actualizaciones por WhatsApp y email</li>
                                        <li>Página de seguimiento en nuestra web</li>
                                        <li>Notificación de entrega</li>
                                    </ul>
                                    Puedes usar nuestra <a href="order-tracking.php">página de seguimiento</a> o contactarnos directamente.
                                </div>
                            </div>
                        </div>

                        <!-- Pagos -->
                        <div class="accordion-item faq-item" data-category="payments">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                                    <i class="fas fa-credit-card me-2 text-primary"></i>
                                    ¿Qué métodos de pago aceptan?
                                </button>
                            </h2>
                            <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Aceptamos múltiples formas de pago:
                                    <ul class="mt-2">
                                        <li><strong>Transferencias bancarias:</strong> BCP, Interbank, BBVA</li>
                                        <li><strong>Depósitos bancarios:</strong> En nuestras cuentas</li>
                                        <li><strong>Pago móvil:</strong> Yape, Plin</li>
                                        <li><strong>Efectivo:</strong> Contra entrega (Lima) o en tienda</li>
                                        <li><strong>Tarjetas:</strong> Visa, MasterCard (próximamente)</li>
                                    </ul>
                                    Para pedidos corporativos ofrecemos facilidades de pago.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item faq-item" data-category="payments">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq9">
                                    <i class="fas fa-percentage me-2 text-primary"></i>
                                    ¿Ofrecen descuentos por volumen?
                                </button>
                            </h2>
                            <div id="faq9" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Sí, ofrecemos descuentos escalonados:
                                    <ul class="mt-2">
                                        <li><strong>12-24 unidades:</strong> 15% descuento</li>
                                        <li><strong>25-49 unidades:</strong> 20% descuento</li>
                                        <li><strong>50-99 unidades:</strong> 25% descuento</li>
                                        <li><strong>100+ unidades:</strong> 30% descuento</li>
                                    </ul>
                                    Para pedidos corporativos y educativos, consulta descuentos especiales adicionales.
                                </div>
                            </div>
                        </div>

                        <!-- General -->
                        <div class="accordion-item faq-item" data-category="all">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq10">
                                    <i class="fas fa-headset me-2 text-primary"></i>
                                    ¿Cómo puedo contactarlos?
                                </button>
                            </h2>
                            <div id="faq10" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Estamos disponibles por múltiples canales:
                                    <ul class="mt-2">
                                        <li><strong>WhatsApp:</strong> +51 999 977 257 (Lun-Sáb 9AM-8PM)</li>
                                        <li><strong>Email:</strong> info@jkgrupotextil.com</li>
                                        <li><strong>Formulario web:</strong> En nuestra página de contacto</li>
                                        <li><strong>Redes sociales:</strong> Facebook, Instagram, TikTok</li>
                                    </ul>
                                    Nuestro equipo responde en menos de 2 horas en horario laboral.
                                </div>
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
                            <h3 class="fw-bold mb-3">¿No encontraste tu respuesta?</h3>
                            <p class="mb-4">Nuestro equipo está listo para ayudarte con cualquier pregunta específica</p>
                            <div class="d-flex justify-content-center gap-3 flex-wrap">
                                <a href="https://wa.me/51999977257?text=Hola,%20tengo%20una%20pregunta%20sobre%20sus%20productos" target="_blank" class="btn btn-success btn-lg">
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
        document.addEventListener('DOMContentLoaded', function() {
            // FAQ Search functionality
            const searchInput = document.getElementById('faqSearch');
            const faqItems = document.querySelectorAll('.faq-item');

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();

                faqItems.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });

            // Category filtering
            const categoryButtons = document.querySelectorAll('[data-category]');

            categoryButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const category = this.dataset.category;

                    // Update active button
                    categoryButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');

                    // Filter FAQ items
                    faqItems.forEach(item => {
                        if (category === 'all' || item.dataset.category === category) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });

                    // Clear search
                    searchInput.value = '';
                });
            });
        });
    </script>
</body>

</html>