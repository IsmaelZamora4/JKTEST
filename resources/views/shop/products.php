<?php
require_once BASE_PATH . 'config/config.php';
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'classes/Product.php';
require_once BASE_PATH . 'classes/Category.php';

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);
$category = new Category($db);

// Parámetros de búsqueda y filtros
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$category_filter = isset($_GET['category']) && $_GET['category'] !== '' ? (int)$_GET['category'] : null;
$sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'newest';
$pricing_mode = isset($_GET['pricing']) ? sanitize_input($_GET['pricing']) : 'unidad';

// Filtrar por modalidad: solo mostrar productos de la modalidad seleccionada
$modalidad_filter = null;
if ($pricing_mode === 'mayorista') {
    $stmt = $db->prepare("SELECT id FROM categories WHERE LOWER(name) LIKE '%mayorista%'");
    $stmt->execute();
    $mayorista_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $modalidad_filter = $mayorista_categories;
} elseif ($pricing_mode === 'unidad') {
    $stmt = $db->prepare("SELECT id FROM categories WHERE LOWER(name) LIKE '%unidad%'");
    $stmt->execute();
    $unidad_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $modalidad_filter = $unidad_categories;
}

// Contar productos para paginación
$total_products = $product->countAllWithModalidad($search, $category_filter, true, $modalidad_filter);
// Conteo total ignorando la categoría seleccionada (para 'Todas')
$total_products_all = $product->countAllWithModalidad($search, null, true, $modalidad_filter);
$total_pages = max(1, (int)ceil($total_products / PRODUCTS_PER_PAGE));
if ($page < 1) $page = 1;
if ($page > $total_pages) $page = $total_pages;

// Obtener productos
$products = $product->getAllWithModalidad($page, PRODUCTS_PER_PAGE, $search, $category_filter, true, $sort, $modalidad_filter);

// Categorías
$categories = $category->getAllWithProductCount();

// Categoría actual
$current_category = null;
if ($category_filter) {
    $current_category = $category->getById($category_filter);
}

