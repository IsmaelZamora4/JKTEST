<?php
require_once BASE_PATH . 'config/config.php';
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'classes/Product.php';
require_once BASE_PATH . 'classes/Category.php';
require_once BASE_PATH . 'classes/Size.php';
require_once BASE_PATH . 'classes/ProductVariant.php';

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);
$category = new Category($db);
$size = new Size($db);
$productVariant = new ProductVariant($db);

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Producto actual
$current_product = $product->getById($product_id);
if (!$current_product) {
  header('Location: products.php?error=product_not_found');
  exit();
}

// === util ===
function asset_url_with_v($path)
{
  if (!$path) return '';
  $path = str_replace('\\', '/', $path);
  if (preg_match('#^https?://#i', $path)) return $path;
  $fs = __DIR__ . '/' . ltrim($path, '/');
  return file_exists($fs) ? $path . '?v=' . filemtime($fs) : $path;
}

// ====== Config Pack (ajustable) ======
$PACK_MIN_QTY_PER_ITEM = 1;   // mínimo por ítem del pack
$PACK_STEP_QTY_PER_ITEM = 1;  // incremento por clic
$PACK_HIDE_OOS_SIZES    = true; // ocultar tallas sin stock (true) o mostrarlas deshabilitadas (false)
$PACK_REQUIRE_COLOR_WHEN_AVAILABLE = true; // exigir color si tiene variantes

// ¿Es pack?
$cat_name_lc = strtolower($current_product['category_name'] ?? '');
$is_pack = (strpos($cat_name_lc, 'unidad - packs') !== false);

// Nombre a mostrar (para packs reemplazamos el nombre del producto de la BD)
$display_name = $is_pack ? 'Pack Personalizable: Polera + Polo + Jean' : ($current_product['name'] ?? 'Producto');

// -------- Datos de producto normal (no pack) ----------
$available_colors = [];
$product_variants = [];
$available_sizes  = [];
$initial_image = $current_product['image_url'];
$gallery_images = [];

