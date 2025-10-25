<?php
require_once BASE_PATH . 'config/config.php';
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'classes/Service.php';
require_once BASE_PATH . 'classes/StoreRating.php';

// Conectar a la base de datos
$database = new Database();
$db = $database->getConnection();

// Instanciar clase Service
$service = new Service($db);
$services = $service->getAll();

// Obtener datos de calificaciones de la base de datos
$storeRating = new StoreRating($db);
$rating_data = $storeRating->getAverageRating();

// Obtener tipo de servicio seleccionado
$service_type = $_GET['type'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicios - <?php echo APP_NAME; ?></title>
    <meta name="description" content="Servicios de personalización textil: Impresión DTF, Sublimación y Bordados para empresas y emprendedores.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- CSS modular cargado directamente para mejor rendimiento -->
    <link href="assets/css/variables.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    <link href="assets/css/components.css" rel="stylesheet">
    <link href="assets/css/layout.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <link href="assets/css/utilities.css" rel="stylesheet">
    <link href="assets/css/modal.css" rel="stylesheet">
    <!-- Estilos específicos de Servicios -->
    <link href="assets/css/services.css" rel="stylesheet">
</head>

<body>
    <?php include COMPONENT_PATH . 'header.php'; ?>

    <!-- Breadcrumb y Header de Servicios -->
    <section class="py-4 bg-light">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb bg-transparent p-0 mb-0">
                    <li class="breadcrumb-item">
                        <a href="index.php" class="text-decoration-none text-secondary">
                            <i class="fas fa-home me-1 text-primary"></i>Inicio
                        </a>
                    </li>
                    <li class="breadcrumb-item active text-primary fw-medium" aria-current="page">
                        Servicios
                    </li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Hero Section -->
    <section class="py-5 hero-services text-white">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <h1 class="display-4 fw-bold mb-4">Servicios de Personalización</h1>
                    <p class="lead mb-4">
                        Especializados en confección personalizada de poleras, casacas, joggers y polos para empresas y emprendedores.
                        Ofrecemos servicios de DTF, sublimación y bordados con la más alta calidad.
                    </p>


                    <div class="text-center">
                        <a href="contact.php" class="btn btn-outline-light btn-lg btn-cta-light">
                            <i class="fas fa-comments me-2"></i>Consulta Personalizada
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Servicios Section -->
    <section id="servicios" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-3">Nuestros Servicios</h2>
                <p class="text-muted">Tecnología de vanguardia para crear productos textiles únicos</p>
            </div>

            <div class="row g-4">
                <?php foreach ($services as $serv): ?>
                    <?php if ($serv['is_active']): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="service-card">
                                <div class="service-icon">
                                    <?php
                                    // Determinar icono basado en el nombre del servicio
                                    $service_name = strtolower($serv['name']);
                                    if (strpos($service_name, 'dtf') !== false) {
                                        echo '<i class="fas fa-print"></i>';
                                    } elseif (strpos($service_name, 'sublim') !== false) {
                                        echo '<i class="fas fa-palette"></i>';
                                    } elseif (strpos($service_name, 'bordad') !== false) {
                                        echo '<i class="fas fa-cut"></i>';
                                    } else {
                                        echo '<i class="fas fa-cogs"></i>';
                                    }
                                    ?>
                                </div>
                                <h3 class="service-title"><?php echo htmlspecialchars($serv['name']); ?></h3>
                                <p class="service-description">
                                    <?php echo htmlspecialchars($serv['description']); ?>
                                </p>
                                <button class="btn-quote" onclick="openQuoteModal('<?php echo htmlspecialchars($serv['name']); ?>')">
                                    <i class="fas fa-quote-left me-2"></i>Solicitar Cotización
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Proceso Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-3">Nuestro Proceso de Personalización</h2>
                <p class="text-muted">Desde la idea hasta el producto final</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="process-step">
                        <div class="process-number">1</div>
                        <h5 class="process-title">Consulta</h5>
                        <p class="process-description">Nos contactas con tu idea y requerimientos</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="process-step">
                        <div class="process-number">2</div>
                        <h5 class="process-title">Diseño</h5>
                        <p class="process-description">Creamos o adaptamos tu diseño para el servicio elegido</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="process-step">
                        <div class="process-number">3</div>
                        <h5 class="process-title">Producción</h5>
                        <p class="process-description">Confeccionamos tu producto con la más alta calidad</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="process-step">
                        <div class="process-number">4</div>
                        <h5 class="process-title">Entrega</h5>
                        <p class="process-description">Entregamos tu pedido en tiempo y forma</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal de Cotización -->
    <div class="modal fade" id="quoteModal" tabindex="-1" aria-labelledby="quoteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quoteModalLabel">
                        <i class="fas fa-quote-left me-2"></i>Solicitar Cotización Mayorista
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Cotización Mayorista</h6>
                                <p class="mb-0 small">Complete el formulario con los detalles de su proyecto. Nos especializamos en pedidos al por mayor con descuentos especiales.</p>
                            </div>
                        </div>
                    </div>

                    <form id="quoteForm">
                        <!-- Información del Cliente -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-user"></i>
                                Información del Cliente
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="serviceName" class="form-label">Servicio Solicitado</label>
                                    <input type="text" class="form-control" id="serviceName" readonly style="background-color: #f8f9fa;">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="clientName" class="form-label">Nombre Completo *</label>
                                    <input type="text" class="form-control" id="clientName" required placeholder="Ingrese su nombre completo">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="clientPhone" class="form-label">Teléfono / WhatsApp *</label>
                                    <input type="tel" class="form-control" id="clientPhone" required placeholder="+51 999 999 999">
                                </div>
                            </div>
                        </div>

                        <!-- Tipo de Producto -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-tshirt"></i>
                                Tipo de Producto
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Selecciona el tipo de producto *</label>
                                    <div class="d-flex flex-column gap-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="productType" id="poleras" value="Poleras">
                                            <label class="form-check-label" for="poleras">Poleras</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="productType" id="casacas" value="Casacas">
                                            <label class="form-check-label" for="casacas">Casacas</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="productType" id="joggers" value="Joggers">
                                            <label class="form-check-label" for="joggers">Joggers</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="productType" id="polos" value="Polos">
                                            <label class="form-check-label" for="polos">Polos</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="projectDescription" class="form-label">Descripción del Proyecto *</label>
                                    <textarea class="form-control" id="projectDescription" rows="4" required
                                        placeholder="Describe tu proyecto: ¿Para qué evento o uso? ¿Tienes algún diseño específico en mente?"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Cantidades por Talla -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-calculator"></i>
                                Cantidades por Talla
                            </div>
                            <div class="quantity-grid">
                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <label for="qtyXS" class="form-label">XS</label>
                                        <input type="number" class="form-control form-control-sm" id="qtyXS" min="0" value="0">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="qtyS" class="form-label">S</label>
                                        <input type="number" class="form-control form-control-sm" id="qtyS" min="0" value="0">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="qtyM" class="form-label">M</label>
                                        <input type="number" class="form-control form-control-sm" id="qtyM" min="0" value="0">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="qtyL" class="form-label">L</label>
                                        <input type="number" class="form-control form-control-sm" id="qtyL" min="0" value="0">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="qtyXL" class="form-label">XL</label>
                                        <input type="number" class="form-control form-control-sm" id="qtyXL" min="0" value="0">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="qtyXXL" class="form-label">XXL</label>
                                        <input type="number" class="form-control form-control-sm" id="qtyXXL" min="0" value="0">
                                    </div>
                                </div>
                                <div class="text-center mt-3">
                                    <strong>Total: <span id="totalQuantity">0</span> unidades</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Especificaciones del Diseño -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-palette"></i>
                                Especificaciones del Diseño
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="fabricType" class="form-label">Tipo de Tela</label>
                                    <select class="form-select" id="fabricType">
                                        <option value="">Seleccionar...</option>
                                        <option value="algodon">100% Algodón</option>
                                        <option value="poliester">100% Poliéster</option>
                                        <option value="mezcla">Mezcla Algodón-Poliéster</option>
                                        <option value="dryfit">Dry Fit</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="gramaje" class="form-label">Gramaje</label>
                                    <select class="form-select" id="gramaje">
                                        <option value="">Seleccionar...</option>
                                        <option value="150">150 gr/m²</option>
                                        <option value="180">180 gr/m²</option>
                                        <option value="200">200 gr/m²</option>
                                        <option value="220">220 gr/m²</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="colorCount" class="form-label">Cantidad de Colores en el Diseño</label>
                                    <select class="form-select" id="colorCount">
                                        <option value="">Seleccionar...</option>
                                        <option value="1">1 color</option>
                                        <option value="2">2 colores</option>
                                        <option value="3">3 colores</option>
                                        <option value="4">4+ colores</option>
                                        <option value="full">Full color</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="colorDetails" class="form-label">Detalles de Colores</label>
                                    <input type="text" class="form-control" id="colorDetails"
                                        placeholder="Ej: Negro, blanco, dorado">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="designWidth" class="form-label">Ancho del Diseño (cm)</label>
                                    <input type="number" class="form-control" id="designWidth" min="1" max="40"
                                        placeholder="Ej: 25">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="designHeight" class="form-label">Alto del Diseño (cm)</label>
                                    <input type="number" class="form-control" id="designHeight" min="1" max="40"
                                        placeholder="Ej: 30">
                                </div>
                            </div>

                            <!-- Posiciones del diseño -->
                            <div class="mb-3">
                                <label class="form-label">Posiciones del Diseño *</label>
                                <div class="design-positions">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="posFrontal" value="Frontal">
                                                <label class="form-check-label" for="posFrontal">Frontal</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="posEspalda" value="Espalda">
                                                <label class="form-check-label" for="posEspalda">Espalda</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="posMangaIzq" value="Manga Izquierda">
                                                <label class="form-check-label" for="posMangaIzq">Manga Izquierda</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="posMangaDer" value="Manga Derecha">
                                                <label class="form-check-label" for="posMangaDer">Manga Derecha</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="posPecho" value="Pecho">
                                                <label class="form-check-label" for="posPecho">Pecho (Logo pequeño)</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Presupuesto y Entrega -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-calendar-alt"></i>
                                Presupuesto y Entrega
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="budget" class="form-label">Presupuesto Aproximado</label>
                                    <select class="form-select" id="budget">
                                        <option value="">Seleccionar rango...</option>
                                        <option value="500-1000">S/ 500 - S/ 1,000</option>
                                        <option value="1000-2000">S/ 1,000 - S/ 2,000</option>
                                        <option value="2000-5000">S/ 2,000 - S/ 5,000</option>
                                        <option value="5000+">Más de S/ 5,000</option>
                                        <option value="flexible">Flexible según calidad</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="deadline" class="form-label">Fecha Límite de Entrega</label>
                                    <input type="date" class="form-control" id="deadline">
                                    <small class="text-muted">¿Para cuándo necesitas el pedido?</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="designFiles" class="form-label">Imágenes de Referencia (máximo 5)</label>
                            <div class="file-upload-area">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <input type="file" class="form-control border-0" id="designFiles" multiple accept="image/*" style="background: transparent;">
                                <div class="form-text text-muted mt-2">Sube imágenes de tu diseño, logos o referencias visuales</div>
                            </div>
                            <div id="filePreview" class="mt-3 d-flex flex-wrap gap-2"></div>
                        </div>

                        <div class="mb-4">
                            <label for="additionalNotes" class="form-label">Notas Adicionales</label>
                            <textarea class="form-control" id="additionalNotes" rows="3"
                                placeholder="Cualquier información adicional sobre tu proyecto..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-warning px-4 fw-semibold" onclick="sendWhatsAppQuote()">
                        <i class="fab fa-whatsapp me-2"></i>Enviar Cotización por WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include COMPONENT_PATH . 'footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Reveal animation for service sections and cards
            (function(){
                const selectors = ['.hero-services', '#servicios .service-card', '.process-step', '.modal-content'];
                const els = document.querySelectorAll(selectors.join(','));
                if('IntersectionObserver' in window){
                    const io = new IntersectionObserver((entries)=>{
                        entries.forEach(en=>{ if(en.isIntersecting){ en.target.classList.add('is-visible'); io.unobserve(en.target); } });
                    },{root:null,rootMargin:'0px 0px -8% 0px',threshold:0.12});
                    els.forEach(el=>{ el.classList.add('reveal'); io.observe(el); });
                } else { els.forEach(el=> el.classList.add('is-visible')); }
            })();

            // File preview functionality
            const fileInput = document.getElementById('designFiles');
            const filePreview = document.getElementById('filePreview');

            fileInput.addEventListener('change', function(e) {
                filePreview.innerHTML = '';
                const files = Array.from(e.target.files).slice(0, 5);

                files.forEach(file => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'img-thumbnail me-2 mb-2';
                            img.style.width = '100px';
                            img.style.height = '100px';
                            img.style.objectFit = 'cover';
                            filePreview.appendChild(img);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            });

            // Quantity calculator
            const quantityInputs = ['qtyXS', 'qtyS', 'qtyM', 'qtyL', 'qtyXL', 'qtyXXL'];
            quantityInputs.forEach(id => {
                document.getElementById(id).addEventListener('input', updateTotalQuantity);
            });

            function updateTotalQuantity() {
                const total = quantityInputs.reduce((sum, id) => {
                    const value = parseInt(document.getElementById(id).value) || 0;
                    return sum + value;
                }, 0);
                document.getElementById('totalQuantity').textContent = total;
            }
        });

        // Open quote modal
        function openQuoteModal(serviceName) {
            document.getElementById('serviceName').value = serviceName;
            const modal = new bootstrap.Modal(document.getElementById('quoteModal'));
            modal.show();
        }

        // Send quote to WhatsApp
        function sendWhatsAppQuote() {
            // Validate required fields
            const clientName = document.getElementById('clientName').value.trim();
            const clientPhone = document.getElementById('clientPhone').value.trim();
            const projectDescription = document.getElementById('projectDescription').value.trim();

            if (!clientName || !clientPhone || !projectDescription) {
                alert('Por favor completa todos los campos obligatorios.');
                return;
            }

            // Check if product type is selected
            const productType = document.querySelector('input[name="productType"]:checked');
            if (!productType) {
                alert('Por favor selecciona el tipo de producto.');
                return;
            }

            // Check if at least one quantity is specified
            const quantities = ['qtyXS', 'qtyS', 'qtyM', 'qtyL', 'qtyXL', 'qtyXXL'];
            const totalQty = quantities.reduce((sum, id) => {
                const value = parseInt(document.getElementById(id).value) || 0;
                return sum + value;
            }, 0);

            if (totalQty === 0) {
                alert('Por favor especifica las cantidades por talla.');
                return;
            }

            // Check if at least one design position is selected
            const designPositions = document.querySelectorAll('input[type="checkbox"][id^="pos"]');
            const selectedPositions = Array.from(designPositions).filter(cb => cb.checked).map(cb => cb.value);

            if (selectedPositions.length === 0) {
                alert('Por favor selecciona al menos una posición para el diseño.');
                return;
            }

            // Build WhatsApp message
            const serviceName = document.getElementById('serviceName').value;
            const additionalNotes = document.getElementById('additionalNotes').value;

            // Get product specifications
            const fabricType = document.getElementById('fabricType').value;
            const gramaje = document.getElementById('gramaje').value;
            const colorCount = document.getElementById('colorCount').value;
            const colorDetails = document.getElementById('colorDetails').value;
            const designWidth = document.getElementById('designWidth').value;
            const designHeight = document.getElementById('designHeight').value;
            const budget = document.getElementById('budget').value;
            const deadline = document.getElementById('deadline').value;

            // Build size breakdown
            const sizeBreakdown = [];
            quantities.forEach(id => {
                const qty = parseInt(document.getElementById(id).value) || 0;
                if (qty > 0) {
                    const size = id.replace('qty', '');
                    sizeBreakdown.push(`${size}: ${qty}`);
                }
            });

            let message = `🎨 *COTIZACIÓN MAYORISTA - JK GRUPO TEXTIL*\n\n`;
            message += `👤 *CLIENTE:*\n`;
            message += `• Nombre: ${clientName}\n`;
            message += `• Teléfono: ${clientPhone}\n\n`;

            message += `🛍️ *SERVICIO:* ${serviceName}\n`;
            message += `👕 *PRODUCTO:* ${productType.value}\n`;
            message += `📝 *DESCRIPCIÓN:* ${projectDescription}\n\n`;

            message += `📊 *CANTIDADES:*\n`;
            message += `• ${sizeBreakdown.join(', ')}\n`;
            message += `• *Total: ${totalQty} unidades*\n\n`;

            if (fabricType || gramaje) {
                message += `🧵 *ESPECIFICACIONES:*\n`;
                if (fabricType) message += `• Tela: ${fabricType}\n`;
                if (gramaje) message += `• Gramaje: ${gramaje}\n`;
            }

            if (colorCount || colorDetails) {
                message += `🎨 *DISEÑO:*\n`;
                if (colorCount) message += `• Colores: ${colorCount}\n`;
                if (colorDetails) message += `• Detalles: ${colorDetails}\n`;
                if (designWidth && designHeight) message += `• Tamaño: ${designWidth}x${designHeight} cm\n`;
                message += `• Posiciones: ${selectedPositions.join(', ')}\n`;
            }

            if (budget || deadline) {
                message += `💰 *PRESUPUESTO Y ENTREGA:*\n`;
                if (budget) message += `• Presupuesto: ${budget}\n`;
                if (deadline) message += `• Fecha límite: ${deadline}\n`;
            }

            if (additionalNotes) {
                message += `\n📋 *NOTAS ADICIONALES:*\n${additionalNotes}\n`;
            }

            message += `\n¡Gracias por confiar en JK Jackets! 🙌`;

            // Send to WhatsApp
            const whatsappUrl = `https://wa.me/51999977257?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('quoteModal'));
            modal.hide();
        }
    </script>
</body>

</html>