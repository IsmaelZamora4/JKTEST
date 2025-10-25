<?php
require_once BASE_PATH . 'config/config.php';
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'classes/Category.php';

$database = new Database();
$db = $database->getConnection();
$category = new Category($db);

// Obtener categorías para el menú
$categories = $category->getAllWithProductCount();

// Helper function for cache-busting
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

// Procesar formulario de contacto
$message_sent = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $subject = sanitize_input($_POST['subject'] ?? '');
    $message = sanitize_input($_POST['message'] ?? '');

    // Validaciones básicas
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Por favor, completa todos los campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Por favor, ingresa un email válido.';
    } else {
        // Aquí normalmente enviarías el email o guardarías en base de datos
        // Por ahora solo simulamos el envío exitoso
        $message_sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto - JK Grupo Textil| Prendas Personalizadas</title>
    <meta name="description" content="Contáctanos para personalizar tus prendas. Casacas, hoodies, polos y más con DTF, sublimación y bordados de alta calidad.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link href="assets/css/variables.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    <link href="assets/css/components.css" rel="stylesheet">
    <link href="assets/css/layout.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <link href="assets/css/utilities.css" rel="stylesheet">
    <!-- Contact page specific styles -->
    <link href="assets/css/contact.css" rel="stylesheet">
</head>

<body>
    <?php include COMPONENT_PATH . 'header.php'; ?>

    <!-- Breadcrumb y Header -->
    <section class="py-4 page-header-minimal">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb bg-transparent p-0 mb-0">
                    <li class="breadcrumb-item">
                        <a href="index.php" class="breadcrumb-link">
                            <i class="fas fa-home me-1"></i>Inicio
                        </a>
                    </li>
                    <li class="breadcrumb-item active breadcrumb-current" aria-current="page">
                        Contacto
                    </li>
                </ol>
            </nav>

            <div class="row">
                <div class="col-12">
                    <h1 class="page-title-minimal mb-2">Contactanos</h1>
                    <p class="page-subtitle-minimal mb-0">
                        Estamos aquí para ayudarte con tus proyectos textiles
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <?php if ($message_sent): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>¡Mensaje enviado!</strong> Gracias por contactarnos. Te responderemos pronto.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Formulario de Contacto -->
                <div class="col-lg-7">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-dark text-white">
                            <h4 class="mb-1">
                                <i class="fas fa-envelope me-2 text-warning"></i>Envíanos un Mensaje
                            </h4>
                            <p class="mb-0 text-light">Completa el formulario y nos pondremos en contacto contigo</p>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label fw-semibold">Nombre Completo *</label>
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label fw-semibold">Email *</label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="subject" class="form-label fw-semibold">Asunto *</label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="">Selecciona un asunto</option>
                                        <option value="consulta_producto" <?php echo (($_POST['subject'] ?? '') === 'consulta_producto') ? 'selected' : ''; ?>>Consulta sobre producto</option>
                                        <option value="personalizacion" <?php echo (($_POST['subject'] ?? '') === 'personalizacion') ? 'selected' : ''; ?>>Servicios de personalización</option>
                                        <option value="pedido_mayoreo" <?php echo (($_POST['subject'] ?? '') === 'pedido_mayoreo') ? 'selected' : ''; ?>>Pedido al por mayor</option>
                                        <option value="problema_pedido" <?php echo (($_POST['subject'] ?? '') === 'problema_pedido') ? 'selected' : ''; ?>>Problema con mi pedido</option>
                                        <option value="sugerencia" <?php echo (($_POST['subject'] ?? '') === 'sugerencia') ? 'selected' : ''; ?>>Sugerencia</option>
                                        <option value="otro" <?php echo (($_POST['subject'] ?? '') === 'otro') ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label for="message" class="form-label fw-semibold">Mensaje *</label>
                                    <textarea class="form-control" id="message" name="message" rows="4"
                                        placeholder="Escribe tu mensaje aquí..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                                </div>

                                <button type="submit" id="sendContactBtn" class="btn btn-warning btn-lg px-4">
                                    <i class="fas fa-paper-plane me-2"></i>Enviar Mensaje por WhatsApp
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Información de Contacto -->
                <div class="col-lg-5">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-warning text-dark">
                            <h4 class="mb-1">
                                <i class="fas fa-info-circle me-2"></i>Información de Contacto
                            </h4>
                            <p class="mb-0">Múltiples formas de comunicarte con nosotros</p>
                        </div>
                        <div class="card-body p-4">

                            <div class="contact-info">
                                <div class="d-flex align-items-start mb-4">
                                    <div class="flex-shrink-0">
                                        <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold mb-1">Nuestra Ubicación</h6>
                                        <p class="text-muted mb-0">
                                            Huancayo, Perú<br>
                                            <span class="text-warning fw-semibold">Código Postal: 12001</span>
                                        </p>
                                    </div>
                                </div>

                                <div class="d-flex align-items-start mb-4">
                                    <div class="flex-shrink-0">
                                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="fab fa-whatsapp"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold mb-1">WhatsApp</h6>
                                        <p class="mb-0">
                                            <a href="https://wa.me/51999977257" target="_blank" class="text-success text-decoration-none fw-semibold">
                                                    +51 999 977 257
                                                </a><br>
                                            <small class="text-muted">Respuesta inmediata</small>
                                        </p>
                                    </div>
                                </div>

                                <div class="d-flex align-items-start mb-4">
                                    <div class="flex-shrink-0">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold mb-1">Email Corporativo</h6>
                                        <p class="mb-0">
                                            <a href="mailto:info@jkgrupotextil.com" class="text-primary text-decoration-none fw-semibold">info@jkgrupotextil.com</a><br>
                                            <small class="text-muted">Para consultas formales</small>
                                        </p>
                                    </div>
                                </div>

                                <div class="d-flex align-items-start mb-4">
                                    <div class="flex-shrink-0">
                                        <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold mb-1">Horarios de Atención</h6>
                                        <p class="text-muted mb-0">
                                            <strong>Lunes - Viernes:</strong> 9:00 AM - 6:00 PM<br>
                                            <strong>Sábados:</strong> 9:00 AM - 2:00 PM<br>
                                            <span class="text-warning fw-semibold">Domingos: Cerrado</span>
                                        </p>
                                    </div>
                                </div>

                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="fas fa-share-alt"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold mb-2">Síguenos en Redes</h6>
                                            <div class="social-row d-flex gap-2 flex-wrap">
                                                <a href="https://www.facebook.com/GrupoTextilJK" class="social-btn social-facebook" target="_blank" aria-label="Facebook JK Grupo Textil">
                                                    <i class="fab fa-facebook-f"></i>
                                                </a>
                                                <a href="https://www.instagram.com/jkgrupotextil?igsh=YzNqNTFra2I2ZHJ0" class="social-btn social-instagram" target="_blank" aria-label="Instagram JK Grupo Textil">
                                                    <i class="fab fa-instagram"></i>
                                                </a>
                                                <a href="https://wa.me/51999977257" class="social-btn social-whatsapp" target="_blank" aria-label="WhatsApp JK Grupo Textil">
                                                    <i class="fab fa-whatsapp"></i>
                                                </a>
                                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-6 fw-bold text-dark">Preguntas Frecuentes</h2>
                <div class="mx-auto bg-warning" style="width: 80px; height: 4px; border-radius: 2px;"></div>
                <p class="lead text-muted mt-3">Encuentra respuestas a las consultas más comunes sobre nuestros productos y servicios textiles</p>
            </div>

            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item border-0 shadow-sm mb-3">
                            <h2 class="accordion-header" id="faq1">
                                <button class="accordion-button bg-light fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="true" aria-controls="collapse1">
                                    <i class="fas fa-tshirt text-warning me-3"></i>
                                    ¿Qué tipos de prendas personalizan?
                                </button>
                            </h2>
                            <div id="collapse1" class="accordion-collapse collapse show" aria-labelledby="faq1" data-bs-parent="#faqAccordion">
                                <div class="accordion-body bg-light">
                                    <p class="mb-0">Especializamos en la personalización de <strong>casacas, hoodies, joggers, polos y camisetas</strong> para empresas e individuos. Ofrecemos servicios de DTF, sublimación y bordados con la más alta calidad.</p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border-0 shadow-sm mb-3">
                            <h2 class="accordion-header" id="faq2">
                                <button class="accordion-button collapsed bg-white fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                                    <i class="fas fa-clock text-warning me-3"></i>
                                    ¿Cuáles son los tiempos de producción?
                                </button>
                            </h2>
                            <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="faq2" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p class="mb-2">Los tiempos de producción varían según el tipo de servicio:</p>
                                    <ul class="mb-0">
                                        <li><strong>DTF y Sublimación:</strong> 3-5 días hábiles</li>
                                        <li><strong>Bordados:</strong> 5-7 días hábiles</li>
                                        <li><strong>Pedidos grandes (50+ piezas):</strong> 7-10 días hábiles</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border-0 shadow-sm mb-3">
                            <h2 class="accordion-header" id="faq3">
                                <button class="accordion-button collapsed bg-white fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                                    <i class="fas fa-layer-group text-warning me-3"></i>
                                    ¿Cuál es la cantidad mínima de pedido?
                                </button>
                            </h2>
                            <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="faq3" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p class="mb-2">Manejamos pedidos flexibles según tus necesidades:</p>
                                    <ul class="mb-0">
                                        <li><strong>Pedidos individuales:</strong> Desde 1 pieza</li>
                                        <li><strong>Pedidos corporativos:</strong> Desde 10 piezas</li>
                                        <li><strong>Descuentos por volumen:</strong> A partir de 25 piezas</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border-0 shadow-sm mb-3">
                            <h2 class="accordion-header" id="faq4">
                                <button class="accordion-button collapsed bg-white fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                                    <i class="fas fa-palette text-warning me-3"></i>
                                    ¿Puedo enviar mi propio diseño?
                                </button>
                            </h2>
                            <div id="collapse4" class="accordion-collapse collapse" aria-labelledby="faq4" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p class="mb-2">¡Por supuesto! Aceptamos diseños en diversos formatos:</p>
                                    <ul class="mb-0">
                                        <li><strong>Formatos recomendados:</strong> PNG, AI, EPS, PDF</li>
                                        <li><strong>Resolución mínima:</strong> 300 DPI para mejor calidad</li>
                                        <li><strong>Asesoría gratuita:</strong> Te ayudamos a optimizar tu diseño</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border-0 shadow-sm mb-3">
                            <h2 class="accordion-header" id="faq5">
                                <button class="accordion-button collapsed bg-white fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse5" aria-expanded="false" aria-controls="collapse5">
                                    <i class="fas fa-credit-card text-warning me-3"></i>
                                    ¿Qué métodos de pago aceptan?
                                </button>
                            </h2>
                            <div id="collapse5" class="accordion-collapse collapse" aria-labelledby="faq5" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p class="mb-2">Ofrecemos múltiples opciones de pago para tu comodidad:</p>
                                    <ul class="mb-0">
                                        <li><strong>Transferencia bancaria</strong></li>
                                        <li><strong>Yape y Plin</strong></li>
                                        <li><strong>Efectivo</strong> (contra entrega en Lima)</li>
                                        <li><strong>Depósito bancario</strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border-0 shadow-sm">
                            <h2 class="accordion-header" id="faq6">
                                <button class="accordion-button collapsed bg-white fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                    <i class="fas fa-shipping-fast text-warning me-3"></i>
                                    ¿Hacen envíos a todo el Perú?
                                </button>
                            </h2>
                            <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="faq6" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p class="mb-2">Sí, realizamos envíos a nivel nacional:</p>
                                    <ul class="mb-0">
                                        <li><strong>Lima Metropolitana:</strong> Entrega en 24-48 horas</li>
                                        <li><strong>Provincias:</strong> 3-5 días hábiles vía courier</li>
                                        <li><strong>Envío gratuito</strong> en pedidos mayores a S/200</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="py-5 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-6 fw-bold mb-3">¿Listo para crear algo increíble?</h2>
                    <div class="mx-auto bg-warning mb-4" style="width: 80px; height: 4px; border-radius: 2px;"></div>
                    <p class="lead mb-4">
                        En JK Grupo Textil transformamos tus ideas en prendas únicas. Desde diseños personalizados hasta pedidos al por mayor, estamos aquí para hacer realidad tu visión textil.
                    </p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="https://wa.me/51999977257" class="btn btn-warning btn-lg px-4" target="_blank">
                            <i class="fab fa-whatsapp me-2"></i>Chatear por WhatsApp
                        </a>
                        <a href="products.php" class="btn btn-outline-warning btn-lg px-4">
                            <i class="fas fa-tshirt me-2"></i>Ver Productos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include COMPONENT_PATH . 'footer.php'; ?>

    <!-- Contact page scripts: reveal + WhatsApp submission -->
    <script>
        (function(){
            // Reveal animation for key sections
            const revealSelectors = ['.page-header-minimal', '.card', '#servicios', '.accordion-item', '.card.bg-dark', '.card.bg-warning'];
            const els = document.querySelectorAll(revealSelectors.join(','));
            if('IntersectionObserver' in window){
                const obs = new IntersectionObserver((entries)=>{
                    entries.forEach(e=>{ if(e.isIntersecting){ e.target.classList.add('is-visible'); obs.unobserve(e.target); } });
                },{root:null,rootMargin:'0px 0px -8% 0px',threshold:0.08});
                els.forEach(el=>{ el.classList.add('reveal'); obs.observe(el); });
            } else { els.forEach(el=> el.classList.add('is-visible')); }

            // Intercept contact form submit and open WhatsApp with prefilled message
            const contactForm = document.querySelector('form[method="POST"]');
            if(contactForm){
                contactForm.addEventListener('submit', function(ev){
                    ev.preventDefault(); // prevent default POST to open WhatsApp first

                    const name = (document.getElementById('name') || {}).value || '';
                    const email = (document.getElementById('email') || {}).value || '';
                    const subjectEl = document.getElementById('subject');
                    const subject = subjectEl ? subjectEl.options[subjectEl.selectedIndex].text : '';
                    const message = (document.getElementById('message') || {}).value || '';

                    // Basic client validation
                    if(!name || !email || !message){
                        alert('Por favor completa los campos obligatorios: Nombre, Email y Mensaje.');
                        return;
                    }

                    // Build professional WhatsApp message
                    let waMsg = '📩 *CONTACTO - JK GRUPO TEXTIL*\n\n';
                    waMsg += '*Cliente:* ' + name + '\n';
                    waMsg += '*Email:* ' + email + '\n';
                    if(subject) waMsg += '*Asunto:* ' + subject + '\n';
                    waMsg += '\n*Mensaje:*\n' + message + '\n\n';
                    waMsg += 'Enviado desde la web.';

                    const phone = '51999977257'; // WhatsApp number (country code + number, no + or spaces)
                    const url = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(waMsg);

                    window.open(url, '_blank');

                    // Optionally, submit form to server in background (uncomment if desired)
                    // contactForm.submit();
                });
            }
        })();
    </script>

</body>

</html>