// Detectar si estamos en la categoría "Unidad - Packs" para mostrar el configurador en vez de listar packs
$is_packs_listing = false;
$pack_product_id_for_builder = null;
if ($current_category && strpos(strtolower($current_category['name']), 'unidad - packs') !== false) {
    $is_packs_listing = true;
    $stmtPack = $db->prepare("
        SELECT id 
        FROM products 
        WHERE is_active = 1 AND category_id = ? 
        ORDER BY id ASC 
        LIMIT 1
    ");
    $stmtPack->execute([$current_category['id']]);
    $pack_product_id_for_builder = (int)$stmtPack->fetchColumn();
}

// Helper assets
function asset_url_with_v($path)
{
    if (!$path) return '';
    $path = str_replace('\\', '/', $path);
    if (preg_match('#^https?://#i', $path)) return $path;
    $fs = __DIR__ . '/' . ltrim($path, '/');
    return file_exists($fs) ? $path . '?v=' . filemtime($fs) : $path;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - <?php echo APP_NAME; ?></title>
    <meta name="description" content="Explora nuestra amplia colección de fragancias y productos de cuidado personal.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/variables.css" rel="stylesheet">
    <script src="assets/js/products-filter.js">

    </script>
    <link href="assets/css/header.css" rel="stylesheet">
    <link href="assets/css/components.css" rel="stylesheet">
    <link href="assets/css/layout.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <link href="assets/css/utilities.css" rel="stylesheet">



</head>

<body>
    <?php include COMPONENT_PATH . 'header.php'; ?>


    <section class="py-4 page-header-minimal">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb bg-transparent p-0 mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="breadcrumb-link"><i class="fas fa-home me-1"></i>Inicio</a></li>
                    <li class="breadcrumb-item active breadcrumb-current" aria-current="page">Productos</li>
                </ol>
            </nav>

            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="page-title-minimal mb-2">
                        <?php if ($current_category): ?>
                            <?php echo htmlspecialchars($current_category['name']); ?>
                        <?php elseif (!empty($search)): ?>
                            Resultados para "<?php echo htmlspecialchars($search); ?>"
                        <?php else: ?>
                            Nuestros Productos
                        <?php endif; ?>
                    </h1>

                    <div class="mb-4">
                        <div class="pricing-mode-selector">
                            <div class="btn-group" role="group" aria-label="Modalidad de venta">
                                <input type="radio" class="btn-check" name="pricing_mode" id="unidad" value="unidad" <?php echo $pricing_mode === 'unidad' ? 'checked' : ''; ?> autocomplete="off">
                                <label class="btn btn-outline-dark" for="unidad"><i class="fas fa-shopping-cart me-2"></i>Por Unidad</label>

                                <input type="radio" class="btn-check" name="pricing_mode" id="mayorista" value="mayorista" <?php echo $pricing_mode === 'mayorista' ? 'checked' : ''; ?> autocomplete="off">
                                <label class="btn btn-outline-dark" for="mayorista"><i class="fas fa-warehouse me-2"></i>Mayorista</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <p class="page-subtitle-minimal mb-2">
                            <?php if ($current_category): ?>
                                Productos de la categoría <?php echo htmlspecialchars($current_category['name']); ?>
                            <?php elseif (!empty($search)): ?>
                                Resultados de búsqueda para "<?php echo htmlspecialchars($search); ?>"
                            <?php else: ?>
                                Descubre nuestra colección de prendas textiles de alta calidad
                            <?php endif; ?>
                        </p>
                        <p class="text-muted small"><?php echo $total_products; ?> productos encontrados</p>
                    </div>
                </div>

                <div class="col-md-4 mt-3 mt-md-0 text-md-end">
                    <form method="GET" action="products.php" class="d-inline-flex align-items-center gap-2">
                        <?php if (!empty($search)): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
                        <?php if ($category_filter !== null): ?><input type="hidden" name="category" value="<?php echo (int)$category_filter; ?>"><?php endif; ?>
                        <input type="hidden" name="pricing" value="<?php echo htmlspecialchars($pricing_mode); ?>">
                        <label for="sort" class="me-1 fw-medium">Ordenar por:</label>
                        <select id="sort" name="sort" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Novedades</option>
                            <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Precio: menor a mayor</option>
                            <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Precio: mayor a menor</option>
                            <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Nombre: A-Z</option>
                            <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Nombre: Z-A</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-lg-3 mb-4">
                    <div class="d-lg-none mb-3">
                        <button class="btn btn-outline-primary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#filtersCollapse" aria-expanded="false" aria-controls="filtersCollapse">
                            <i class="fas fa-filter me-2"></i>Filtros <i class="fas fa-chevron-down ms-auto"></i>
                        </button>
                    </div>

                    <div class="collapse d-lg-block" id="filtersCollapse">
                        <div class="card shadow-sm border-0 filters-compact">
                            <div class="card-header bg-dark text-white py-2">
                                <h6 class="mb-0 d-flex align-items-center"><i class="fas fa-filter me-2"></i>Filtros</h6>
                            </div>
                            <div class="card-body p-0">

                                <?php if ($category_filter !== null || !empty($search)): ?>
                                    <div class="p-2 border-bottom bg-light">
                                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?pricing=<?php echo urlencode($pricing_mode); ?><?php if (!empty($sort)) echo '&sort=' . urlencode($sort); ?>" class="btn btn-outline-danger btn-sm w-100">
                                            <i class="fas fa-times me-1"></i>Limpiar filtros
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <div class="p-2">
                                    <div class="category-filters">

                                        <!-- Todas -->
                                        <div class="filter-item-wrap">
                                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?pricing=<?php echo urlencode($pricing_mode); ?><?php if (!empty($search)) echo '&search=' . urlencode($search);
                                                                                                                                        if (!empty($sort)) echo '&sort=' . urlencode($sort); ?>"
                                                class="filter-item <?php echo !$category_filter ? 'active' : ''; ?>">
                                                <i class="fas fa-th-large me-2"></i>
                                                <span>Todas</span>
                                                <span class="badge bg-secondary ms-auto"><?php echo (int)$total_products_all; ?></span>
                                            </a>
                                        </div>

                                        <?php
                                        $category_icons = [
                                            'mayorista - conjuntos' => 'fas fa-boxes',
                                            'mayorista - joggers'   => 'fas fa-warehouse',
                                            'unidad - conjuntos'    => 'fas fa-tshirt',
                                            'unidad - joggers'      => 'fas fa-running',
                                            'unidad - packs'        => 'fas fa-gift',
                                            'conjuntos'             => 'fas fa-tshirt',
                                            'joggers'               => 'fas fa-running',
                                            'packs'                 => 'fas fa-gift'
                                        ];
                                        ?>

                                        <?php foreach ($categories as $cat): ?>
                                            <?php
                                            $cat_key = strtolower($cat['name']);
                                            $icon = $category_icons[$cat_key] ?? 'fas fa-tag';

                                            $badge_class = 'bg-secondary';
                                            if (strpos($cat_key, 'mayorista') !== false) $badge_class = 'badge-gold';
                                            elseif (strpos($cat_key, 'unidad') !== false) $badge_class = 'bg-primary';

                                            // Mostrar solo según modalidad
                                            $show_category = ($pricing_mode === 'mayorista' && strpos($cat_key, 'mayorista') !== false)
                                                || ($pricing_mode === 'unidad' && strpos($cat_key, 'unidad') !== false);

                                            $collapse_id = 'subfilters-' . (int)$cat['id'];
                                            ?>
                                            <?php if ($show_category): ?>
                                                <div class="filter-item-wrap">
                                                    <div class="filter-item d-flex align-items-center <?php echo $category_filter == $cat['id'] ? 'active' : ''; ?>">
                                                        <!-- Link principal a la categoría -->
                                                        <a class="stretched-link text-reset text-decoration-none d-flex align-items-center flex-grow-1"
                                                            href="<?php echo $_SERVER['PHP_SELF']; ?>?category=<?php echo $cat['id']; ?>&pricing=<?php echo urlencode($pricing_mode); ?><?php if (!empty($search)) echo '&search=' . urlencode($search);
                                                                                                                                                                                        if (!empty($sort)) echo '&sort=' . urlencode($sort); ?>">
                                                            <i class="<?php echo $icon; ?> me-2"></i>
                                                            <span><?php echo str_replace(['mayorista - ', 'unidad - '], '', htmlspecialchars($cat['name'])); ?></span>
                                                        </a>

                                                        <span class="badge <?php echo $badge_class; ?> ms-2"><?php echo (int)$cat['product_count']; ?></span>

                                                        <!-- Chevron abre/cierra -->
                                                        <button type="button"
                                                            class="btn btn-sm btn-link text-muted ms-2 toggle-subfilters"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#<?php echo $collapse_id; ?>"
                                                            aria-expanded="false"
                                                            aria-controls="<?php echo $collapse_id; ?>"
                                                            title="Ver productos de esta categoría"
                                                            onclick="event.preventDefault(); event.stopPropagation();">
                                                            <i class="fas fa-chevron-down"></i>
                                                        </button>
                                                    </div>

                                                    <!-- LISTA DE PRODUCTOS DE LA CATEGORÍA -->
                                                    <div id="<?php echo $collapse_id; ?>" class="collapse subfilters">
                                                        <div class="subfilter-block">
                                                            <?php
                                                            // Traer productos activos de la categoría
                                                            $pstmt = $db->prepare("SELECT id, name FROM products WHERE is_active = 1 AND category_id = ? ORDER BY name ASC");
                                                            $pstmt->execute([$cat['id']]);
                                                            $cat_products = $pstmt->fetchAll(PDO::FETCH_ASSOC);
                                                            ?>

                                                            <?php if (!empty($cat_products)): ?>
                                                                <ul class="list-unstyled mb-0 subproduct-list">
                                                                    <?php foreach ($cat_products as $cp): ?>
                                                                        <li>
                                                                            <a class="subproduct-link" href="product.php?id=<?php echo (int)$cp['id']; ?>">
                                                                                <i class="fas fa-angle-right me-1"></i>
                                                                                <?php echo htmlspecialchars($cp['name']); ?>
                                                                            </a>
                                                                        </li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            <?php else: ?>
                                                                <div class="text-muted small fst-italic">No hay productos en esta categoría.</div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>

                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grid de Productos -->
                <div class="col-lg-9">
                    <?php if ($is_packs_listing): ?>
                        <!-- Vista especial para la categoría Packs: no listar packs, mostrar CTA al configurador -->
                        <div class="card shadow-sm border-0">
                            <div class="card-body d-flex flex-column flex-md-row align-items-center">
                                <div class="flex-grow-1">
                                    <h3 class="mb-1">Personaliza tu Pack</h3>
                                    <p class="text-muted mb-2">Arma un pack con Jean + Polo + Polera. Elige tallas y (si aplica) colores, y añade al carrito.</p>
                                    <ul class="mb-3 text-muted">
                                        <li>Stock y tallas reales por prenda (S, M, L, XL…)</li>
                                        <li>Imágenes por prenda/color si existen</li>
                                        <li>Validaciones de cantidad y disponibilidad</li>
                                    </ul>
                                </div>
                                <div class="ms-md-4 mt-3 mt-md-0">
                                    <?php if ($pack_product_id_for_builder): ?>
                                        <a class="btn btn-primary btn-lg" href="product.php?id=<?php echo $pack_product_id_for_builder; ?>">
                                            <i class="fas fa-sliders-h me-2"></i> Armar Pack ahora
                                        </a>
                                    <?php else: ?>
                                        <div class="alert alert-warning mb-0">
                                            No hay un producto Pack activo para usar el configurador. Crea uno en “Unidad - Packs”.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if (empty($products)): ?>
                            <div class="empty-state">
                                <i class="fas fa-search"></i>
                                <h3>No se encontraron productos</h3>
                                <p>No hay productos que coincidan con tu búsqueda.</p>
                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?pricing=<?php echo urlencode($pricing_mode); ?>" class="btn btn-primary">
                                    <i class="fas fa-arrow-left"></i> Ver Todos los Productos
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($products as $prod): ?>
                                    <div class="col-lg-4 col-md-6 mb-4">
                                        <div class="card product-card h-100 shadow-sm">
                                            <div class="position-relative">
                                                <?php
                                                $display_image = $prod['image_url'];
                                                // Cambio: si hay variantes, solo usar imagen de variante cuando NO haya imagen base
                                                if ($prod['has_variants'] && empty($display_image)) {
                                                    $img_stmt = $db->prepare("
                                                    SELECT pv.variant_image_url
                                                    FROM product_variants pv
                                                    JOIN colors c ON pv.color_id = c.id
                                                    WHERE pv.product_id = ?
                                                      AND pv.variant_image_url IS NOT NULL
                                                      AND pv.stock_quantity > 0
                                                    ORDER BY c.name ASC
                                                    LIMIT 1
                                                ");
                                                    $img_stmt->execute([$prod['id']]);
                                                    $variant_image = $img_stmt->fetchColumn();
                                                    if ($variant_image) $display_image = $variant_image;
                                                }
                                                ?>

                                                <?php if (!empty($display_image)): ?>
                                                    <a href="product.php?id=<?php echo (int)$prod['id']; ?>" class="d-block">
                                                        <img src="<?php echo htmlspecialchars(asset_url_with_v($display_image)); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>" class="card-img-top product-image">

                                                    </a>
                                                <?php else: ?>
                                                    <a href="product.php?id=<?php echo (int)$prod['id']; ?>" class="d-block">
                                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 240px;">
                                                            <i class="fas fa-image fa-3x text-muted"></i>
                                                        </div>
                                                    </a>
                                                <?php endif; ?>

                                                <?php
                                                if ($prod['has_variants']) {
                                                    $stmt = $db->prepare("SELECT SUM(stock_quantity) as total FROM product_variants WHERE product_id = ?");
                                                } else {
                                                    $stmt = $db->prepare("SELECT SUM(stock_quantity) as total FROM product_sizes WHERE product_id = ?");
                                                }
                                                $stmt->execute([$prod['id']]);
                                                $stock_result = $stmt->fetch(PDO::FETCH_ASSOC);
                                                $total_stock = $stock_result ? (int)$stock_result['total'] : 0;
                                                ?>
                                                <?php if ($total_stock == 0): ?>
                                                    <span class="badge bg-danger position-absolute top-0 end-0 m-2">Agotado</span>
                                                <?php elseif ($total_stock <= 5): ?>
                                                    <span class="badge bg-warning position-absolute top-0 end-0 m-2">Últimas unidades</span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="card-body d-flex flex-column">
                                                <h5 class="card-title mb-1">
                                                    <a href="product.php?id=<?php echo (int)$prod['id']; ?>" class="text-dark text-decoration-none">
                                                        <?php echo htmlspecialchars($prod['name']); ?>
                                                    </a>
                                                </h5>

                                                <p class="card-text text-muted flex-grow-1">
                                                    <?php echo htmlspecialchars(substr($prod['description'], 0, 100)); ?>...
                                                </p>

                                                <?php if (!empty($prod['category_name'])): ?>
                                                    <div class="mb-2"><span class="badge bg-info"><?php echo htmlspecialchars($prod['category_name']); ?></span></div>
                                                <?php endif; ?>

                                                <div class="pricing-section mb-3">
                                                    <?php
                                                    $is_mayorista = strpos(strtolower($prod['category_name']), 'mayorista') !== false;
                                                    $is_unidad = strpos(strtolower($prod['category_name']), 'unidad') !== false;
                                                    ?>
                                                    <?php if ($is_mayorista): ?>
                                                        <div class="mayorista-price">
                                                            <span class="badge badge-gold mb-2">MAYORISTA</span>
                                                            <div class="h5 text-gold mb-1">S/ <?php echo number_format($prod['base_price'], 2); ?></div>
                                                            <small class="text-muted">Compra mínima: 6 unidades</small>
                                                        </div>
                                                    <?php elseif ($is_unidad): ?>
                                                        <div class="retail-price">
                                                            <span class="badge bg-primary mb-2">UNIDAD</span>
                                                            <div class="h5 text-primary mb-0">S/ <?php echo number_format($prod['base_price'], 2); ?></div>
                                                            <small class="text-muted">Precio por unidad</small>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="h5 text-primary mb-0">S/ <?php echo number_format($prod['base_price'], 2); ?></div>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if ($prod['has_variants']): ?>
                                                    <div class="product-colors mb-3">
                                                        <small class="text-muted d-block mb-2">Colores disponibles:</small>
                                                        <div class="color-options">
                                                            <?php
                                                            $color_stmt = $db->prepare("
                                                            SELECT DISTINCT c.id, c.name, c.hex_code
                                                            FROM colors c
                                                            JOIN product_variants pv ON c.id = pv.color_id
                                                            WHERE pv.product_id = ? AND pv.stock_quantity > 0
                                                            ORDER BY c.name
                                                        ");
                                                            $color_stmt->execute([$prod['id']]);
                                                            $available_colors = $color_stmt->fetchAll(PDO::FETCH_ASSOC);
                                                            foreach ($available_colors as $color):
                                                                $border_style = (strtolower($color['name']) === 'blanco' || $color['hex_code'] === '#ffffff') ? 'border: 1px solid #ddd;' : '';
                                                            ?>
                                                                <span class="color-dot me-1" style="background-color: <?php echo htmlspecialchars($color['hex_code']); ?>; <?php echo $border_style; ?>" title="<?php echo htmlspecialchars($color['name']); ?>"></span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="mt-auto">
                                                    <?php if ($total_stock <= 0): ?>
                                                        <div class="d-flex gap-2">
                                                            <button class="btn btn-secondary btn-sm flex-fill" disabled>
                                                                <i class="fas fa-times me-1"></i> Agotado
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Paginación de productos" class="mt-5">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $page - 1; ?>&pricing=<?php echo urlencode($pricing_mode); ?><?php if (!empty($search)) echo '&search=' . urlencode($search);
                                                                                                                                                                                            if ($category_filter !== null) echo '&category=' . $category_filter;
                                                                                                                                                                                            if (!empty($sort)) echo '&sort=' . urlencode($sort); ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $i; ?>&pricing=<?php echo urlencode($pricing_mode); ?><?php if (!empty($search)) echo '&search=' . urlencode($search);
                                                                                                                                                                                        if ($category_filter !== null) echo '&category=' . $category_filter;
                                                                                                                                                                                        if (!empty($sort)) echo '&sort=' . urlencode($sort); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $page + 1; ?>&pricing=<?php echo urlencode($pricing_mode); ?><?php if (!empty($search)) echo '&search=' . urlencode($search);
                                                                                                                                                                                            if ($category_filter !== null) echo '&category=' . $category_filter;
                                                                                                                                                                                            if (!empty($sort)) echo '&sort=' . urlencode($sort); ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include COMPONENT_PATH . 'footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const unidadRadio = document.getElementById('unidad');
            const mayoristaRadio = document.getElementById('mayorista');

            function changePricingMode() {
                const currentUrl = new URL(window.location);
                const selectedMode = unidadRadio.checked ? 'unidad' : 'mayorista';
                currentUrl.searchParams.set('pricing', selectedMode);
                window.location.href = currentUrl.toString();
            }

            if (unidadRadio && mayoristaRadio) {
                unidadRadio.addEventListener('change', changePricingMode);
                mayoristaRadio.addEventListener('change', changePricingMode);
            }

            // Abrir automáticamente el acordeón si la categoría está activa
            const activeChevron = document.querySelector('.filter-item.active ~ .toggle-subfilters');
            if (activeChevron) {
                const target = activeChevron.getAttribute('data-bs-target');
                if (target) {
                    const el = document.querySelector(target);
                    if (el && !el.classList.contains('show')) {
                        const collapse = new bootstrap.Collapse(el, {
                            toggle: false
                        });
                        collapse.show();
                    }
                }
            }
        });
    </script>

    <style>
        /* Fondo general */
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }

        /* Modalidad */
        .pricing-mode-selector {
            background: #fff;
            padding: 8px;
            border-radius: 25px;
            box-shadow: 0 4px 15px rgba(212, 175, 55, .1);
            border: 1px solid rgba(212, 175, 55, .2);
        }

        .pricing-mode-selector .btn-outline-dark {
            border-color: var(--accent-color);
            color: var(--accent-color);
            background: transparent;
            font-weight: 600;
            border-radius: 20px;
            padding: 10px 20px;
            transition: .3s;
        }

        .pricing-mode-selector .btn-outline-dark:hover {
            background: linear-gradient(135deg, var(--gold-light), var(--accent-color));
            border-color: var(--accent-color);
            color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(212, 175, 55, .3);
        }

        .pricing-mode-selector .btn-check:checked+.btn-outline-dark {
            background: var(--gradient-gold);
            border-color: var(--accent-color);
            color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(212, 175, 55, .4);
        }

        /* Sidebar filtros */
        .filters-compact {
            position: sticky;
            top: 100px;
            border: 1px solid #e9ecef !important;
        }

        .category-filters {
            display: block;
        }

        .filter-item-wrap {
            margin-bottom: 4px;
        }

        .filter-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            color: #495057;
            text-decoration: none;
            border-bottom: 1px solid #f8f9fa;
            transition: .2s;
            font-size: .9rem;
            background: #fff;
            position: relative;
        }

        .filter-item:hover {
            background: #f8f9fa;
            color: var(--accent-color);
            text-decoration: none;
        }

        .filter-item.active {
            background: var(--accent-color);
            color: #fff;
        }

        .filter-item .badge {
            font-size: .75rem;
        }

        .toggle-subfilters {
            z-index: 2;
        }

        .subfilters {
            padding: 8px 12px 12px 38px;
            background: #fafafa;
            border-left: 3px solid rgba(212, 175, 55, .3);
        }

        .subproduct-list li {
            margin: 0 0 6px 0;
        }

        .subproduct-link {
            display: inline-block;
            font-size: .9rem;
            color: #495057;
            text-decoration: none;
        }

        .subproduct-link:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }

        /* Cards */
        .product-card {
            border: 1px solid rgba(212, 175, 55, .1);
            transition: .3s;
            background: #fff;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(212, 175, 55, .15);
            border-color: rgba(212, 175, 55, .3);
        }

        /* Breadcrumb */
        .breadcrumb-link {
            color: var(--accent-color);
            text-decoration: none;
            transition: .3s;
        }

        .breadcrumb-link:hover {
            color: var(--gold-dark);
            text-decoration: none;
        }

        .breadcrumb-current {
            color: var(--primary-color);
            font-weight: 600;
        }

        /* Paginación */
        .pagination .page-link {
            color: var(--accent-color);
            border-color: rgba(212, 175, 55, .2);
        }

        .pagination .page-link:hover {
            background: var(--gradient-gold);
            border-color: var(--accent-color);
            color: var(--primary-color);
        }

        .pagination .page-item.active .page-link {
            background: var(--gradient-gold);
            border-color: var(--accent-color);
            color: var(--primary-color);
        }

        /* Colores (card) */
        .color-dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 1px solid rgba(0, 0, 0, .1);
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>