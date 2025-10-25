<?php
require_once BASE_PATH . "config/config.php";
require_once BASE_PATH . "config/paths.php";
require_once BASE_PATH . "config/database.php";
require_once BASE_PATH . "config/redbean.php";

$database = new Database();
$db = $database->getConnection();

use RedBeanPHP\R;

$categories = R::findAll('categories', ' is_active = 1 ORDER BY name ASC');

foreach ($categories as $category) {
    $productCount = R::count('products', 'category_id = ? AND is_active = 1', [$category->id]);
    $category->product_count = $productCount;
}

$page = 1;
$limit = 8;
$offset = ($page - 1) * $limit;

$sql = "
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
";

$featured_products = R::getAll($sql, [$limit, $offset]);

$rating_data = (function () {
    $result = R::getRow("SELECT AVG(rating) AS average_rating, COUNT(*) AS total_reviews FROM store_ratings WHERE is_approved = 1");
    return [
        'average_rating' => $result['average_rating'] !== null ? round((float)$result['average_rating'], 1) : 4.7,
        'total_reviews' => (int)$result['total_reviews'] ?: 6,
    ];
})();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Confección Textil Personalizada</title>
    <meta name="description" content="Especialistas en poleras, casacas, joggers y polos personalizados. DTF, sublimación y bordados de alta calidad para empresas y emprendedores.">

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
    <section class="hero-modern bg-dark text-white position-relative overflow-hidden">
        <div class="hero-overlay position-absolute w-100 h-100"></div>
        <div class="container position-relative" style="z-index: 2;">
            <div class="row min-vh-75 align-items-center py-5">
                <div class="col-lg-8 mx-auto text-center">
                    <div class="hero-content">
                        <h1 class="display-2 fw-bold mb-4 hero-title">JK GRUPO TEXTIL</h1>
                        <h2 class="h3 fw-semibold mb-4 hero-subtitle" style="color: black !important;">Confección Textil de Alta Calidad</h2>
                        <p class="lead mb-4 text-light">Especialistas en poleras, casacas, joggers y polos personalizados para empresas y emprendedores. DTF, sublimación y bordados con los más altos estándares de calidad.</p>

                        <div class="hero-buttons d-flex justify-content-center gap-3 flex-wrap">
                            <a href="products.php" class="btn btn-hero-primary btn-lg px-4 py-3 fw-semibold">
                                <i class="fas fa-tshirt me-2"></i> Ver Productos
                            </a>
                            <a href="services.php" class="btn btn-hero-secondary btn-lg px-4 py-3 fw-semibold">
                                <i class="fas fa-palette me-2"></i> Servicios
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Beneficios/Características -->
    <div class="benefits-container">
        <div class="benefits-wrapper">
            <div class="benefit-sticker">
                <i class="fas fa-shipping-fast"></i>
                <span>Delivery Disponible</span>
            </div>

            <div class="benefit-sticker">
                <i class="fas fa-award"></i>
                <span>Alta Calidad</span>
            </div>

            <div class="benefit-sticker">
                <i class="fas fa-clock"></i>
                <span>Entrega Rápida</span>
            </div>

            <div class="benefit-sticker">
                <i class="fab fa-whatsapp"></i>
                <span>Atención Directa</span>
            </div>
        </div>
    </div>
    <section class="category-section py-4" style="margin-bottom: 0 !important;">
        <div class="container category-container">
            <div class="text-center mb-4">
                <h3 class="fw-bold text-dark mb-3">Explorar por Categoría</h3>
                <div class="mx-auto category-divider"></div>
                <p class="text-muted mt-3 mb-0">Encuentra el producto textil perfecto para tu proyecto</p>
            </div>

            <div class="categories-carousel">
                <button class="carousel-arrow prev" onclick="slideCategories && slideCategories('prev')">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <div class="categories-container">
                    <div class="categories-track" id="categoriesTrack">
                        <?php
                        // Iconos para cada categoría
                        $category_icons = [
                            'Poleras' => 'fas fa-tshirt',
                            'Casacas' => 'fas fa-user-tie',
                            'Joggers' => 'fas fa-running',
                            'Polos' => 'fas fa-user'
                        ];

                        if (empty($categories)): ?>
                            <div class="category-item">
                                <p>No hay categorías disponibles</p>
                            </div>
                        <?php else:
                            foreach ($categories as $cat):
                                $icon = isset($category_icons[$cat['name']]) ? $category_icons[$cat['name']] : 'fas fa-tag';
                        ?>
                            <a href="products.php?category=<?php echo $cat['id']; ?>" class="text-decoration-none">
                                <div class="category-item bg-white rounded-3 shadow-sm p-4 text-center h-100 border-0 transition-all" style="transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 25px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.1)'">
                                    <div class="category-icon mb-3">
                                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-dark text-warning" style="width: 60px; height: 60px;">
                                            <i class="<?php echo $icon; ?> fa-2x"></i>
                                        </div>
                                    </div>
                                    <h4 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($cat['name']); ?></h4>
                                    <p class="text-muted small mb-0"><?php echo $cat['product_count']; ?> productos</p>
                                </div>
                            </a>
                        <?php 
                            endforeach; 
                        endif; ?>
                    </div>
                </div>

                <button class="carousel-arrow next" onclick="slideCategories && slideCategories('next')">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </section>
    <!-- Prendas Destacadas -->
    <section class="py-4 bg-dark text-white" style="margin-top: 0 !important;">
        <div class="container">
            <!-- Título en contenedor separado -->
            <div class="text-center mb-4">
                <h2 class="display-6 fw-bold text-warning mb-3">Prendas Destacadas</h2>
                <div class="mx-auto bg-warning" style="width: 70px; height: 3px; border-radius: 2px;"></div>
                <p class="text-light mt-3 mb-0">Los productos más populares de nuestra colección</p>
            </div>

            <!-- Productos en contenedor separado -->
            <div class="section-container">
                <div class="product-carousel position-relative" id="featuredProductsCarousel">
                    <button class="carousel-btn prev featured-prev" onclick="slideFeaturedProducts && slideFeaturedProducts('prev')">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="carousel-btn next featured-next" onclick="slideFeaturedProducts && slideFeaturedProducts('next')">
                        <i class="fas fa-chevron-right"></i>
                    </button>

                    <div class="overflow-hidden">
                        <div class="product-scroll" id="productScroll">
                            <?php foreach ($featured_products as $prod): ?>
                                <?php
                                // Calcular stock total según el tipo de producto
                                if ($prod['has_variants']) {
                                    $stmt = $db->prepare("SELECT SUM(stock_quantity) as total FROM product_variants WHERE product_id = ?");
                                    $stmt->execute([$prod['id']]);
                                    $stock_result = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $total_stock = $stock_result ? (int)$stock_result['total'] : 0;
                                } else {
                                    $stmt = $db->prepare("SELECT SUM(stock_quantity) as total FROM product_sizes WHERE product_id = ?");
                                    $stmt->execute([$prod['id']]);
                                    $stock_result = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $total_stock = $stock_result ? (int)$stock_result['total'] : 0;
                                }

                                // Determinar imagen a mostrar según tipo de producto
                                $display_image = $prod['image_url']; // Imagen por defecto

                                if ($prod['has_variants']) {
                                    // Para productos con variantes, obtener imagen del primer color disponible
                                    $img_stmt = $db->prepare("
                                    SELECT pv.variant_image_url 
                                    FROM product_variants pv 
                                    JOIN colors c ON pv.color_id = c.id 
                                    WHERE pv.product_id = ? AND pv.variant_image_url IS NOT NULL AND pv.stock_quantity > 0
                                    ORDER BY c.name ASC 
                                    LIMIT 1
                                ");
                                    $img_stmt->execute([$prod['id']]);
                                    $variant_image = $img_stmt->fetchColumn();

                                    if ($variant_image) {
                                        $display_image = $variant_image;
                                    }
                                }
                                ?>
                                <div class="product-item">
                                    <a href="product.php?id=<?php echo $prod['id']; ?>" class="text-decoration-none">
                                        <div class="card product-card-modern h-100">
                                            <div class="position-relative">
                                                <?php if (!empty($display_image)): ?>
                                                    <img src="<?php echo htmlspecialchars(asset_url_with_v($display_image)); ?>"
                                                        alt="<?php echo htmlspecialchars($prod['name']); ?>"
                                                        class="product-image-modern">
                                                <?php else: ?>
                                                    <div class="product-image-modern bg-light d-flex align-items-center justify-content-center">
                                                        <i class="fas fa-image fa-2x text-muted"></i>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($total_stock == 0): ?>
                                                    <span class="badge bg-danger position-absolute top-0 end-0 m-2">Agotado</span>
                                                <?php elseif ($total_stock <= 5): ?>
                                                    <span class="badge bg-warning position-absolute top-0 end-0 m-2">Últimas</span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($prod['name']); ?></h6>
                                                <p class="card-text text-muted small">
                                                    <?php echo htmlspecialchars(substr($prod['description'], 0, 60)); ?>...
                                                </p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold text-warning fs-5">S/ <?php echo number_format($prod['base_price'], 2); ?></span>
                                                    <?php if ($total_stock > 0): ?>
                                                        <button class="btn btn-primary btn-sm add-to-cart"
                                                            data-product-id="<?php echo $prod['id']; ?>"
                                                            data-product-name="<?php echo htmlspecialchars($prod['name']); ?>"
                                                            data-product-price="<?php echo $prod['base_price']; ?>">
                                                            <i class="fas fa-cart-plus"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-secondary btn-sm" disabled>
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Nuevos Ingresos -->
    <section class="py-3 bg-white">
        <div class="container">
            <!-- Título en contenedor separado -->
            <div class="text-center mb-3">
                <h2 class="display-6 fw-bold text-dark mb-2">Nuevos Ingresos</h2>
                <div class="mx-auto bg-warning" style="width: 70px; height: 3px; border-radius: 2px;"></div>
                <p class="text-muted mt-2 mb-0">Las últimas novedades en confección personalizada</p>
            </div>

            <!-- Productos nuevos en carrusel -->
            <div class="section-container">
                <div class="product-carousel position-relative" id="newProductsCarousel">
                    <button class="carousel-btn prev new-prev" onclick="slideNewProducts && slideNewProducts('prev')">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="carousel-btn next new-next" onclick="slideNewProducts && slideNewProducts('next')">
                        <i class="fas fa-chevron-right"></i>
                    </button>

                    <div class="overflow-hidden">
                        <div class="product-scroll" id="newProductScroll">
                            <?php
                            // Obtener los productos más recientes
                            $newest_products = array_slice($featured_products, 0, 8);
                            foreach ($newest_products as $prod):
                                // Calcular stock total según el tipo de producto
                                if ($prod['has_variants']) {
                                    $stmt = $db->prepare("SELECT SUM(stock_quantity) as total FROM product_variants WHERE product_id = ?");
                                    $stmt->execute([$prod['id']]);
                                    $stock_result = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $total_stock = $stock_result ? (int)$stock_result['total'] : 0;
                                } else {
                                    $stmt = $db->prepare("SELECT SUM(stock_quantity) as total FROM product_sizes WHERE product_id = ?");
                                    $stmt->execute([$prod['id']]);
                                    $stock_result = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $total_stock = $stock_result ? (int)$stock_result['total'] : 0;
                                }

                                // Determinar imagen a mostrar según tipo de producto
                                $new_display_image = $prod['image_url']; // Imagen por defecto

                                if ($prod['has_variants']) {
                                    // Para productos con variantes, obtener imagen del primer color disponible
                                    $new_img_stmt = $db->prepare("
                                    SELECT pv.variant_image_url 
                                    FROM product_variants pv 
                                    JOIN colors c ON pv.color_id = c.id 
                                    WHERE pv.product_id = ? AND pv.variant_image_url IS NOT NULL AND pv.stock_quantity > 0
                                    ORDER BY c.name ASC 
                                    LIMIT 1
                                ");
                                    $new_img_stmt->execute([$prod['id']]);
                                    $new_variant_image = $new_img_stmt->fetchColumn();

                                    if ($new_variant_image) {
                                        $new_display_image = $new_variant_image;
                                    }
                                }
                            ?>
                                <div class="product-item">
                                    <a href="product.php?id=<?php echo $prod['id']; ?>" class="text-decoration-none">
                                        <div class="card product-card-modern h-100">
                                            <div class="position-relative">
                                                <?php if (!empty($new_display_image)): ?>
                                                    <img src="<?php echo htmlspecialchars(asset_url_with_v($new_display_image)); ?>"
                                                        alt="<?php echo htmlspecialchars($prod['name']); ?>"
                                                        class="product-image-modern">
                                                <?php else: ?>
                                                    <div class="product-image-modern bg-light d-flex align-items-center justify-content-center">
                                                        <i class="fas fa-image fa-2x text-muted"></i>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Badge de nuevo -->
                                                <span class="badge bg-success position-absolute top-0 start-0 m-2">
                                                    <i class="fas fa-star me-1"></i>Nuevo
                                                </span>

                                                <?php if ($total_stock == 0): ?>
                                                    <span class="badge bg-danger position-absolute top-0 end-0 m-2">Agotado</span>
                                                <?php elseif ($total_stock <= 5): ?>
                                                    <span class="badge bg-warning position-absolute top-0 end-0 m-2">Últimas</span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($prod['name']); ?></h6>
                                                <p class="card-text text-muted small">
                                                    <?php echo htmlspecialchars(substr($prod['description'], 0, 60)); ?>...
                                                </p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold text-warning fs-5">S/ <?php echo number_format($prod['base_price'], 2); ?></span>
                                                    <?php if ($total_stock > 0): ?>
                                                        <button class="btn btn-warning btn-sm add-to-cart fw-semibold"
                                                            data-product-id="<?php echo $prod['id']; ?>"
                                                            data-product-name="<?php echo htmlspecialchars($prod['name']); ?>"
                                                            data-product-price="<?php echo $prod['base_price']; ?>">
                                                            <i class="fas fa-cart-plus me-1"></i>Agregar
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-secondary btn-sm" disabled>
                                                            <i class="fas fa-times me-1"></i>Agotado
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mini Testimonials -->
    <section class="mini-testimonials">
        <div class="container">
            <div class="row align-items-center text-center">
                <div class="col-md-4">
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="me-3">
                            <div class="fw-bold" style="font-size: 1.8rem;"><?php echo $rating_data['average_rating']; ?></div>
                            <div style="font-size: 0.9rem;">de 5 estrellas</div>
                        </div>
                        <div class="text-dark" style="font-size: 1.2rem;">
                            <?php
                            $avg_rating = $rating_data['average_rating'];
                            for ($i = 1; $i <= 5; $i++):
                                if ($i <= floor($avg_rating)): ?>
                                    <i class="fas fa-star"></i>
                                <?php elseif ($i <= $avg_rating): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                            <?php endif;
                            endfor; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fw-bold" style="font-size: 1.1rem;">+<?php echo $rating_data['total_reviews']; ?></div>
                    <div style="font-size: 0.9rem;">Clientes Satisfechos</div>
                </div>
                <div class="col-md-4">
                    <div class="fw-bold" style="font-size: 1.1rem;">Calidad Garantizada</div>
                    <div style="font-size: 0.9rem;">Confección Premium</div>
                </div>
            </div>
        </div>
    </section>


    <section class="py-4 bg-dark text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="about-content">
                        <h2 class="display-6 fw-bold mb-4 text-warning">Conoce JK Grupo Textil</h2>
                        <div class="about-divider"></div>
                        <p class="lead mb-4 text-light">
                            Somos especialistas en la confección de poleras, casacas, joggers y polos personalizados para emprendedores y empresas privadas y públicas.
                        </p>
                        <p class="mb-4 text-light">
                            Ofrecemos diseños innovadores en tendencia para adultos y niños, con enfoque en la <strong class="text-warning">calidad</strong>, <strong class="text-warning">innovación</strong> y <strong class="text-warning">puntualidad</strong>. Realizamos ventas por mayor y unidad con el más alto estándar de calidad que satisface las necesidades y expectativas de nuestros clientes.
                        </p>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning text-dark me-3" style="width: 50px; height: 50px;">
                                        <i class="fas fa-award"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 text-white fw-semibold">Alta Calidad</h6>
                                        <small class="text-light">Materiales premium</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning text-dark me-3" style="width: 50px; height: 50px;">
                                        <i class="fas fa-lightbulb"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 text-white fw-semibold">Innovación</h6>
                                        <small class="text-light">Diseños en tendencia</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning text-dark me-3" style="width: 50px; height: 50px;">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 text-white fw-semibold">Puntualidad</h6>
                                        <small class="text-light">Entrega garantizada</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning text-dark me-3" style="width: 50px; height: 50px;">
                                        <i class="fas fa-heart"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 text-white fw-semibold">Pasión</h6>
                                        <small class="text-light">Trabajo bien hecho</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="about.php" class="btn btn-warning btn-lg px-4 py-3 fw-semibold">
                            <i class="fas fa-info-circle me-2"></i>Conócenos Más
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-image text-center">
                        <div class="position-relative d-inline-block">
                            <img src="assets/images/jk-jackets-about.jpg"
                                alt="JK Grupo Textil - Confección de Excelencia"
                                class="img-fluid rounded shadow-lg about-main-image">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Carruseles JS modularizados -->
    <script>
    // Carrusel modular para categorías y productos (SIEMPRE SOLO 1 CARD A LA VEZ EN MÓVIL)
    function setupCarousel(trackSelector, itemSelector, prevBtnSelector, nextBtnSelector, globalFuncName) {
        const track = document.querySelector(trackSelector);
        if (!track) return; // Si no existe el track, salir
        
        const items = track.querySelectorAll(itemSelector);
        if (items.length === 0) return; // Si no hay items, salir
        
        let currentIndex = 0;
        let isTransitioning = false;
        let itemWidth = 0;

        function updateItemWidth() {
            if (items[0]) {
                itemWidth = items[0].offsetWidth;
            }
        }
        
        function goToIndex(index) {
            updateItemWidth();
            track.style.transition = 'transform 0.3s';
            track.style.transform = `translateX(-${index * itemWidth}px)`;
        }
        
        function slide(direction) {
            if (isTransitioning || items.length === 0) return;
            isTransitioning = true;
            if (direction === 'next') {
                currentIndex = (currentIndex + 1) % items.length;
            } else {
                currentIndex = (currentIndex - 1 + items.length) % items.length;
            }
            goToIndex(currentIndex);
            setTimeout(() => isTransitioning = false, 300);
        }
        
        // Botones - usar selectores específicos para evitar conflictos
        const prevBtn = document.querySelector(prevBtnSelector);
        const nextBtn = document.querySelector(nextBtnSelector);
        
        if (prevBtn) prevBtn.onclick = () => slide('prev');
        if (nextBtn) nextBtn.onclick = () => slide('next');
        
        // Swipe touch/tap
        let startX = 0, movedX = 0;
        track.addEventListener('touchstart', e => startX = e.touches[0].clientX, {passive: true});
        track.addEventListener('touchmove', e => movedX = e.touches[0].clientX - startX, {passive: true});
        track.addEventListener('touchend', () => {
            if (Math.abs(movedX) > 40) slide(movedX < 0 ? 'next' : 'prev');
            movedX = 0;
        });
        
        // Resize responsivo
        window.addEventListener('resize', () => goToIndex(currentIndex));
        
        // Inicializar
        updateItemWidth();
        goToIndex(currentIndex);
        
        // Para llamar desde el botón HTML
        window[globalFuncName] = slide;
    }

    // Inicializa los carruseles con selectores únicos
    setupCarousel('#categoriesTrack', '.category-item', '.categories-carousel .carousel-arrow.prev', '.categories-carousel .carousel-arrow.next', 'slideCategories');
    setupCarousel('#productScroll', '.product-item', '#featuredProductsCarousel .featured-prev', '#featuredProductsCarousel .featured-next', 'slideFeaturedProducts');
    setupCarousel('#newProductScroll', '.product-item', '#newProductsCarousel .new-prev', '#newProductsCarousel .new-next', 'slideNewProducts');
    </script>
    <?php include COMPONENT_PATH . '/footer.php'; ?>

</body>
</html>