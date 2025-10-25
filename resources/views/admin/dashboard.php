<?php
require_once BASE_PATH . 'config/config.php';
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'classes/Auth.php';
require_once BASE_PATH . 'classes/Product.php';
require_once BASE_PATH . 'classes/Category.php';
require_once BASE_PATH . 'classes/Order.php';
require_once BASE_PATH . 'classes/Size.php';
require_once BASE_PATH . 'classes/Color.php';
require_once BASE_PATH . 'classes/ProductVariant.php';
require_once BASE_PATH . 'classes/WholesaleRule.php';
require_once BASE_PATH . 'classes/Service.php';

// Inicializar conexión a la base de datos
$database = new Database();
$pdo = $database->getConnection();

$auth = new Auth($pdo);
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$current_user = $auth->getCurrentUser();

$product = new Product($pdo);
$category = new Category($pdo);
$order = new Order($pdo);
$size = new Size($pdo);
$color = new Color($pdo);
$variant = new ProductVariant($pdo);
$wholesaleRule = new WholesaleRule($pdo);
$service = new Service($pdo);

// Obtener estadísticas generales
$total_products = count($product->getAll(1, 1000, '', null));
$active_products = count($product->getAll(1, 1000, '', null, true));
$categories = $category->getAll();
$total_categories = count($categories);

// Obtener estadísticas de pedidos
$pending_orders = 0; // Placeholder - implementar cuando Order class tenga getByStatus
$total_orders = 0; // Placeholder - implementar cuando Order class tenga getAll mejorado
$completed_orders = 0; // Placeholder - implementar cuando Order class tenga getByStatus

// Obtener estadísticas de configuración
$total_sizes = count($size->getAll());
$total_colors = count($color->getAll());
$total_variants = count($variant->getAllWithDetails());
$total_wholesale_rules = count($wholesaleRule->getAll());
$total_services = count($service->getAll());

// Obtener productos recientes
$recent_products = $product->getAll(1, 5, '', null);

// Obtener pedidos recientes
$recent_orders = []; // Placeholder - implementar cuando Order class esté completa
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Panel Administrativo | <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/public/assets/css/admin.css" rel="stylesheet">
</head>

