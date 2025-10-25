<?php
require_once BASE_PATH . 'config/config.php';
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'classes/Product.php';
require_once BASE_PATH . 'classes/Category.php';

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);
$category = new Category($db);

// Helper: assets con versionado
function asset_url_with_v($path)
{
    if (!$path) return '';
    $path = str_replace('\\', '/', $path);
    if (preg_match('#^https?://#i', $path)) return $path;
    $fs = __DIR__ . '/' . ltrim($path, '/');
    return file_exists($fs) ? $path . '?v=' . filemtime($fs) : $path;
}

/**
 * Construye el “catálogo” para el configurador:
 * Para cada producto carga:
 * - imagen base
 * - lista de colores (si tiene variantes) con una imagen de ese color (si existe)
 * - tallas por color (si tiene variantes) o por producto (product_sizes)
 */
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
            // Tallas globales (para fallback)
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

// Cargar catálogos DINÁMICOS desde productos existentes (sin usar un “producto Pack”)
$q = $db->prepare("
  SELECT p.id, p.name, p.image_url, p.base_price, p.has_variants
  FROM products p
  JOIN categories c ON p.category_id = c.id
  WHERE LOWER(c.name) LIKE :cat AND p.is_active=1
  ORDER BY p.id DESC
  LIMIT 200
");
$likePolos = '%unidad - polos%';
$q->bindParam(':cat', $likePolos);
$q->execute();
$CAT_POLOS = add_sizes_to_rows_pack_mixed($db, $q->fetchAll(PDO::FETCH_ASSOC));

$likePoleras = '%unidad - poleras%';
$q->bindParam(':cat', $likePoleras);
$q->execute();
$CAT_POLERAS = add_sizes_to_rows_pack_mixed($db, $q->fetchAll(PDO::FETCH_ASSOC));

$likeJeans = '%unidad - jeans%';
$q->bindParam(':cat', $likeJeans);
$q->execute();
$CAT_JEANS = add_sizes_to_rows_pack_mixed($db, $q->fetchAll(PDO::FETCH_ASSOC));

// Slots: Polera, Polo, Jean
$pack_slots = [
    ['type' => 'polera', 'label' => 'Polera'],
    ['type' => 'polo',   'label' => 'Polo'],
    ['type' => 'jean',   'label' => 'Jean'],
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arma tu Pack - <?php echo APP_NAME; ?></title>
    <meta name="description" content="Crea tu outfit: elige Polera, Polo y Jean con sus tallas y colores.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/variables.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    <link href="assets/css/components.css" rel="stylesheet">
    <link href="assets/css/layout.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <link href="assets/css/utilities.css" rel="stylesheet">
    <style>
        /* ====== Look profesional con dorados y previsualización borrosa (blur) ====== */
        :root {
            --gold: #d4af37;
            --gold-light: #f6d365;
            --coffee: #3b2321;
        }

        body {
            background: linear-gradient(180deg, #fafafa 0, #fff 180px)
        }

        .page-header-minimal h1 {
            font-weight: 800
        }

        /* contenedor */
        .pack-grid {
            background: #fff;
            border: 0;
            border-radius: 18px;
            padding: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .08)
        }

        /* slot */
        .pack-slot {
            position: relative;
            background: #fff;
            border: 1px solid rgba(212, 175, 55, .25);
            border-radius: 16px;
            padding: 14px;
            aspect-ratio: 3/4;
            min-height: 360px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden
        }

        .pack-slot img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            display: block;
            z-index: 2
        }

        /* Edge blur previews */
        .edge-blur {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 34%;
            pointer-events: none;
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            filter: blur(10px);
            transform: scale(1.04);
            opacity: .40;
            z-index: 1
        }

        .edge-blur.left {
            left: -6%;
            -webkit-mask-image: linear-gradient(to right, rgba(0, 0, 0, 1), rgba(0, 0, 0, 0));
            mask-image: linear-gradient(to right, rgba(0, 0, 0, 1), rgba(0, 0, 0, 0))
        }

        .edge-blur.right {
            right: -6%;
            -webkit-mask-image: linear-gradient(to left, rgba(0, 0, 0, 1), rgba(0, 0, 0, 0));
            mask-image: linear-gradient(to left, rgba(0, 0, 0, 1), rgba(0, 0, 0, 0))
        }

        /* flechas */
        .pack-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 1px solid rgba(212, 175, 55, .6);
            background: linear-gradient(135deg, var(--gold-light), var(--gold));
            color: #111;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 18px rgba(212, 175, 55, .35);
            z-index: 3
        }

        .pack-arrow.left {
            left: 10px
        }

        .pack-arrow.right {
            right: 10px
        }

        .pack-arrow:hover {
            filter: brightness(.96)
        }

        /* Info debajo */
        .pack-info {
            text-align: center
        }

        .pack-info .tag {
            display: inline-flex;
            gap: 6px;
            align-items: center;
            font-weight: 700;
            color: #111
        }

        /* colores y tallas */
        .pack-colors .color-dot {
            display: inline-block;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 1px solid rgba(0, 0, 0, .15);
            cursor: pointer;
            margin: 0 6px 6px 0
        }

        .pack-colors .color-dot.active {
            outline: 2px solid var(--gold);
            outline-offset: 2px
        }

        .pack-sizes .btn {
            margin: 4px 6px 0 0;
            border-radius: 10px
        }

        .pack-sizes .btn-check:checked+.btn,
        .pack-sizes .btn.active {
            background: linear-gradient(135deg, var(--gold-light), var(--gold));
            color: #111;
            border-color: var(--gold)
        }

        /* cantidad */
        .pack-qty {
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: center
        }

        .pack-qty .qty-btn {
            width: 36px;
            height: 36px;
            border: 1px solid rgba(212, 175, 55, .6);
            background: #fff;
            border-radius: 8px;
            font-weight: 700;
            color: #111
        }

        .pack-qty input[type="number"] {
            width: 80px;
            text-align: center;
            border-radius: 8px;
            border: 1px solid #e3e3e3
        }

        /* resumen inferior */
        .price-summary-pack {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            border: 1px dashed rgba(212, 175, 55, .45);
            border-radius: 14px;
            padding: 16px
        }

        .btn-pack {
            background: linear-gradient(135deg, var(--gold-light), var(--gold));
            color: #111;
            border: 0;
            border-radius: 10px;
            padding: 12px 18px;
            font-weight: 800;
            letter-spacing: .3px;
            box-shadow: 0 6px 18px rgba(212, 175, 55, .35)
        }

        .btn-pack:hover {
            filter: brightness(.96);
            color: #111
        }

        /* micro copy */
        .micro {
            font-size: .85rem;
            color: #6c757d
        }

        @media (max-width:768px) {
            .pack-slot {
                min-height: 300px
            }
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
                    <li class="breadcrumb-item active breadcrumb-current" aria-current="page">Arma tu Pack</li>
                </ol>
            </nav>
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <h1 class="mb-1 fw-bold">Crea tu outfit</h1>
                    <p class="text-muted mb-0">Elige una Polera, un Polo y un Jean. Personaliza color, talla y cantidad por prenda.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-4">
        <div class="container">
            <div class="card border-0 shadow-sm pack-grid">
                <div class="card-header bg-dark text-white">
                    <strong><i class="fa-solid fa-sliders me-2"></i>Personaliza tu pack</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($pack_slots as $idx => $slot): ?>
                            <div class="col-12 col-md-4">
                                <div class="pack-slot">
                                    <button class="pack-arrow left" data-slot="<?php echo $idx; ?>"><i class="fa-solid fa-chevron-left"></i></button>
                                    <div class="edge-blur left" id="packBlurL-<?php echo $idx; ?>"></div>
                                    <img id="packImg-<?php echo $idx; ?>" alt="<?php echo htmlspecialchars($slot['label']); ?>" loading="lazy">
                                    <div class="edge-blur right" id="packBlurR-<?php echo $idx; ?>"></div>
                                    <button class="pack-arrow right" data-slot="<?php echo $idx; ?>"><i class="fa-solid fa-chevron-right"></i></button>
                                </div>

                                <div class="pack-info mt-2">
                                    <?php $iconClass = ($slot['type'] === 'jean' ? 'fa-user' : 'fa-shirt'); ?>
                                    <div class="tag"><i class="fa-solid <?php echo $iconClass; ?> me-1"></i><?php echo htmlspecialchars($slot['label']); ?>:&nbsp;<span id="packName-<?php echo $idx; ?>" class="text-muted fw-normal"></span></div>
                                    <div class="fw-semibold">S/ <span id="packPrice-<?php echo $idx; ?>"></span></div>
                                </div>

                                <div class="mt-2 pack-info">
                                    <label class="form-label fw-bold mb-1">Color</label>
                                    <div class="pack-colors d-inline-block" id="packColors-<?php echo $idx; ?>"></div>
                                    <div><small id="packColorHelp-<?php echo $idx; ?>" class="text-muted micro"></small></div>
                                </div>

                                <div class="mt-2 pack-info">
                                    <label class="form-label fw-bold mb-1">Talla</label>
                                    <div class="pack-sizes d-inline-block" id="packSizes-<?php echo $idx; ?>"></div>
                                    <div><small id="packSizeHelp-<?php echo $idx; ?>" class="text-muted micro"></small></div>
                                </div>

                                <div class="mt-2 pack-info">
                                    <label class="form-label fw-bold mb-1">Cantidad</label>
                                    <div class="pack-qty">
                                        <button type="button" class="qty-btn" data-slot="<?php echo $idx; ?>" data-dir="-1">-</button>
                                        <input type="number" class="form-control form-control-sm" id="packQty-<?php echo $idx; ?>" value="1" min="1" step="1">
                                        <button type="button" class="qty-btn" data-slot="<?php echo $idx; ?>" data-dir="1">+</button>
                                    </div>
                                    <small id="packQtyHelp-<?php echo $idx; ?>" class="text-muted micro"></small>
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
                    <small class="text-muted d-block micro">El total se calcula como la suma de los productos elegidos (no hay precio fijo de pack).</small>
                </div>
            </div>
        </div>
    </section>

    <?php include COMPONENT_PATH . 'footer.php'; ?>

    <script>
        // Datos desde PHP
        const CAT_POLOS = <?php echo json_encode($CAT_POLOS,   JSON_UNESCAPED_UNICODE); ?>;
        const CAT_POLERAS = <?php echo json_encode($CAT_POLERAS, JSON_UNESCAPED_UNICODE); ?>;
        const CAT_JEANS = <?php echo json_encode($CAT_JEANS,   JSON_UNESCAPED_UNICODE); ?>;
        const PACK_SLOTS = <?php echo json_encode($pack_slots,  JSON_UNESCAPED_UNICODE); ?>;

        const PACK_HIDE_OOS = true;
        const PACK_MIN_QTY = 1;
        const PACK_STEP_QTY = 1;
        const PACK_REQUIRE_COLOR = true;

        // Estado por slot
        const state = PACK_SLOTS.map(s => ({
            type: s.type,
            data: (s.type === 'jean' ? CAT_JEANS : (s.type === 'polo' ? CAT_POLOS : CAT_POLERAS)),
            i: 0,
            size_id: null,
            color_id: null,
            qty: 1
        }));

        // Helpers
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

        function primaryImageFor(it) {
            if (!it) return '';
            let url = it.image_disp || it.image_url || '';
            if (it.colors && it.colors.length) {
                const c = it.colors[0];
                if (c && c.image_url) url = c.image_url;
            }
            return url || '';
        }

        // Render de colores
        function renderColors(k, item) {
            const wrap = document.getElementById('packColors-' + k);
            const help = document.getElementById('packColorHelp-' + k);
            if (!wrap) return;
            wrap.innerHTML = '';
            if (!item.colors || !item.colors.length) {
                wrap.innerHTML = '<div class="text-muted micro">N/A</div>';
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
                    const img = document.getElementById('packImg-' + k);
                    if (col && col.image_url && img) img.src = col.image_url;
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
                wrap.innerHTML = '<div class="text-muted micro">Sin tallas disponibles por stock.</div>';
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
                const low = (sz.stock_quantity > 0 && sz.stock_quantity <= 5);
                wrap.insertAdjacentHTML('beforeend', `
      <input type="radio" class="btn-check" name="packsz-${k}" id="${uid}" ${disabled} ${checked}>
      <label class="btn btn-outline-dark ${checked?'active':''}" for="${uid}" data-size="${idForCart}" data-stock="${sz.stock_quantity}">
        ${sz.name}${low?`<small class="d-block text-warning micro">Últimas ${sz.stock_quantity}</small>`:''}
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
                    // toggle visual active
                    wrap.querySelectorAll('label.btn').forEach(b => b.classList.remove('active'));
                    lbl.classList.add('active');
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
            let val = parseInt(qtyInput.value) || PACK_MIN_QTY;
            if (val < PACK_MIN_QTY) val = PACK_MIN_QTY;
            if (val > stock) val = stock - (stock % PACK_STEP_QTY || 0);
            if (val < PACK_MIN_QTY) val = PACK_MIN_QTY;
            qtyInput.value = val;
            state[k].qty = val;
            if (qtyHelp) qtyHelp.textContent = `Máx: ${stock}`;
        }

        function renderSlot(k) {
            const s = state[k];
            if (!s.data || !s.data.length) return;
            const it = s.data[s.i];

            // imagen preferente por color
            let imgUrl = it.image_disp || it.image_url || '';
            if (it.colors && it.colors.length) {
                if (!state[k].color_id) state[k].color_id = String(it.colors[0].id);
                const col = currentColorObj(it, k);
                if (col && col.image_url) imgUrl = col.image_url;
            }
            const imgEl = document.getElementById('packImg-' + k);
            if (imgEl && imgUrl) imgEl.src = imgUrl;

            // edge blur previews (prev/next)
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

            // textos
            const nameEl = document.getElementById('packName-' + k);
            if (nameEl) nameEl.textContent = it.name || '';
            const priceEl = document.getElementById('packPrice-' + k);
            if (priceEl) priceEl.textContent = (parseFloat(it.base_price) || 0).toFixed(2);

            renderColors(k, it);
            renderSizes(k, it);

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

        // Navegación flechas
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.pack-arrow').forEach(btn => {
                btn.addEventListener('click', () => {
                    const k = parseInt(btn.getAttribute('data-slot'));
                    const s = state[k];
                    if (!s || !s.data || !s.data.length) return;
                    const dir = btn.classList.contains('right') ? +1 : -1;
                    s.i = (s.i + dir + s.data.length) % s.data.length;
                    const it = getItem(k);
                    s.color_id = (it && it.colors && it.colors.length) ? String(it.colors[0].id) : null;
                    s.size_id = null;
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

            // Agregar al carrito
            document.getElementById('btnAddPack').addEventListener('click', async () => {
                // Validación
                for (let idx = 0; idx < state.length; idx++) {
                    const it = getItem(idx);
                    if (!it) {
                        alert('Producto inválido en el pack');
                        return;
                    }
                    if (PACK_REQUIRE_COLOR && it.colors && it.colors.length && !state[idx].color_id) {
                        alert(`Selecciona el color para "${it.name}"`);
                        return;
                    }
                    if (!state[idx].size_id) {
                        alert(`Selecciona la talla para "${it.name}"`);
                        return;
                    }
                    const qtyInput = document.getElementById('packQty-' + idx);
                    const qty = parseInt(qtyInput?.value) || 0;
                    if (qty < PACK_MIN_QTY) {
                        alert(`La cantidad para "${it.name}" debe ser mínimo ${PACK_MIN_QTY}.`);
                        return;
                    }
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
                    if (sizeId) fd.append('size_id', sizeId);
                    if (colorId) fd.append('color_id', colorId);
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
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>