if (!$is_pack) {
  if ($current_product['has_variants']) {
    // Cargar colores y variantes, pero NO sobrescribir la imagen principal
    $available_colors = $productVariant->getAvailableColors($product_id);
    $product_variants = $productVariant->getVariantsByProduct($product_id);
  } else {
    $available_sizes = $size->getByProduct($product_id);
  }

  // 1) Siempre incluir la imagen base primero si existe
  if (!empty($current_product['image_url'])) {
    $gallery_images[] = $current_product['image_url'];
  } elseif ($current_product['has_variants']) {
    // Fallback si no hay imagen base: usar imagen de la primera variante con stock
    $stmt = $db->prepare("
      SELECT variant_image_url
      FROM product_variants
      WHERE product_id=? AND variant_image_url IS NOT NULL AND variant_image_url<>'' 
        AND stock_quantity>0
      ORDER BY color_id ASC
      LIMIT 1
    ");
    $stmt->execute([$product_id]);
    $fallback_img = $stmt->fetchColumn();
    if ($fallback_img) $gallery_images[] = $fallback_img;
  }

  // 2) Agregar imágenes de variantes sin duplicar
  if ($current_product['has_variants']) {
    $stmt = $db->prepare("
      SELECT DISTINCT variant_image_url
      FROM product_variants
      WHERE product_id=? AND variant_image_url IS NOT NULL AND variant_image_url<>'' 
      ORDER BY color_id ASC
    ");
    $stmt->execute([$product_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $img) {
      if ($img && !in_array($img, $gallery_images, true)) $gallery_images[] = $img;
    }
  }

  // 3) Asegurar que la imagen principal a mostrar sea la primera de la galería
  if (!empty($gallery_images)) {
    $initial_image = $gallery_images[0];
  }
}

// Para el zoom: lista final de imágenes (asegurar URLs versionadas)
$gallery_images_final = [];
if (!empty($gallery_images)) {
  foreach ($gallery_images as $gi) {
    $gallery_images_final[] = asset_url_with_v($gi);
  }
} elseif (!empty($current_product['image_url'])) {
  $gallery_images_final[] = asset_url_with_v($current_product['image_url']);
}

// Relacionados
$related_products = [];
if ($current_product['category_id']) {
  $related_products = $product->getRelated($current_product['category_id'], $product_id, 4);
}

// Textos acordeón
$detalle_texto = trim($current_product['description'] ?? '') ?: 'Descripción no disponible por el momento.';
$guia_tallas_html = '
<table class="table table-sm align-middle mb-0">
  <thead>
    <tr><th>Talla</th><th>Pecho (cm)</th><th>Cintura (cm)</th><th>Cadera (cm)</th></tr>
  </thead>
  <tbody>
    <tr><td>S</td><td>88–94</td><td>76–82</td><td>88–94</td></tr>
    <tr><td>M</td><td>95–100</td><td>83–88</td><td>95–100</td></tr>
    <tr><td>L</td><td>101–106</td><td>89–94</td><td>101–106</td></tr>
    <tr><td>XL</td><td>107–112</td><td>95–100</td><td>107–112</td></tr>
  </tbody>
</table>
<p class="text-muted small mt-2 mb-0">Medidas referenciales. Puede variar ±2 cm según el modelo.</p>';
$envio_cambios_texto = 'Realizamos envíos a nivel nacional. Las solicitudes de cambios o devoluciones pueden efectuarse dentro de los 7 días posteriores a la recepción del pedido.';

// ====== Mínimos para mayorista ======
$MAYORISTA_MIN_QTY = 6;
$is_mayorista = strpos(strtolower($current_product['category_name'] ?? ''), 'mayorista') !== false;
$min_required = $is_mayorista ? $MAYORISTA_MIN_QTY : 1;
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($display_name); ?> - <?php echo APP_NAME; ?></title>
  <meta name="description" content="<?php echo htmlspecialchars(substr($current_product['description'], 0, 160)); ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="assets/css/variables.css" rel="stylesheet">
  <link href="assets/css/header.css" rel="stylesheet">
  <link href="assets/css/components.css" rel="stylesheet">
  <link href="assets/css/layout.css" rel="stylesheet">
  <link href="assets/css/responsive.css" rel="stylesheet">
  <link href="assets/css/utilities.css" rel="stylesheet">
  <style>
    /* Modern Pack UI */
    .pack-grid {
      background: #fff;
      border: 0;
      border-radius: 18px;
      padding: 16px;
      box-shadow: 0 8px 28px rgba(0, 0, 0, .06)
    }

    .pack-slot {
      position: relative;
      background: #fafafa;
      border: 1px solid #eee;
      border-radius: 16px;
      padding: 14px;
      min-height: 340px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden
    }

    .pack-slot img {
      max-height: 300px;
      object-fit: contain;
      transition: transform .2s ease
    }

    .pack-slot:hover img {
      transform: scale(1.02)
    }

    .pack-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 0;
      background: #111;
      color: #fff;
      opacity: .85;
      display: flex;
      align-items: center;
      justify-content: center
    }

    .pack-arrow.left {
      left: 10px
    }

    .pack-arrow.right {
      right: 10px
    }

    .pack-arrow:hover {
      opacity: 1
    }

    @media (max-width:768px) {
      .pack-slot {
        min-height: 260px
      }

      .pack-slot img {
        max-height: 220px
      }
    }

    .pack-info {
      text-align: center
    }

    .pack-info .fw-semibold {
      color: #111
    }

    .pack-colors .color-dot {
      display: inline-block;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      border: 1px solid rgba(0, 0, 0, .15);
      cursor: pointer;
      margin: 0 6px 6px 0;
      position: relative
    }

    .pack-colors .color-dot.active {
      outline: 2px solid #7b3c34;
      outline-offset: 2px
    }

    .pack-sizes .btn {
      margin: 4px 6px 0 0;
      border-radius: 10px
    }

    .pack-qty {
      display: flex;
      gap: 8px;
      align-items: center;
      justify-content: center
    }

    .pack-qty .qty-btn {
      width: 36px;
      height: 36px;
      border: 1px solid #ced4da;
      background: #fff;
      border-radius: 8px;
      line-height: 1;
      font-weight: 700
    }

    .pack-qty input[type="number"] {
      width: 80px;
      text-align: center;
      border-radius: 8px
    }

    .price-summary-pack {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #fff;
      border: 1px dashed rgba(0, 0, 0, .1);
      border-radius: 14px;
      padding: 16px
    }

    .btn-pack {
      background: #3b2321;
      color: #fff;
      border: 0;
      border-radius: 10px;
      padding: 12px 18px
    }

    .btn-pack:hover {
      background: #2d1a18
    }

    /* Galería estándar */
    .product-media-grid {
      display: grid;
      grid-template-columns: 22% 1fr;
      gap: 16px;
      align-items: start
    }

    .product-thumbs {
      display: flex;
      flex-direction: column;
      gap: 10px;
      max-height: 80vh;
      overflow: auto;
      padding-right: 6px
    }

    .product-thumbs .thumb-btn {
      border: 0;
      background: transparent;
      padding: 0;
      cursor: pointer;
      border-radius: 10px;
      outline: 0
    }

    .product-thumbs .thumb-btn img {
      width: 100%;
      display: block;
      border-radius: 10px;
      border: 2px solid transparent
    }

    .product-thumbs .thumb-btn.active img,
    .product-thumbs .thumb-btn:hover img {
      border-color: #d4af37
    }

    .product-main img {
      width: 100%;
      border-radius: 14px
    }

    @media (max-width:768px) {
      .product-media-grid {
        grid-template-columns: 1fr
      }

      .product-thumbs {
        flex-direction: row;
        overflow-x: auto;
        max-height: none
      }

      .product-thumbs .thumb-btn {
        min-width: 80px
      }
    }

    .jk-accordion .accordion-item {
      border: 1px solid rgba(212, 175, 55, .18);
      border-radius: 12px;
      margin-bottom: 10px;
      overflow: hidden;
      box-shadow: 0 6px 16px rgba(212, 175, 55, .08)
    }

    .jk-accordion .accordion-button {
      font-weight: 600
    }

    .jk-accordion .accordion-button:not(.collapsed) {
      color: var(--primary-color);
      background: var(--gradient-gold);
      box-shadow: inset 0 -1px 0 rgba(0, 0, 0, .05)
    }

    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 15px 20px;
      background: linear-gradient(135deg, #28a745, #20c997);
      color: #fff;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
      z-index: 9999;
      transform: translateX(400px);
      opacity: 0;
      transition: all .3s ease;
      font-weight: 500
    }

    .notification.show {
      transform: translateX(0);
      opacity: 1
    }

    /* Etiquetas y detalles de la derecha cuando es pack */
    .right-pack-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 10px
    }

    .right-pack-title {
      font-weight: 800;
      font-size: 1.75rem;
      margin: 0
    }

    .right-pack-sub {
      color: #6c757d
    }

    /* --- Edge blur previews like Remsides (para pack) --- */
    .edge-blur {
      position: absolute;
      top: 0;
      bottom: 0;
      width: 32%;
      pointer-events: none;
      background-repeat: no-repeat;
      background-position: center;
      background-size: contain;
      filter: blur(7px);
      transform: scale(1.05);
      opacity: .35;
    }

    .edge-blur.left {
      left: -6%;
      -webkit-mask-image: linear-gradient(to right, rgba(0, 0, 0, 1), rgba(0, 0, 0, 0));
      mask-image: linear-gradient(to right, rgba(0, 0, 0, 1), rgba(0, 0, 0, 0));
    }

    .edge-blur.right {
      right: -6%;
      -webkit-mask-image: linear-gradient(to left, rgba(0, 0, 0, 1), rgba(0, 0, 0, 0));
      mask-image: linear-gradient(to left, rgba(0, 0, 0, 1), rgba(0, 0, 0, 0));
    }

    @media (max-width:768px) {
      .edge-blur {
        display: none;
      }
    }

    .pack-grid {
      padding: 12px 0;
      background: transparent;
      box-shadow: none
    }

    .pack-slot {
      border: 0;
      background: #fff;
      min-height: 540px;
      border-radius: 16px
    }

    .pack-slot img {
      max-height: 460px
    }

    .edge-blur {
      width: 38%;
      filter: blur(10px);
      opacity: .45
    }

    .edge-blur.left {
      left: -8%
    }

    .edge-blur.right {
      right: -8%
    }

    .pack-arrow {
      width: 44px;
      height: 44px
    }

    @media (max-width:992px) {
      .pack-slot {
        min-height: 420px
      }

      .pack-slot img {
        max-height: 360px
      }
    }

    /* Right panel compact */
    .product-detail-info .right-pack-header h1 {
      font-size: 32px
    }

    .product-detail-info .jk-accordion .accordion-item {
      margin-bottom: 10px
    }

    /* ====== Zoom PRO modal (Canvas + Lens) ====== */
    .zoom-modal .modal-content {
      background: radial-gradient(60% 60% at 50% 50%, rgba(20, 20, 20, .95), rgba(10, 10, 10, .98));
      backdrop-filter: blur(6px);
    }

    .zoom-stage {
      position: relative;
      width: 100%;
      height: 100vh;
      overflow: hidden;
      cursor: grab;
      touch-action: none;
      background: #0f0f0f;
    }

    .zoom-stage.bg-lightish {
      background: #f7f7f7;
    }

    .zoom-stage.bg-neutral {
      background: #e6e6e6;
    }

    .zoom-stage.bg-darkish {
      background: #0f0f0f;
    }

    .zoom-stage.grabbing {
      cursor: grabbing
    }

    #zoomImg {
      position: absolute;
      top: 0;
      left: 0;
      will-change: transform;
      transform-origin: 0 0;
      user-select: none;
      -webkit-user-drag: none;
      pointer-events: none;
      transition: transform .06s ease-out, filter .2s ease;
      image-rendering: auto;
    }

    .zoom-toolbar {
      position: absolute;
      right: 16px;
      top: 12px;
      z-index: 6;
      display: flex;
      gap: 8px;
      align-items: center;
      flex-wrap: wrap;
      max-width: 60vw;
    }

    .zoom-meter {
      background: rgba(255, 255, 255, .07);
      color: #eee;
      border: 1px solid rgba(255, 255, 255, .15);
      padding: 6px 10px;
      border-radius: 10px;
      font-weight: 700;
      backdrop-filter: blur(6px);
    }

    .zoom-btn {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      border: 1px solid rgba(212, 175, 55, .45);
      background: linear-gradient(135deg, #f6d365, #d4af37);
      color: #111;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 6px 18px rgba(212, 175, 55, .35);
    }

    .zoom-btn:hover {
      filter: brightness(.96)
    }

    .zoom-btn.toggle.active {
      outline: 2px solid #fff;
      outline-offset: 2px
    }

    /* Lens */
    .zoom-lens {
      position: absolute;
      width: 200px;
      height: 200px;
      border-radius: 50%;
      box-shadow: 0 10px 30px rgba(0, 0, 0, .4), inset 0 0 0 1px rgba(255, 255, 255, .25);
      border: 1px solid rgba(212, 175, 55, .45);
      background-repeat: no-repeat;
      background-position: center;
      background-size: 200%;
      display: none;
      z-index: 5;
      pointer-events: none;
    }

    .zoom-lens.show {
      display: block
    }

    /* Grid overlay */
    .zoom-grid {
      position: absolute;
      inset: 0;
      pointer-events: none;
      z-index: 4;
      display: none;
      background-image:
        linear-gradient(to right, rgba(255, 255, 255, .05) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(255, 255, 255, .05) 1px, transparent 1px);
      background-size: 40px 40px;
    }

    .zoom-grid.show {
      display: block
    }

    /* Prev/Next dentro del modal */
    .zoom-nav {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 48px;
      height: 48px;
      border-radius: 50%;
      border: 1px solid rgba(255, 255, 255, .35);
      background: rgba(255, 255, 255, .08);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 7;
    }

    .zoom-nav:hover {
      background: rgba(255, 255, 255, .18)
    }

    .zoom-nav.prev {
      left: 14px
    }

    .zoom-nav.next {
      right: 14px
    }

    /* Hint */
    .zoom-hint {
      position: absolute;
      left: 16px;
      bottom: 16px;
      color: #ddd;
      opacity: .95;
      background: rgba(0, 0, 0, .35);
      border: 1px solid rgba(255, 255, 255, .15);
      padding: 8px 12px;
      border-radius: 10px;
      font-size: .9rem;
      z-index: 6;
      backdrop-filter: blur(4px);
    }

    /* Texture enhancer */
    .zoom-texture-boost {
      filter: contrast(1.08) saturate(1.05) brightness(1.02);
    }
  </style>
</head>

<body>
  <?php include COMPONENT_PATH . 'header.php'; ?>

  <section class="py-4 page-header-minimal">
    <div class="container">
      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0 mb-0">
          <li class="breadcrumb-item"><a href="index.php" class="breadcrumb-link"><i class="fas fa-home me-1"></i>Inicio</a></li>
          <li class="breadcrumb-item"><a href="products.php" class="breadcrumb-link">Productos</a></li>
          <?php if (!empty($current_product['category_name'])): ?>
            <li class="breadcrumb-item"><a href="products.php?category=<?php echo $current_product['category_id']; ?>" class="breadcrumb-link"><?php echo htmlspecialchars($current_product['category_name']); ?></a></li>
          <?php endif; ?>
          <li class="breadcrumb-item active breadcrumb-current" aria-current="page"><?php echo htmlspecialchars($display_name); ?></li>
        </ol>
      </nav>
    </div>
  </section>

  <section class="py-5">
    <div class="container">
      <div class="row">

        <!-- ===== Columna izquierda ===== -->
        <div class="col-lg-8 mb-4">

          <?php if ($is_pack): ?>
            <?php
            // === PACK: construir catálogo con tallas y colores por producto ===
            function add_sizes_to_rows_pack_mixed(PDO $db, array $rows)
            {
              if (!$rows) return [];

              $q_sizes_trad = $db->prepare("
              SELECT ps.id AS id_for_cart, s.name, COALESCE(ps.stock_quantity,0) AS stock_quantity
              FROM product_sizes ps
              JOIN sizes s ON s.id = ps.size_id
              WHERE ps.product_id = ?
              ORDER BY s.name ASC
            ");

              $q_sizes_variants_all = $db->prepare("
              SELECT s.id AS id_for_cart, s.name, COALESCE(SUM(pv.stock_quantity),0) AS stock_quantity
              FROM product_variants pv
              JOIN sizes s ON s.id = pv.size_id
              WHERE pv.product_id = ?
              GROUP BY s.id, s.name
              ORDER BY s.name ASC
            ");

              $q_colors = $db->prepare("
              SELECT DISTINCT c.id, c.name, c.hex_code
              FROM colors c
              JOIN product_variants pv ON pv.color_id = c.id
              WHERE pv.product_id = ?
              ORDER BY c.name ASC
            ");

              $q_color_img = $db->prepare("
              SELECT pv.variant_image_url
              FROM product_variants pv
              WHERE pv.product_id = ? AND pv.color_id = ?
                    AND pv.variant_image_url IS NOT NULL AND pv.variant_image_url <> ''
              ORDER BY pv.stock_quantity DESC
              LIMIT 1
            ");

              $q_sizes_by_color = $db->prepare("
              SELECT s.id AS id_for_cart, s.name, COALESCE(SUM(pv.stock_quantity),0) AS stock_quantity
              FROM product_variants pv
              JOIN sizes s ON s.id = pv.size_id
              WHERE pv.product_id = ? AND pv.color_id = ?
              GROUP BY s.id, s.name
              ORDER BY s.name ASC
            ");

              foreach ($rows as &$r) {
                $r['image_disp'] = asset_url_with_v($r['image_url']);
                $r['sizes'] = [];
                $r['colors'] = [];

                if ((int)$r['has_variants'] === 1) {
                  // Tallas globales (todas las tallas del producto)
                  $q_sizes_variants_all->execute([(int)$r['id']]);
                  $r['sizes'] = array_map(function ($row) {
                    return [
                      'id_for_cart'    => (int)$row['id_for_cart'], // sizes.id
                      'id'             => (int)$row['id_for_cart'],
                      'name'           => $row['name'],
                      'stock_quantity' => (int)$row['stock_quantity'],
                      'source'         => 'variants'
                    ];
                  }, $q_sizes_variants_all->fetchAll(PDO::FETCH_ASSOC));

                  // Colores y tallas por color
                  $q_colors->execute([(int)$r['id']]);
                  $colors = $q_colors->fetchAll(PDO::FETCH_ASSOC);
                  foreach ($colors as $c) {
                    $color_id = (int)$c['id'];
                    $q_color_img->execute([(int)$r['id'], $color_id]);
                    $img = $q_color_img->fetchColumn();

                    $q_sizes_by_color->execute([(int)$r['id'], $color_id]);
                    $sz = $q_sizes_by_color->fetchAll(PDO::FETCH_ASSOC);
                    $sizes_by_color = array_map(function ($row) {
                      return [
                        'id_for_cart'    => (int)$row['id_for_cart'], // sizes.id
                        'id'             => (int)$row['id_for_cart'],
                        'name'           => $row['name'],
                        'stock_quantity' => (int)$row['stock_quantity'],
                        'source'         => 'variants'
                      ];
                    }, $sz);

                    $r['colors'][] = [
                      'id'        => $color_id,
                      'name'      => $c['name'],
                      'hex_code'  => $c['hex_code'],
                      'image_url' => $img ? asset_url_with_v($img) : null,
                      'sizes'     => $sizes_by_color
                    ];
                  }
                } else {
                  // Modo tradicional (product_sizes)
                  $q_sizes_trad->execute([(int)$r['id']]);
                  $r['sizes'] = array_map(function ($row) {
                    return [
                      'id_for_cart'    => (int)$row['id_for_cart'], // product_sizes.id
                      'id'             => (int)$row['id_for_cart'],
                      'name'           => $row['name'],
                      'stock_quantity' => (int)$row['stock_quantity'],
                      'source'         => 'product_sizes'
                    ];
                  }, $q_sizes_trad->fetchAll(PDO::FETCH_ASSOC));
                }
              }
              return $rows;
            }

            // Catálogos para Pack Personalizable: Polos / Poleras / Jeans
            $stmt = $db->prepare("
            SELECT p.id, p.name, p.image_url, p.base_price, p.has_variants
            FROM products p
            JOIN categories c ON p.category_id=c.id
            WHERE LOWER(c.name) LIKE :cat AND p.is_active=1
            ORDER BY p.id DESC
            LIMIT 200
          ");
            $likePolos = '%unidad - polos%';
            $stmt->bindParam(':cat', $likePolos);
            $stmt->execute();
            $CAT_POLOS = add_sizes_to_rows_pack_mixed($db, $stmt->fetchAll(PDO::FETCH_ASSOC));

            $stmt = $db->prepare("
            SELECT p.id, p.name, p.image_url, p.base_price, p.has_variants
            FROM products p
            JOIN categories c ON p.category_id=c.id
            WHERE LOWER(c.name) LIKE :cat AND p.is_active=1
            ORDER BY p.id DESC
            LIMIT 200
          ");
            $likePoleras = '%unidad - poleras%';
            $stmt->bindParam(':cat', $likePoleras);
            $stmt->execute();
            $CAT_POLERAS = add_sizes_to_rows_pack_mixed($db, $stmt->fetchAll(PDO::FETCH_ASSOC));

            $stmt = $db->prepare("
            SELECT p.id, p.name, p.image_url, p.base_price, p.has_variants
            FROM products p
            JOIN categories c ON p.category_id=c.id
            WHERE LOWER(c.name) LIKE :cat AND p.is_active=1
            ORDER BY p.id DESC
            LIMIT 200
          ");
            $likeJeans = '%unidad - jeans%';
            $stmt->bindParam(':cat', $likeJeans);
            $stmt->execute();
            $CAT_JEANS = add_sizes_to_rows_pack_mixed($db, $stmt->fetchAll(PDO::FETCH_ASSOC));

            // Definir 3 slots en orden: Polera, Polo, Jean
            $pack_slots = [
              ['type' => 'polera', 'label' => 'Polera'],
              ['type' => 'polo',   'label' => 'Polo'],
              ['type' => 'jean',   'label' => 'Jean'],
            ];
            ?>
            <div class="card border-0 shadow-sm pack-grid">
              <div class="card-header bg-dark text-white">
                <strong><i class="fa-solid fa-sliders me-2"></i>Personaliza tu pack</strong>
              </div>
              <div class="card-body">
                <div class="row g-3">
                  <?php foreach ($pack_slots as $idx => $slot): ?>
                    <!-- 3 columnas en desktop, 1 en móvil -->
                    <div class="col-12">
                      <div class="pack-slot">
                        <button class="pack-arrow left" data-slot="<?php echo $idx; ?>"><i class="fa-solid fa-chevron-left"></i></button>
                        <div class="edge-blur left" id="packBlurL-<?php echo $idx; ?>"></div>
                        <img id="packImg-<?php echo $idx; ?>" class="w-100 d-block" alt="<?php echo htmlspecialchars($slot['label']); ?>" loading="lazy" decoding="async">
                        <div class="edge-blur right" id="packBlurR-<?php echo $idx; ?>"></div>
                        <button class="pack-arrow right" data-slot="<?php echo $idx; ?>"><i class="fa-solid fa-chevron-right"></i></button>
                      </div>

                      <div class="pack-info mt-2 small text-muted">
                        <?php $iconClass = ($slot['type'] === 'jean' ? 'fa-user' : 'fa-shirt'); ?>
                        <i class="fa-solid <?php echo $iconClass; ?> me-1"></i>
                        <span class="fw-semibold"><?php echo htmlspecialchars($slot['label']); ?>:</span>
                        <span id="packName-<?php echo $idx; ?>"></span>
                      </div>

                      <div class="pack-info fw-semibold">S/ <span id="packPrice-<?php echo $idx; ?>"></span></div>

                      <!-- Selector de color (si aplica) -->
                      <div class="mt-2 pack-info">
                        <label class="form-label fw-bold mb-1">Color:</label>
                        <div class="pack-colors d-inline-block" id="packColors-<?php echo $idx; ?>"></div>
                        <div><small id="packColorHelp-<?php echo $idx; ?>" class="text-muted"></small></div>
                      </div>

                      <!-- Selector de tallas para el slot -->
                      <div class="mt-2 pack-info">
                        <label class="form-label fw-bold mb-1">Talla:</label>
                        <div class="pack-sizes d-inline-block" id="packSizes-<?php echo $idx; ?>"></div>
                        <div><small id="packSizeHelp-<?php echo $idx; ?>" class="text-muted"></small></div>
                      </div>

                      <!-- Cantidad por slot -->
                      <div class="mt-2 pack-info">
                        <label class="form-label fw-bold mb-1">Cantidad:</label>
                        <div class="pack-qty">
                          <button type="button" class="qty-btn" data-slot="<?php echo $idx; ?>" data-dir="-1">-</button>
                          <input type="number"
                            class="form-control form-control-sm"
                            id="packQty-<?php echo $idx; ?>"
                            value="<?php echo (int)$PACK_MIN_QTY_PER_ITEM; ?>"
                            min="<?php echo (int)$PACK_MIN_QTY_PER_ITEM; ?>"
                            step="<?php echo (int)$PACK_STEP_QTY_PER_ITEM; ?>">
                          <button type="button" class="qty-btn" data-slot="<?php echo $idx; ?>" data-dir="1">+</button>
                        </div>
                        <small id="packQtyHelp-<?php echo $idx; ?>" class="text-muted"></small>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>

                <hr class="my-4">
                <div class="price-summary-pack mb-2">
                  <div>
                    <div class="text-muted">Total del pack</div>
                    <div class="h4 mb-0">S/ <span id="packTotal"></span></div>
                    <small class="text-muted d-block">Se agregarán <?php echo count($pack_slots); ?> ítems al carrito.</small>
                  </div>
                  <button id="btnAddPack" class="btn btn-pack btn-lg">
                    <i class="fa-solid fa-cart-plus me-2"></i>Agregar Pack al carrito
                  </button>
                </div>
                <small class="text-muted">El total se calcula como la suma de los productos elegidos (no hay precio fijo de pack).</small>
              </div>
            </div>

            <script>
              // Config JS
              const PACK_MIN_QTY = <?php echo (int)$PACK_MIN_QTY_PER_ITEM; ?>;
              const PACK_STEP_QTY = <?php echo (int)$PACK_STEP_QTY_PER_ITEM; ?>;
              const PACK_HIDE_OOS = <?php echo $PACK_HIDE_OOS_SIZES ? 'true' : 'false'; ?>;
              const PACK_REQUIRE_COLOR = <?php echo $PACK_REQUIRE_COLOR_WHEN_AVAILABLE ? 'true' : 'false'; ?>;

              const CAT_POLOS = <?php echo json_encode($CAT_POLOS,   JSON_UNESCAPED_UNICODE); ?>;
              const CAT_POLERAS = <?php echo json_encode($CAT_POLERAS, JSON_UNESCAPED_UNICODE); ?>;
              const CAT_JEANS = <?php echo json_encode($CAT_JEANS,   JSON_UNESCAPED_UNICODE); ?>;
              const PACK_SLOTS = <?php echo json_encode($pack_slots,  JSON_UNESCAPED_UNICODE); ?>;

              // estado por slot en el orden solicitado: Polera, Polo, Jean
              const state = PACK_SLOTS.map(s => ({
                type: s.type,
                data: (s.type === 'jean' ? CAT_JEANS : (s.type === 'polo' ? CAT_POLOS : CAT_POLERAS)),
                i: 0,
                size_id: null, // product_sizes.id (tradicional) o sizes.id (variantes)
                color_id: null, // solo para variantes
                qty: PACK_MIN_QTY
              }));

              function primaryImageFor(it) {
                if (!it) return '';
                let url = it.image_disp || it.image_url || '';
                if (it.colors && it.colors.length) {
                  const c = it.colors[0];
                  if (c && c.image_url) url = c.image_url;
                }
                return url || '';
              }

              function getItem(k) {
                const s = state[k];
                return (s && s.data && s.data.length) ? s.data[s.i] : null;
              }

              function currentColorObj(item, k) {
                if (!item || !item.colors || !item.colors.length) return null;
                if (!state[k].color_id) state[k].color_id = String(item.colors[0].id);
                return item.colors.find(c => String(c.id) === String(state[k].color_id)) || item.colors[0];
              }

              function sizesFor(item, k) {
                const col = currentColorObj(item, k);
                if (col && Array.isArray(col.sizes) && col.sizes.length) return col.sizes;
                return item.sizes || [];
              }

              function filterSizesByStock(list) {
                if (!PACK_HIDE_OOS) return list;
                return list.filter(sz => (sz.stock_quantity || 0) > 0);
              }

              function firstAvailableSizeIn(list) {
                if (!list || !list.length) return null;
                for (const sz of list) {
                  if ((sz.stock_quantity || 0) > 0) return (sz.id_for_cart ?? sz.id);
                }
                return (list[0].id_for_cart ?? list[0].id);
              }

              function getSelectedSizeObj(list, sizeId) {
                return list.find(s => String((s.id_for_cart ?? s.id)) === String(sizeId)) || null;
              }

              // Render de colores
              function renderColors(k, item) {
                const wrap = document.getElementById('packColors-' + k);
                const help = document.getElementById('packColorHelp-' + k);
                if (!wrap) return;
                wrap.innerHTML = '';
                if (!item.colors || !item.colors.length) {
                  wrap.innerHTML = '<div class="text-muted">N/A</div>';
                  if (help) help.textContent = '';
                  return;
                }
                if (!state[k].color_id) state[k].color_id = String(item.colors[0].id);

                item.colors.forEach(c => {
                  const dot = document.createElement('span');
                  dot.className = 'color-dot' + (String(state[k].color_id) === String(c.id) ? ' active' : '');
                  dot.style.backgroundColor = c.hex_code || '#ccc';
                  if (!c.hex_code || c.hex_code.toLowerCase() === '#ffffff') dot.style.border = '1px solid #ddd';
                  dot.title = c.name;
                  dot.dataset.colorId = c.id;
                  wrap.appendChild(dot);
                });

                wrap.querySelectorAll('.color-dot').forEach(dot => {
                  dot.addEventListener('click', () => {
                    state[k].color_id = dot.dataset.colorId;
                    const col = currentColorObj(item, k);
                    if (col && col.image_url) {
                      const img = document.getElementById('packImg-' + k);
                      if (img) img.src = col.image_url;
                    }
                    wrap.querySelectorAll('.color-dot').forEach(d => d.classList.remove('active'));
                    dot.classList.add('active');
                    if (help) help.textContent = 'Color: ' + (currentColorObj(item, k)?.name || '');
                    state[k].size_id = null;
                    renderSizes(k, item);
                  });
                });

                if (help) help.textContent = 'Color: ' + (currentColorObj(item, k)?.name || '');
              }

              // Render de tallas
              function renderSizes(k, item) {
                const wrap = document.getElementById('packSizes-' + k);
                const help = document.getElementById('packSizeHelp-' + k);
                if (!wrap) return;
                wrap.innerHTML = '';

                let list = sizesFor(item, k);
                list = filterSizesByStock(list);

                if (!list || !list.length) {
                  wrap.innerHTML = '<div class="text-muted">Sin tallas disponibles por stock.</div>';
                  if (help) help.textContent = '';
                  state[k].size_id = null;
                  updateQtyMax(k, 0);
                  return;
                }
                if (!state[k].size_id) state[k].size_id = firstAvailableSizeIn(list);

                list.forEach(sz => {
                  const idForCart = (sz.id_for_cart ?? sz.id);
                  const uid = `slot${k}-size-${idForCart}`;
                  const disabled = (!PACK_HIDE_OOS && (sz.stock_quantity || 0) <= 0) ? 'disabled' : '';
                  const checked = (String(state[k].size_id) === String(idForCart)) ? 'checked' : '';
                  const badges = (sz.stock_quantity <= 0 && !PACK_HIDE_OOS) ? '<small class="d-block text-danger">Agotado</small>' :
                    (sz.stock_quantity > 0 && sz.stock_quantity <= 5) ? `<small class="d-block text-warning">Últimas ${sz.stock_quantity}</small>` : '';
                  wrap.insertAdjacentHTML('beforeend', `
                <input type="radio" class="btn-check" name="packsz-${k}" id="${uid}" ${disabled} ${checked}>
                <label class="btn btn-outline-dark" for="${uid}" data-size="${idForCart}" data-stock="${sz.stock_quantity}">
                  ${sz.name}${badges}
                </label>
              `);
                });

                wrap.querySelectorAll('label.btn').forEach(lbl => {
                  lbl.addEventListener('click', () => {
                    const sid = lbl.getAttribute('data-size');
                    const st = parseInt(lbl.getAttribute('data-stock') || '0');
                    state[k].size_id = sid;
                    updateQtyMax(k, st);
                    if (help) {
                      const sel = getSelectedSizeObj(list, sid);
                      help.textContent = sel ? `Seleccionado: ${sel.name} (Stock: ${sel.stock_quantity})` : '';
                    }
                  });
                });

                const sel = getSelectedSizeObj(list, state[k].size_id);
                updateQtyMax(k, sel ? (parseInt(sel.stock_quantity) || 0) : 0);
                if (help && sel) {
                  help.textContent = `Seleccionado: ${sel.name} (Stock: ${sel.stock_quantity})`;
                }
              }

              function updateQtyMax(k, stock) {
                const qtyInput = document.getElementById('packQty-' + k);
                const qtyHelp = document.getElementById('packQtyHelp-' + k);
                if (!qtyInput) return;
                if (stock <= 0) {
                  qtyInput.max = 0;
                  qtyInput.value = 0;
                  state[k].qty = 0;
                  if (qtyHelp) qtyHelp.textContent = 'Sin stock';
                  return;
                }
                qtyInput.max = stock;
                // ajustar al rango permitido
                let val = parseInt(qtyInput.value) || PACK_MIN_QTY;
                if (val < PACK_MIN_QTY) val = PACK_MIN_QTY;
                if (val > stock) val = stock - (stock % PACK_STEP_QTY || 0);
                if (val < PACK_MIN_QTY) val = PACK_MIN_QTY; // por si el ajuste anterior cae en 0
                qtyInput.value = val;
                state[k].qty = val;
                if (qtyHelp) qtyHelp.textContent = `Máx: ${stock}`;
              }

              function renderSlot(k) {
                const s = state[k];
                if (!s.data || !s.data.length) return;
                const it = s.data[s.i];

                // Imagen preferente por color seleccionado
                let imgUrl = it.image_disp || it.image_url || '';
                if (it.colors && it.colors.length) {
                  if (!state[k].color_id) state[k].color_id = String(it.colors[0].id);
                  const col = currentColorObj(it, k);
                  if (col && col.image_url) imgUrl = col.image_url;
                }
                const imgEl = document.getElementById('packImg-' + k);
                if (imgEl && imgUrl) imgEl.src = imgUrl;

                // Edge blur previews
                const totalN = s.data.length;
                const prevI = (s.i - 1 + totalN) % totalN;
                const nextI = (s.i + 1) % totalN;
                const prevItem = s.data[prevI];
                const nextItem = s.data[nextI];
                const prevUrl = primaryImageFor(prevItem);
                const nextUrl = primaryImageFor(nextItem);
                const blurL = document.getElementById('packBlurL-' + k);
                const blurR = document.getElementById('packBlurR-' + k);
                if (blurL) blurL.style.backgroundImage = prevUrl ? `url('${prevUrl}')` : 'none';
                if (blurR) blurR.style.backgroundImage = nextUrl ? `url('${nextUrl}')` : 'none';

                const nameEl = document.getElementById('packName-' + k);
                if (nameEl) nameEl.textContent = it.name || '';

                const priceEl = document.getElementById('packPrice-' + k);
                if (priceEl) priceEl.textContent = (parseFloat(it.base_price) || 0).toFixed(2);

                renderColors(k, it);
                renderSizes(k, it);

                // Inicializar cantidad con mínimo si no está
                const qtyInput = document.getElementById('packQty-' + k);
                if (qtyInput) {
                  if (!state[k].qty || state[k].qty < PACK_MIN_QTY) state[k].qty = PACK_MIN_QTY;
                  qtyInput.value = state[k].qty;
                }
              }

              function renderTotal() {
                const total = state.reduce((acc, s) => {
                  const it = (s.data && s.data.length) ? s.data[s.i] : null;
                  const qty = Math.max(0, parseInt(s.qty) || 0);
                  return acc + (it ? (parseFloat(it.base_price) || 0) * qty : 0);
                }, 0);
                const el = document.getElementById('packTotal');
                if (el) el.textContent = total.toFixed(2);
              }

              function renderAll() {
                for (let k = 0; k < state.length; k++) renderSlot(k);
                renderTotal();
              }

              // Flechas
              document.querySelectorAll('.pack-arrow').forEach(btn => {
                btn.addEventListener('click', () => {
                  const k = parseInt(btn.getAttribute('data-slot'));
                  const s = state[k];
                  if (!s || !s.data || !s.data.length) return;
                  const dir = btn.classList.contains('right') ? +1 : -1;
                  s.i = (s.i + dir + s.data.length) % s.data.length;
                  const it = getItem(k);
                  // Reset color y talla al cambiar de producto del slot
                  s.color_id = (it && it.colors && it.colors.length) ? String(it.colors[0].id) : null;
                  s.size_id = null;
                  // Reset qty al mínimo
                  s.qty = PACK_MIN_QTY;
                  renderSlot(k);
                  renderTotal();
                });
              });

              // Qty botones
              document.querySelectorAll('.qty-btn').forEach(b => {
                b.addEventListener('click', () => {
                  const k = parseInt(b.getAttribute('data-slot'));
                  const dir = parseInt(b.getAttribute('data-dir'));
                  const input = document.getElementById('packQty-' + k);
                  if (!input) return;
                  const max = parseInt(input.max) || 999;
                  let val = parseInt(input.value) || PACK_MIN_QTY;
                  val = val + dir * PACK_STEP_QTY;
                  if (val < PACK_MIN_QTY) val = PACK_MIN_QTY;
                  if (val > max) val = max;
                  input.value = val;
                  state[k].qty = val;
                  renderTotal();
                });
              });

              // Qty manual
              PACK_SLOTS.forEach((_, k) => {
                const input = document.getElementById('packQty-' + k);
                if (!input) return;
                input.addEventListener('change', () => {
                  let val = parseInt(input.value) || PACK_MIN_QTY;
                  const max = parseInt(input.max) || 999;
                  if (val < PACK_MIN_QTY) val = PACK_MIN_QTY;
                  if (val > max) val = max;
                  // respetar step
                  if (PACK_STEP_QTY > 1) {
                    val = Math.max(PACK_MIN_QTY, Math.floor(val / PACK_STEP_QTY) * PACK_STEP_QTY);
                  }
                  input.value = val;
                  state[k].qty = val;
                  renderTotal();
                });
              });

              document.getElementById('btnAddPack').addEventListener('click', async () => {
                // Validaciones previas
                for (let idx = 0; idx < state.length; idx++) {
                  const it = getItem(idx);
                  if (!it) {
                    alert('Producto inválido en el pack');
                    return;
                  }

                  // Color requerido si variantes
                  if (PACK_REQUIRE_COLOR && it.colors && it.colors.length && !state[idx].color_id) {
                    alert(`Selecciona el color para "${it.name}"`);
                    return;
                  }
                  // Talla requerida
                  if (!state[idx].size_id) {
                    alert(`Selecciona la talla para "${it.name}"`);
                    return;
                  }
                  // Cantidad mínima y stock
                  const qtyInput = document.getElementById('packQty-' + idx);
                  const qty = parseInt(qtyInput?.value) || 0;
                  if (qty < PACK_MIN_QTY) {
                    alert(`La cantidad para "${it.name}" debe ser mínimo ${PACK_MIN_QTY}.`);
                    return;
                  }
                  // stock de la talla seleccionada
                  let list = sizesFor(it, idx);
                  list = filterSizesByStock(list);
                  const sel = getSelectedSizeObj(list, state[idx].size_id);
                  const maxStock = sel ? (parseInt(sel.stock_quantity) || 0) : 0;
                  if (maxStock <= 0) {
                    alert(`La talla seleccionada de "${it.name}" no tiene stock.`);
                    return;
                  }
                  if (qty > maxStock) {
                    alert(`La cantidad de "${it.name}" excede el stock (${maxStock}).`);
                    return;
                  }
                }

                async function addOne(pid, sizeId, colorId, quantity) {
                  const fd = new FormData();
                  fd.append('action', 'add');
                  fd.append('product_id', pid);
                  fd.append('quantity', quantity);
                  if (sizeId) fd.append('size_id', sizeId); // product_sizes.id o sizes.id
                  if (colorId) fd.append('color_id', colorId); // solo variantes
                  const r = await fetch('cart_action.php', {
                    method: 'POST',
                    body: fd
                  });
                  return r.json();
                }
                try {
                  let last = null;
                  for (let idx = 0; idx < state.length; idx++) {
                    const it = getItem(idx);
                    const qty = Math.max(PACK_MIN_QTY, parseInt(state[idx].qty) || PACK_MIN_QTY);
                    last = await addOne(it.id, state[idx].size_id, state[idx].color_id, qty);
                    if (!last || last.status !== 'ok') throw new Error(last && last.message ? last.message : 'Error al agregar');
                  }
                  if (last && last.status === 'ok' && window.SGUCart && SGUCart.updateBadge) SGUCart.updateBadge(last.total_items);
                  const n = document.createElement('div');
                  n.className = 'notification';
                  n.innerHTML = `<i class="fas fa-check-circle"></i>Pack agregado al carrito`;
                  document.body.appendChild(n);
                  setTimeout(() => n.classList.add('show'), 100);
                  setTimeout(() => {
                    n.classList.remove('show');
                    setTimeout(() => n.remove(), 300);
                  }, 3000);
                } catch (e) {
                  alert('No se pudo agregar el pack: ' + (e.message || 'error'));
                }
              });

              renderAll();
            </script>

          <?php else: ?>
            <!-- ==== Vista estándar (NO pack) ==== -->
            <?php if (!empty($gallery_images_final)): ?>
              <div class="product-media-grid">
                <div class="product-thumbs">
                  <?php foreach ($gallery_images_final as $idx => $img): ?>
                    <button type="button" class="thumb-btn <?php echo $idx === 0 ? 'active' : ''; ?>" data-img="<?php echo htmlspecialchars($img); ?>">
                      <img src="<?php echo htmlspecialchars($img); ?>" alt="Miniatura <?php echo $idx + 1; ?>" loading="lazy">
                    </button>
                  <?php endforeach; ?>
                </div>
                <div class="product-main">
                  <div class="product-image-container position-relative">
                    <img src="<?php echo htmlspecialchars($gallery_images_final[0]); ?>" alt="<?php echo htmlspecialchars($current_product['name']); ?>" class="img-fluid product-detail-image clickable-image" id="productImage" data-bs-toggle="modal" data-bs-target="#imageModal" style="cursor:pointer;">
                    <div class="zoom-icon position-absolute top-0 end-0 m-3"><i class="fas fa-search-plus text-white bg-dark bg-opacity-75 p-2 rounded-circle"></i></div>
                  </div>
                </div>
              </div>
            <?php else: ?>
              <div class="bg-light d-flex align-items-center justify-content-center product-detail-image" style="height:500px;"><i class="fas fa-image fa-5x text-muted"></i></div>
            <?php endif; ?>
          <?php endif; ?>

        </div>
        <!-- /col izquierda -->

        <!-- ===== Columna derecha ===== -->
        <div class="col-lg-4">
          <div class="product-detail-info">

            <?php if ($is_pack): ?>
              <div class="right-pack-header">
                <h1 class="right-pack-title">Crea tu outfit</h1>
                <span class="badge bg-dark">UNIDAD - PACKS</span>
              </div>
              <p class="right-pack-sub">Elige una polera, un polo y un jean. El total se calcula con la suma de los productos seleccionados.</p>
            <?php else: ?>
              <h1 class="h2 fw-bold mb-3"><?php echo htmlspecialchars($display_name); ?></h1>
            <?php endif; ?>

            <?php if (!empty($current_product['category_name']) && !$is_pack): ?>
              <div class="mb-3">
                <span class="badge fs-6" style="background-color: var(--accent-color); color: var(--primary-color);">
                  <?php echo htmlspecialchars($current_product['category_name']); ?>
                </span>
              </div>
            <?php endif; ?>

            <?php if (!$is_pack): ?>
              <div class="product-price mb-4">
                <?php
                $is_mayorista = strpos(strtolower($current_product['category_name']), 'mayorista') !== false;
                $is_unidad    = strpos(strtolower($current_product['category_name']), 'unidad') !== false;
                ?>
                <?php if ($is_mayorista): ?>
                  <span class="badge badge-gold mb-2 fs-6">MAYORISTA</span>
                  <div class="h3 text-gold mb-1">S/ <?php echo number_format($current_product['base_price'], 2); ?></div>
                  <small class="text-muted">Compra mínima: 6 unidades</small>
                <?php elseif ($is_unidad): ?>
                  <span class="badge bg-primary mb-2 fs-6">UNIDAD</span>
                  <div class="h3 text-primary mb-1">S/ <?php echo number_format($current_product['base_price'], 2); ?></div>
                  <small class="text-muted">Precio por unidad</small>
                <?php else: ?>
                  <div class="h3 text-primary">S/ <?php echo number_format($current_product['base_price'], 2); ?></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php
            // STOCK (oculto para packs para evitar "agotado" cuando es solo un contenedor)
            if (!$is_pack) {
              if ($current_product['has_variants']) {
                $total_stock = $productVariant->getTotalStock($product_id);
              } else {
                $stmt = $db->prepare("SELECT SUM(stock_quantity) as total FROM product_sizes WHERE product_id=?");
                $stmt->execute([$product_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $total_stock = $result ? (int)$result['total'] : 0;
              }
            }
            ?>
            <?php if (!$is_pack): ?>
              <div class="mb-4" id="stock-status">
                <?php if ($total_stock >= $min_required): ?>
                  <div class="d-flex align-items-center text-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <span><strong>En stock</strong> <span id="stock-count">(<?php echo $total_stock; ?> disponibles)</span></span>
                  </div>
                  <div id="stock-warning" class="d-none"><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> ¡Últimas unidades disponibles!</small></div>
                <?php elseif ($total_stock > 0): ?>
                  <div class="d-flex align-items-center text-warning">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <span><strong>Stock insuficiente para compra mínima (<?php echo $MAYORISTA_MIN_QTY; ?>)</strong> <span id="stock-count">(<?php echo $total_stock; ?> disponibles)</span></span>
                  </div>
                <?php else: ?>
                  <div class="d-flex align-items-center text-danger"><i class="fas fa-times-circle me-2"></i><span><strong>Producto agotado</strong></span></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if (!$is_pack): ?>
              <!-- Controles SOLO para productos normales -->
              <?php if ($current_product['has_variants'] && !empty($available_colors)): ?>
                <div class="mb-4">
                  <label class="form-label fw-bold">Color:</label>
                  <div class="row g-2">
                    <?php foreach ($available_colors as $i => $color): ?>
                      <div class="col-auto">
                        <input type="radio" class="btn-check" name="color" id="color-<?php echo $color['id']; ?>" value="<?php echo $color['id']; ?>" <?php echo $i === 0 ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-dark color-btn d-flex align-items-center" for="color-<?php echo $color['id']; ?>">
                          <span class="me-2" style="background:<?php echo $color['hex_code']; ?>;width:20px;height:20px;border-radius:50%;border:1px solid #ddd;"></span>
                          <?php echo htmlspecialchars($color['name']); ?>
                        </label>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>

              <div class="mb-3">
                <label class="form-label fw-bold">Talla:</label>
                <div class="row g-2" id="size-options">
                  <?php if ($current_product['has_variants']): ?>
                    <!-- Se llena por JS según color -->
                  <?php elseif (!empty($available_sizes)): ?>
                    <?php foreach ($available_sizes as $index => $s): ?>
                      <div class="col-auto">
                        <input type="radio" class="btn-check" name="size" id="size-<?php echo $s['id']; ?>" value="<?php echo $s['id']; ?>"
                          data-stock="<?php echo (int)$s['stock_quantity']; ?>"
                          <?php echo $index === 0 ? 'checked' : ''; ?>
                          <?php echo ((int)$s['stock_quantity']) <= 0 ? 'disabled' : ''; ?>>
                        <label class="btn btn-outline-dark size-btn <?php echo ((int)$s['stock_quantity']) <= 0 ? 'disabled' : ''; ?>" for="size-<?php echo $s['id']; ?>">
                          <?php echo htmlspecialchars($s['name']); ?>
                          <?php if (((int)$s['stock_quantity']) <= 0): ?>
                            <small class="d-block text-danger">Agotado</small>
                          <?php elseif (((int)$s['stock_quantity']) <= 5): ?>
                            <small class="d-block text-warning">Últimas <?php echo (int)$s['stock_quantity']; ?></small>
                          <?php endif; ?>
                        </label>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="col-12">
                      <div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>No hay tallas disponibles para este producto.</div>
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Precio + cantidad + botón (solo NO pack) -->
              <div class="mb-4">
                <div class="price-summary p-3 bg-light rounded">
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Precio Unitario:</span>
                    <span class="h5 mb-0" style="color: var(--accent-color);" id="total-price">S/ <?php echo number_format($current_product['base_price'], 2); ?></span>
                  </div>
                  <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="fw-bold">Total a Pagar:</span>
                    <span class="h4 mb-0" style="color: var(--primary-color);" id="quantity-total-display">S/ <?php echo number_format($current_product['base_price'], 2); ?></span>
                  </div>
                  <small class="text-muted"><span id="price-breakdown">Precio base: S/ <?php echo number_format($current_product['base_price'], 2); ?></span></small>
                </div>
              </div>

              <?php
              if ($current_product['has_variants']) {
                $total_stock = $productVariant->getTotalStock($product_id);
              } else {
                $stmt = $db->prepare("SELECT SUM(stock_quantity) as total FROM product_sizes WHERE product_id=?");
                $stmt->execute([$product_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $total_stock = $result ? (int)$result['total'] : 0;
              }
              ?>
              <?php if ($total_stock >= $min_required): ?>
                <div class="row align-items-center mb-2">
                  <div class="col-md-4">
                    <label for="quantity" class="form-label fw-bold">Cantidad:</label>
                    <div class="quantity-selector">
                      <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                      <input type="number" id="quantity" value="<?php echo $is_mayorista ? $MAYORISTA_MIN_QTY : 1; ?>" min="<?php echo $is_mayorista ? $MAYORISTA_MIN_QTY : 1; ?>" max="<?php echo $total_stock; ?>" step="1" readonly>
                      <button type="button" class="quantity-btn" onclick="changeQuantity(1)">+</button>
                    </div>
                  </div>
                  <div class="col-md-8">
                    <button class="btn btn-primary btn-lg w-100 add-to-cart"
                      data-product-id="<?php echo $current_product['id']; ?>"
                      data-product-name="<?php echo htmlspecialchars($current_product['name']); ?>"
                      data-product-price="<?php echo $current_product['base_price']; ?>"
                      data-product-image="<?php echo htmlspecialchars(asset_url_with_v($current_product['image_url'])); ?>">
                      <i class="fas fa-cart-plus"></i> Agregar al Carrito (<?php echo $is_mayorista ? 'Mayorista' : 'Unidad'; ?>)
                    </button>
                  </div>
                </div>
                <?php if ($is_mayorista): ?>
                  <div class="mb-4">
                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Compra mínima: <?php echo $MAYORISTA_MIN_QTY; ?> unidades.</small>
                  </div>
                <?php endif; ?>
              <?php endif; ?>
            <?php endif; // !$is_pack 
            ?>

            <!-- ===== ACORDEÓN ===== -->
            <div class="mb-4">
              <div class="accordion accordion-flush jk-accordion" id="productInfoAccordion">
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingDetalles">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDetalles" aria-expanded="false" aria-controls="collapseDetalles">Detalles</button>
                  </h2>
                  <div id="collapseDetalles" class="accordion-collapse collapse" aria-labelledby="headingDetalles" data-bs-parent="#productInfoAccordion">
                    <div class="accordion-body"><?php echo nl2br(htmlspecialchars($detalle_texto)); ?></div>
                  </div>
                </div>
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingTallas">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTallas" aria-expanded="false" aria-controls="collapseTallas">Guía de tallas</button>
                  </h2>
                  <div id="collapseTallas" class="accordion-collapse collapse" aria-labelledby="headingTallas" data-bs-parent="#productInfoAccordion">
                    <div class="accordion-body"><?php echo $guia_tallas_html; ?></div>
                  </div>
                </div>
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingEnvio">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEnvio" aria-expanded="false" aria-controls="collapseEnvio">Envío - Cambios y Devoluciones</button>
                  </h2>
                  <div id="collapseEnvio" class="accordion-collapse collapse" aria-labelledby="headingEnvio" data-bs-parent="#productInfoAccordion">
                    <div class="accordion-body"><?php echo nl2br(htmlspecialchars($envio_cambios_texto)); ?></div>
                  </div>
                </div>
              </div>
            </div>
            <!-- ===== /ACORDEÓN ===== -->

            <!-- Info rápida -->
            <div class="row mt-4">
              <div class="col-md-6">
                <div class="d-flex align-items-center mb-2"><i class="fas fa-truck me-2" style="color: var(--accent-color);"></i><span>Delivery: Envíos a todo el Perú</span></div>
                <div class="d-flex align-items-center mb-2"><i class="fas fa-shield-alt me-2" style="color: var(--accent-color);"></i><span>Garantía de calidad JK Grupo Textil</span></div>
              </div>
              <div class="col-md-6">
                <div class="d-flex align-items-center mb-2"><i class="fas fa-undo me-2" style="color: var(--accent-color);"></i><span>Cambios y devoluciones</span></div>
                <div class="d-flex align-items-center mb-2"><i class="fas fa-whatsapp me-2" style="color: var(--accent-color);"></i><span>Atención por WhatsApp</span></div>
              </div>
            </div>
          </div>
        </div>
        <!-- /col derecha -->

      </div>
    </div>
  </section>

  <?php if (!empty($related_products)): ?>
    <section class="py-5 bg-light">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="fw-bold">Productos Relacionados</h2>
          <p class="text-muted">Otros productos que podrían interesarte</p>
        </div>
        <div class="row">
          <?php foreach ($related_products as $related): ?>
            <div class="col-lg-3 col-md-6 mb-4">
              <div class="card product-card h-100 shadow-sm">
                <div class="position-relative">
                  <?php $img = $related['image_url']; ?>
                  <?php if (!empty($img)): ?>
                    <a href="product.php?id=<?php echo (int)$related['id']; ?>" class="d-block">
                      <img src="<?php echo htmlspecialchars(asset_url_with_v($img)); ?>" alt="<?php echo htmlspecialchars($related['name']); ?>" class="card-img-top product-image" loading="lazy">
                    </a>
                  <?php else: ?>
                    <a href="product.php?id=<?php echo (int)$related['id']; ?>" class="d-block text-decoration-none">
                      <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:250px;"><i class="fas fa-image fa-3x text-muted"></i></div>
                    </a>
                  <?php endif; ?>
                </div>
                <div class="card-body d-flex flex-column">
                  <h5 class="card-title"><a href="product.php?id=<?php echo (int)$related['id']; ?>" class="text-dark text-decoration-none"><?php echo htmlspecialchars($related['name']); ?></a></h5>
                  <p class="card-text text-muted flex-grow-1"><?php echo htmlspecialchars(substr($related['description'], 0, 80)); ?>...</p>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="h5 mb-0" style="color: var(--accent-color);">S/ <?php echo number_format($related['base_price'], 2); ?></span>
                    <a class="btn btn-primary btn-sm" href="product.php?id=<?php echo (int)$related['id']; ?>"><i class="fas fa-eye"></i> Ver</a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <!-- Modal zoom PRO (Canvas + Lens + Navegación) -->
  <div class="modal fade zoom-modal" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
      <div class="modal-content border-0">
        <div class="zoom-toolbar">
          <button class="zoom-btn" id="zoomBg" title="Fondo (oscuro/medio/claro)"><i class="fa-solid fa-palette"></i></button>
          <button class="zoom-btn toggle" id="zoomLensToggle" title="Lupa (L)"><i class="fa-solid fa-circle-dot"></i></button>
          <button class="zoom-btn toggle" id="zoomGridToggle" title="Rejilla (G)"><i class="fa-solid fa-border-all"></i></button>
          <button class="zoom-btn toggle" id="zoomTextureToggle" title="Realzar textura (T)"><i class="fa-solid fa-sparkles"></i></button>
          <button class="zoom-btn" id="zoomOut" title="Alejar (-)"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
          <button class="zoom-btn" id="zoomIn" title="Acercar (+)"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
          <button class="zoom-btn" data-preset="1" title="100% (1)">1x</button>
          <button class="zoom-btn" data-preset="2" title="200% (2)">2x</button>
          <button class="zoom-btn" data-preset="3" title="300% (3)">3x</button>
          <button class="zoom-btn" data-preset="4" title="400% (4)">4x</button>
          <button class="zoom-btn" id="zoomReset" title="Ajustar (0)"><i class="fa-solid fa-compress"></i></button>
          <span class="zoom-meter" id="zoomMeter">100%</span>
          <button type="button" class="zoom-btn" data-bs-dismiss="modal" aria-label="Cerrar" title="Cerrar (Esc)"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <button class="zoom-nav prev" id="zoomPrev" title="Anterior (←)"><i class="fa-solid fa-chevron-left"></i></button>
        <button class="zoom-nav next" id="zoomNext" title="Siguiente (→)"><i class="fa-solid fa-chevron-right"></i></button>

        <div class="zoom-stage bg-darkish" id="zoomStage">
          <div class="zoom-grid" id="zoomGrid"></div>
          <img id="zoomImg" src="<?php echo !empty($gallery_images_final) ? htmlspecialchars($gallery_images_final[0]) : htmlspecialchars(asset_url_with_v($current_product['image_url'])); ?>" alt="Zoom" draggable="false">
          <div class="zoom-lens" id="zoomLens"></div>
          <div class="zoom-hint d-none d-md-inline">
            Rueda/pellizca para zoom. Arrastra para mover. Doble clic para alternar. Atajos: + - 0 1 2 3 4 L G T ← →
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include COMPONENT_PATH . 'footer.php'; ?>

  <?php if (!$is_pack): ?>
    <script>
      // thumbs → imagen principal + sincroniza modal de zoom
      document.addEventListener('DOMContentLoaded', function() {
        const mainImg = document.getElementById('productImage');
        const zoomImg = document.getElementById('zoomImg');
        const thumbs = document.querySelectorAll('.thumb-btn');

        function setActive(btn) {
          document.querySelectorAll('.thumb-btn.active').forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
        }
        thumbs.forEach(btn => btn.addEventListener('click', () => {
          const url = btn.getAttribute('data-img');
          if (!url || !mainImg) return;
          mainImg.src = url;
          if (zoomImg) zoomImg.src = url;
          setActive(btn);
        }));
      });
    </script>
  <?php endif; ?>

  <script>
    // helpers (solo para vista NO pack)
    function changeQuantity(delta) {
      const q = document.getElementById('quantity');
      if (!q) return;
      const v = Math.max((parseInt(q.min) || 1), Math.min((parseInt(q.max) || 999), ((parseInt(q.value) || 1) + delta)));
      q.value = v;
      updateTotalPrice();
    }

    function updateTotalPrice() {
      const unit = document.getElementById('total-price');
      const qty = document.getElementById('quantity');
      const out = document.getElementById('quantity-total-display');
      if (!unit || !qty || !out) return;
      const u = parseFloat(unit.textContent.replace('S/ ', '')) || 0;
      const q = parseInt(qty.value) || 1;
      out.textContent = 'S/ ' + (u * q).toFixed(2);
    }
    document.addEventListener('DOMContentLoaded', () => {
      const qty = document.getElementById('quantity');
      if (qty) {
        qty.addEventListener('input', updateTotalPrice);
        qty.addEventListener('change', updateTotalPrice);
        setTimeout(updateTotalPrice, 50);
      }
    });

    // add-to-cart (unidad/mayorista)
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', function() {
          const quantityEl = document.getElementById('quantity');
          const quantity = quantityEl ? quantityEl.value : 1;
          const selectedSize = document.querySelector('input[name="size"]:checked');
          const selectedColor = document.querySelector('input[name="color"]:checked');
          const fd = new FormData();
          fd.append('action', 'add');
          fd.append('product_id', this.dataset.productId);
          fd.append('quantity', quantity);
          if (selectedSize) fd.append('size_id', selectedSize.value);
          if (selectedColor) fd.append('color_id', selectedColor.value);
          fetch('cart_action.php', {
              method: 'POST',
              body: fd
            })
            .then(r => r.json())
            .then(d => {
              if (d.status === 'ok') {
                if (window.SGUCart && SGUCart.updateBadge) SGUCart.updateBadge(d.total_items);
                const n = document.createElement('div');
                n.className = 'notification';
                n.innerHTML = `<i class="fas fa-check-circle"></i>Producto agregado al carrito`;
                document.body.appendChild(n);
                setTimeout(() => n.classList.add('show'), 100);
                setTimeout(() => {
                  n.classList.remove('show');
                  setTimeout(() => n.remove(), 300);
                }, 3000);
              } else alert('Error al agregar: ' + (d.message || 'desconocido'));
            })
            .catch(() => alert('Error al agregar producto'));
        });
      });
    });
  </script>

  <?php if (!$is_pack && $current_product['has_variants']): ?>
    <!-- ===== Sistema de variantes (NO pack) ===== -->
    <script>
      const productVariants = <?php echo json_encode($product_variants); ?>;
      const basePrice = <?php echo (float)$current_product['base_price']; ?>;
      const hasVariants = <?php echo !empty($product_variants) ? 'true' : 'false'; ?>;

      document.addEventListener('DOMContentLoaded', function() {
        if (!hasVariants) return;

        const colorInputs = document.querySelectorAll('input[name="color"]');
        const sizeContainer = document.getElementById('size-options');
        const productImage = document.getElementById('productImage');
        const stockCount = document.getElementById('stock-count');
        const stockWarning = document.getElementById('stock-warning');
        const totalPriceElement = document.getElementById('total-price');
        const priceBreakdownElement = document.getElementById('price-breakdown');
        const zoomImg = document.getElementById('zoomImg');

        if (colorInputs.length > 0) {
          // Inicializar tallas según primer color, pero NO cambiar imagen para respetar la base al cargar
          updateSizesForColor(colorInputs[0].value);
        }

        colorInputs.forEach(input => {
          input.addEventListener('change', function() {
            if (this.checked) {
              updateSizesForColor(this.value);
              updateProductImage(this.value);
            }
          });
        });

        function updateSizesForColor(colorId) {
          const colorVariants = productVariants.filter(v => v.color_id == colorId);
          if (colorVariants.length === 0) {
            sizeContainer.innerHTML = '<div class="col-12"><div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>No hay tallas disponibles para este color.</div></div>';
            return;
          }

          // agrupar stock por talla
          const sizeGroups = {};
          colorVariants.forEach(variant => {
            if (!sizeGroups[variant.size_id]) {
              sizeGroups[variant.size_id] = {
                id: variant.size_id,
                name: variant.size_name,
                stock: 0
              };
            }
            sizeGroups[variant.size_id].stock += parseInt(variant.stock_quantity);
          });

          let sizesHTML = '';
          let firstSize = true;
          Object.values(sizeGroups).forEach(size => {
            const isDisabled = size.stock <= 0;
            const isLowStock = size.stock <= 5 && size.stock > 0;
            sizesHTML += `
          <div class="col-auto">
            <input type="radio" class="btn-check" name="size" 
                   id="size-${size.id}" 
                   value="${size.id}"
                   data-stock="${size.stock}"
                   ${firstSize && !isDisabled ? 'checked' : ''}
                   ${isDisabled ? 'disabled' : ''}>
            <label class="btn btn-outline-dark size-btn ${isDisabled ? 'disabled' : ''}" 
                   for="size-${size.id}">
              ${size.name}
              ${isDisabled ? '<small class="d-block text-danger">Agotado</small>' :
                isLowStock ? `<small class="d-block text-warning">Últimas ${size.stock}</small>` : ''}
            </label>
          </div>
        `;
            if (firstSize && !isDisabled) firstSize = false;
          });
          sizeContainer.innerHTML = sizesHTML;

          sizeContainer.querySelectorAll('input[name="size"]').forEach(input => {
            input.addEventListener('change', updatePriceAndStock);
          });
          updatePriceAndStock();
        }

        function updateProductImage(colorId) {
          const withImg = productVariants.find(v => v.color_id == colorId && v.variant_image_url);
          if (withImg && productImage) {
            const url = withImg.variant_image_url;
            productImage.src = url;
            if (zoomImg) zoomImg.src = url;
            document.querySelectorAll('.thumb-btn').forEach(btn => {
              const same = btn.getAttribute('data-img') === url;
              btn.classList.toggle('active', same);
            });
          }
        }

        function updatePriceAndStock() {
          const selectedColor = document.querySelector('input[name="color"]:checked');
          const selectedSize = document.querySelector('input[name="size"]:checked');
          if (!selectedColor || !selectedSize) return;

          const variant = productVariants.find(v => v.color_id == selectedColor.value && v.size_id == selectedSize.value);
          if (!variant) return;

          const stock = parseInt(variant.stock_quantity);
          if (stockCount) stockCount.textContent = `(${stock} disponibles)`;
          if (stockWarning) {
            if (stock <= 5 && stock > 0) stockWarning.classList.remove('d-none');
            else stockWarning.classList.add('d-none');
          }

          if (totalPriceElement) totalPriceElement.textContent = 'S/ ' + (basePrice).toFixed(2);
          if (priceBreakdownElement) priceBreakdownElement.textContent = `Precio base: S/ ${basePrice.toFixed(2)}`;

          const quantityInput = document.getElementById('quantity');
          if (quantityInput) {
            const minValue = parseInt(quantityInput.min) || 1;
            const step = parseInt(quantityInput.step) || 1;
            const currentQuantity = parseInt(quantityInput.value) || minValue;
            quantityInput.max = stock;
            if (currentQuantity > stock) {
              const adjustedValue = step > 1 ? Math.max(minValue, Math.floor(stock / step) * step) : Math.max(minValue, stock);
              quantityInput.value = adjustedValue;
            }
            const qtyTotal = document.getElementById('quantity-total-display');
            if (qtyTotal) qtyTotal.textContent = 'S/ ' + (basePrice * (parseInt(quantityInput.value) || 1)).toFixed(2);
          }
        }
      });
    </script>
  <?php endif; ?>

  <!-- Bootstrap + Fix acordeón: abre/cierra correctamente -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const acc = document.getElementById('productInfoAccordion');
      if (!acc || typeof bootstrap === 'undefined' || !bootstrap.Collapse) return;
      acc.querySelectorAll('.accordion-button').forEach(btn => {
        const targetSel = btn.getAttribute('data-bs-target');
        const target = targetSel ? document.querySelector(targetSel) : null;
        if (!target) return;
        const instance = bootstrap.Collapse.getOrCreateInstance(target, {
          toggle: false
        });
        btn.addEventListener('click', function(e) {
          e.stopImmediatePropagation();
          e.preventDefault();
          if (target.classList.contains('show')) instance.hide();
          else instance.show();
        });
      });
    });

    /* ====== Zoom PRO: Canvas + Lens, con presets, navegación y atajos ====== */
    (function() {
      const modalEl = document.getElementById('imageModal');
      const stage = document.getElementById('zoomStage');
      const img = document.getElementById('zoomImg');
      const meter = document.getElementById('zoomMeter');
      const btnIn = document.getElementById('zoomIn');
      const btnOut = document.getElementById('zoomOut');
      const btnReset = document.getElementById('zoomReset');
      const btnBg = document.getElementById('zoomBg');
      const btnLens = document.getElementById('zoomLensToggle');
      const btnGrid = document.getElementById('zoomGridToggle');
      const btnTexture = document.getElementById('zoomTextureToggle');
      const grid = document.getElementById('zoomGrid');
      const lens = document.getElementById('zoomLens');
      const btnPrev = document.getElementById('zoomPrev');
      const btnNext = document.getElementById('zoomNext');

      // Galería desde PHP
      const GALLERY = <?php echo json_encode($gallery_images_final ?: []); ?>;
      let gIndex = 0;

      // Estado
      let imgW = 0,
        imgH = 0,
        stageW = 0,
        stageH = 0;
      let baseScale = 1,
        scale = 1,
        minScale = 1,
        maxScale = 5;
      let tx = 0,
        ty = 0; // translate
      let isDragging = false,
        startX = 0,
        startY = 0,
        startTx = 0,
        startTy = 0;

      // Fling/inercia
      let lastMoveTime = 0,
        lastX = 0,
        lastY = 0,
        vx = 0,
        vy = 0,
        rafId = null;

      // Lens
      let lensMode = false,
        lensPower = 2.0,
        lensSize = 220;

      // Fondo: oscuro → neutro → claro
      let bgMode = 0; // 0 dark, 1 neutral, 2 light

      const storeKey = 'zoomState:<?php echo $product_id; ?>';

      function clamp(v, min, max) {
        return Math.max(min, Math.min(max, v));
      }

      function setTransform() {
        img.style.transform = `translate(${tx}px, ${ty}px) scale(${scale})`;
        meter.textContent = Math.round((scale / baseScale) * 100) + '%';
      }

      function contentSize() {
        return {
          w: imgW * scale,
          h: imgH * scale
        };
      }

      function fit() {
        stageW = stage.clientWidth;
        stageH = stage.clientHeight;
        imgW = img.naturalWidth || img.width;
        imgH = img.naturalHeight || img.height;
        if (!imgW || !imgH) return;

        baseScale = Math.min(stageW / imgW, stageH / imgH);
        scale = baseScale;
        minScale = baseScale * 0.8;
        maxScale = baseScale * 5;

        tx = (stageW - imgW * scale) / 2;
        ty = (stageH - imgH * scale) / 2;

        // lens defaults
        lensSize = Math.max(180, Math.min(280, Math.floor(Math.min(stageW, stageH) * 0.28)));
        lens.style.width = lens.style.height = lensSize + 'px';

        setTransform();
      }

      function saveState() {
        const state = {
          gIndex,
          tx,
          ty,
          scale,
          lensMode,
          lensPower,
          bgMode
        };
        try {
          sessionStorage.setItem(storeKey, JSON.stringify(state));
        } catch (e) {}
      }

      function loadState() {
        try {
          const raw = sessionStorage.getItem(storeKey);
          if (!raw) return;
          const s = JSON.parse(raw);
          if (typeof s.gIndex === 'number' && GALLERY[s.gIndex]) {
            gIndex = s.gIndex;
            img.src = GALLERY[gIndex];
          }
          lensMode = !!s.lensMode;
          lens.classList.toggle('show', lensMode);
          btnLens.classList.toggle('active', lensMode);
          lensPower = typeof s.lensPower === 'number' ? s.lensPower : lensPower;
          bgMode = typeof s.bgMode === 'number' ? s.bgMode : 0;
          applyBg();
          // fit first, then apply custom transform
          if (img.complete) afterLoadApply(s);
          else img.onload = () => afterLoadApply(s);
        } catch (e) {}
      }

      function afterLoadApply(s) {
        fit();
        if (typeof s.scale === 'number' && typeof s.tx === 'number' && typeof s.ty === 'number') {
          scale = clamp(s.scale, minScale, maxScale);
          tx = s.tx;
          ty = s.ty;
          setTransform();
        }
      }

      function setScaleAt(stageX, stageY, nextScale) {
        nextScale = clamp(nextScale, minScale, maxScale);
        const beforeX = (stageX - tx) / scale;
        const beforeY = (stageY - ty) / scale;
        scale = nextScale;
        tx = stageX - beforeX * scale;
        ty = stageY - beforeY * scale;
        applyBounds();
        setTransform();
        saveState();
      }

      function applyBounds() {
        const {
          w,
          h
        } = contentSize();
        const minTx = Math.min(0, stageW - w);
        const minTy = Math.min(0, stageH - h);
        const maxTx = Math.max(0, stageW - w);
        const maxTy = Math.max(0, stageH - h);
        tx = clamp(tx, minTx, maxTx);
        ty = clamp(ty, minTy, maxTy);
      }

      function onWheel(e) {
        if (lensMode) {
          // En modo lens: rueda ajusta potencia
          e.preventDefault();
          const delta = -e.deltaY;
          lensPower = clamp(lensPower + (delta > 0 ? 0.15 : -0.15), 1.5, 4.0);
          updateLens(e);
          saveState();
          return;
        }
        e.preventDefault();
        const rect = stage.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const factor = (e.deltaY < 0) ? 1.12 : 0.88;
        setScaleAt(x, y, scale * factor);
      }

      function onDblClick(e) {
        if (lensMode) {
          lensMode = false;
          lens.classList.remove('show');
          btnLens.classList.remove('active');
          saveState();
          return;
        }
        const rect = stage.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const targetScale = (scale <= minScale * 1.05) ? minScale * 2 : minScale; // alternar 2x / ajuste
        setScaleAt(x, y, targetScale);
      }

      // Panning con inercia
      function onPointerDown(e) {
        stage.setPointerCapture(e.pointerId);
        isDragging = true;
        stage.classList.add('grabbing');
        startX = e.clientX;
        startY = e.clientY;
        startTx = tx;
        startTy = ty;
        lastX = e.clientX;
        lastY = e.clientY;
        lastMoveTime = performance.now();
        vx = vy = 0;
      }

      function onPointerMove(e) {
        if (!isDragging) {
          if (lensMode) updateLens(e);
          return;
        }
        const now = performance.now();
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        tx = startTx + dx;
        ty = startTy + dy;
        setTransform();

        // velocidad para inercia
        const dt = now - lastMoveTime;
        if (dt > 0) {
          vx = (e.clientX - lastX) / dt;
          vy = (e.clientY - lastY) / dt;
        }
        lastX = e.clientX;
        lastY = e.clientY;
        lastMoveTime = now;
      }

      function onPointerUp(e) {
        if (!isDragging) return;
        isDragging = false;
        stage.classList.remove('grabbing');
        // inercia/fling
        const friction = 0.95;
        const step = () => {
          if (Math.hypot(vx, vy) < 0.01) {
            cancelAnimationFrame(rafId);
            applyBounds();
            setTransform();
            saveState();
            return;
          }
          tx += vx * 16;
          ty += vy * 16;
          applyBounds();
          setTransform();
          vx *= friction;
          vy *= friction;
          rafId = requestAnimationFrame(step);
        };
        rafId = requestAnimationFrame(step);
      }

      // Lens handling
      function updateLens(e) {
        const rect = stage.getBoundingClientRect();
        const cx = e.clientX - rect.left;
        const cy = e.clientY - rect.top;
        const half = lensSize / 2;
        lens.style.left = (cx - half) + 'px';
        lens.style.top = (cy - half) + 'px';

        const zoomBackgroundScale = lensPower * 100;
        lens.style.backgroundImage = `url('${img.currentSrc || img.src}')`;
        lens.style.backgroundSize = `${imgW*scale*lensPower}px ${imgH*scale*lensPower}px`;

        // Determinar posición relativa dentro de la imagen
        const imgX = (cx - tx) / scale;
        const imgY = (cy - ty) / scale;

        const bgPosX = -(imgX * lensPower - half);
        const bgPosY = -(imgY * lensPower - half);
        lens.style.backgroundPosition = `${bgPosX}px ${bgPosY}px`;
      }

      // UI
      function applyBg() {
        stage.classList.remove('bg-darkish', 'bg-neutral', 'bg-lightish');
        if (bgMode === 0) stage.classList.add('bg-darkish');
        else if (bgMode === 1) stage.classList.add('bg-neutral');
        else stage.classList.add('bg-lightish');
      }

      btnBg.addEventListener('click', () => {
        bgMode = (bgMode + 1) % 3;
        applyBg();
        saveState();
      });
      btnLens.addEventListener('click', () => {
        lensMode = !lensMode;
        lens.classList.toggle('show', lensMode);
        btnLens.classList.toggle('active', lensMode);
        saveState();
      });
      btnGrid.addEventListener('click', () => {
        grid.classList.toggle('show');
        btnGrid.classList.toggle('active');
      });
      btnTexture.addEventListener('click', () => {
        img.classList.toggle('zoom-texture-boost');
        btnTexture.classList.toggle('active');
      });

      btnIn.addEventListener('click', () => {
        setScaleAt(stage.clientWidth / 2, stage.clientHeight / 2, scale * 1.2);
      });
      btnOut.addEventListener('click', () => {
        setScaleAt(stage.clientWidth / 2, stage.clientHeight / 2, scale / 1.2);
      });
      btnReset.addEventListener('click', () => {
        fit();
        saveState();
      });

      // Presets 1x..4x
      document.querySelectorAll('.zoom-btn[data-preset]').forEach(b => {
        b.addEventListener('click', () => {
          const mult = parseInt(b.getAttribute('data-preset')) || 1;
          setScaleAt(stage.clientWidth / 2, stage.clientHeight / 2, baseScale * mult);
        });
      });

      // Navegación galería
      function showIndex(i) {
        if (!GALLERY.length) return;
        gIndex = (i + GALLERY.length) % GALLERY.length;
        img.src = GALLERY[gIndex];
        // esperar carga para recalcular y conservar estado relativo
        img.onload = () => {
          fit();
          saveState();
        };
      }
      btnPrev.addEventListener('click', () => showIndex(gIndex - 1));
      btnNext.addEventListener('click', () => showIndex(gIndex + 1));

      // Mouse wheel / dblclick
      stage.addEventListener('wheel', onWheel, {
        passive: false
      });
      stage.addEventListener('dblclick', onDblClick);

      // Pointer events unificados
      stage.addEventListener('pointerdown', onPointerDown);
      window.addEventListener('pointermove', onPointerMove);
      window.addEventListener('pointerup', onPointerUp);

      // Teclado
      function onKey(e) {
        if (!modalEl.classList.contains('show')) return;
        if (e.key === '+' || e.key === '=') {
          setScaleAt(stage.clientWidth / 2, stage.clientHeight / 2, scale * 1.2);
        } else if (e.key === '-') {
          setScaleAt(stage.clientWidth / 2, stage.clientHeight / 2, scale / 1.2);
        } else if (e.key === '0') {
          fit();
        } else if (e.key === '1') {
          setScaleAt(stage.clientWidth / 2, stage.clientHeight / 2, baseScale * 1);
        } else if (e.key === '2') {
          setScaleAt(stage.clientWidth / 2, stage.clientHeight / 2, baseScale * 2);
        } else if (e.key === '3') {
          setScaleAt(stage.clientWidth / 2, stage.clientHeight / 2, baseScale * 3);
        } else if (e.key === '4') {
          setScaleAt(stage.clientWidth / 2, stage.clientHeight / 2, baseScale * 4);
        } else if (e.key.toLowerCase() === 'l') {
          btnLens.click();
        } else if (e.key.toLowerCase() === 'g') {
          btnGrid.click();
        } else if (e.key.toLowerCase() === 't') {
          btnTexture.click();
        } else if (e.key === 'ArrowLeft') {
          btnPrev.click();
        } else if (e.key === 'ArrowRight') {
          btnNext.click();
        }
      }
      window.addEventListener('keydown', onKey);

      // Sincronizar imagen mostrada en el detalle con el modal
      modalEl.addEventListener('show.bs.modal', () => {
        const main = document.getElementById('productImage');
        if (main && main.currentSrc) {
          const current = main.currentSrc;
          img.src = current;
          const i = GALLERY.indexOf(current);
          gIndex = i >= 0 ? i : 0;
        }
      });

      modalEl.addEventListener('shown.bs.modal', () => {
        // Ajustar al contenedor y cargar estado si existe
        if (img.complete) {
          fit();
          loadState();
        } else {
          img.onload = () => {
            fit();
            loadState();
          };
        }
      });

      modalEl.addEventListener('hidden.bs.modal', () => {
        saveState();
      });

      // Reajustar en resize
      window.addEventListener('resize', () => {
        if (modalEl.classList.contains('show')) fit();
      });
    })();
  </script>
</body>

</html>