<body>
    <?php include COMPONENT_PATH . 'admin/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include COMPONENT_PATH . 'admin/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-download"></i> Exportar
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                    <li><a class="dropdown-item" href="export.php?type=products" target="_blank">
                                            <i class="fas fa-box me-2"></i>Productos
                                        </a></li>
                                    <li><a class="dropdown-item" href="export.php?type=categories" target="_blank">
                                            <i class="fas fa-tags me-2"></i>Categorías
                                        </a></li>
                                    <li><a class="dropdown-item" href="export.php?type=variants" target="_blank">
                                            <i class="fas fa-layer-group me-2"></i>Variantes
                                        </a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="export.php?type=dashboard" target="_blank">
                                            <i class="fas fa-chart-bar me-2"></i>Resumen Dashboard
                                        </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjetas de estadísticas principales -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-start border-primary border-4 shadow h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                            Total Productos
                                        </div>
                                        <div class="h4 mb-0 fw-bold text-dark"><?php echo $total_products; ?></div>
                                        <small class="text-success">
                                            <i class="fas fa-arrow-up"></i> <?php echo $active_products; ?> activos
                                        </small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-box fa-2x text-primary opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-start border-success border-4 shadow h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                            Categorías
                                        </div>
                                        <div class="h4 mb-0 fw-bold text-dark"><?php echo $total_categories; ?></div>
                                        <small class="text-muted">
                                            <i class="fas fa-tags"></i> Organizadas
                                        </small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-tags fa-2x text-success opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-start border-info border-4 shadow h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                            Variantes
                                        </div>
                                        <div class="h4 mb-0 fw-bold text-dark"><?php echo $total_variants; ?></div>
                                        <small class="text-muted">
                                            <i class="fas fa-layer-group"></i> Combinaciones
                                        </small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-layer-group fa-2x text-info opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-start border-warning border-4 shadow h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                            Pedidos Pendientes
                                        </div>
                                        <div class="h4 mb-0 fw-bold text-dark"><?php echo $pending_orders; ?></div>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> Por procesar
                                        </small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-shopping-cart fa-2x text-warning opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjetas de configuración -->
                <div class="row mb-4">
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-ruler fa-2x text-primary mb-2"></i>
                                <h5 class="card-title mb-1"><?php echo $total_sizes; ?></h5>
                                <p class="card-text small text-muted">Tallas</p>
                                <a href="sizes/index.php" class="btn btn-sm btn-outline-primary">Gestionar</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-palette fa-2x text-info mb-2"></i>
                                <h5 class="card-title mb-1"><?php echo $total_colors; ?></h5>
                                <p class="card-text small text-muted">Colores</p>
                                <a href="colors/index.php" class="btn btn-sm btn-outline-info">Gestionar</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-percentage fa-2x text-success mb-2"></i>
                                <h5 class="card-title mb-1"><?php echo $total_wholesale_rules; ?></h5>
                                <p class="card-text small text-muted">Reglas Mayoristas</p>
                                <a href="wholesale/index.php" class="btn btn-sm btn-outline-success">Gestionar</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-tools fa-2x text-warning mb-2"></i>
                                <h5 class="card-title mb-1"><?php echo $total_services; ?></h5>
                                <p class="card-text small text-muted">Servicios</p>
                                <a href="services/index.php" class="btn btn-sm btn-outline-warning">Gestionar</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-shopping-cart fa-2x text-danger mb-2"></i>
                                <h5 class="card-title mb-1"><?php echo $total_orders; ?></h5>
                                <p class="card-text small text-muted">Total Pedidos</p>
                                <a href="orders/index.php" class="btn btn-sm btn-outline-danger">Ver Todos</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <h5 class="card-title mb-1"><?php echo $completed_orders; ?></h5>
                                <p class="card-text small text-muted">Completados</p>
                                <a href="orders/index.php?status=completed" class="btn btn-sm btn-outline-success">Ver</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Productos recientes -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-box"></i> Productos Recientes
                                </h6>
                                <a href="products/index.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Ver Todos
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Imagen</th>
                                                <th>Nombre</th>
                                                <th>Precio Base</th>
                                                <th>Variantes</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_products as $prod): ?>
                                                <tr>
                                                    <td>
                                                        <?php if (!empty($prod['image_url'])): ?>
                                                            <img src="../<?php echo htmlspecialchars($prod['image_url']); ?>"
                                                                alt="<?php echo htmlspecialchars($prod['name']); ?>"
                                                                class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="bg-light d-flex align-items-center justify-content-center"
                                                                style="width: 50px; height: 50px;">
                                                                <i class="fas fa-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($prod['name']); ?></td>
                                                    <td>S/ <?php echo number_format($prod['base_price'] ?? 0, 2); ?></td>
                                                    <td>
                                                        <?php if ($prod['has_variants']): ?>
                                                            <span class="badge bg-info">
                                                                <i class="fas fa-palette"></i> Con variantes
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">
                                                                <i class="fas fa-ruler"></i> Solo tallas
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($prod['is_active']): ?>
                                                            <span class="badge bg-success">Activo</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Inactivo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="products/edit.php?id=<?php echo $prod['id']; ?>"
                                                            class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-tags"></i> Categorías
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php foreach ($categories as $cat): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span><?php echo htmlspecialchars($cat['name']); ?></span>
                                        <a href="categories/index.php" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                                <hr>
                                <a href="categories/index.php" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-plus"></i> Gestionar Categorías
                                </a>
                            </div>
                        </div>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-chart-pie"></i> Acciones Rápidas
                                </h6>
                            </div>
                            <div class="card-body">
                                <a href="products/create.php" class="btn btn-success btn-sm w-100 mb-2">
                                    <i class="fas fa-plus"></i> Agregar Producto
                                </a>
                                <a href="products/index.php" class="btn btn-primary btn-sm w-100 mb-2">
                                    <i class="fas fa-box"></i> Ver Productos
                                </a>
                                <a href="variants/index.php" class="btn btn-info btn-sm w-100 mb-2">
                                    <i class="fas fa-layer-group"></i> Gestionar Variantes
                                </a>
                                <a href="orders/index.php" class="btn btn-warning btn-sm w-100">
                                    <i class="fas fa-shopping-cart"></i> Ver Pedidos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>