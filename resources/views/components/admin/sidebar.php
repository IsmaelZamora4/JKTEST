<?php

?>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                   href="dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <?php $isProducts = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/products/') !== false; ?>
                <a class="nav-link <?php echo $isProducts ? 'active' : ''; ?>" 
                   href="products">
                    <i class="fas fa-box"></i> Productos
                </a>
            </li>
            
            <li class="nav-item">
                <?php $isCategories = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/categories/') !== false; ?>
                <a class="nav-link <?php echo $isCategories ? 'active' : ''; ?>" 
                   href="categories">
                    <i class="fas fa-tags"></i> Categorías
                </a>
            </li>
            
            <li class="nav-item">
                <?php $isOrders = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/orders/') !== false; ?>
                <a class="nav-link <?php echo $isOrders ? 'active' : ''; ?>" 
                   href="orders">
                    <i class="fas fa-shopping-cart"></i> Pedidos
                </a>
            </li>
            
            <li class="nav-item">
                <?php $isCustomers = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/customers/') !== false; ?>
                <a class="nav-link <?php echo $isCustomers ? 'active' : ''; ?>" 
                   href="customers">
                    <i class="fas fa-users"></i> Clientes
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Configuración</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <?php $isSizes = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/sizes/') !== false; ?>
                <a class="nav-link <?php echo $isSizes ? 'active' : ''; ?>" 
                   href="sizes">
                    <i class="fas fa-ruler"></i> Tallas
                </a>
            </li>
            
            <li class="nav-item">
                <?php $isColors = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/colors/') !== false; ?>
                <a class="nav-link <?php echo $isColors ? 'active' : ''; ?>" 
                   href="colors">
                    <i class="fas fa-palette"></i> Colores
                </a>
            </li>
            
            <li class="nav-item">
                <?php $isVariants = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/variants/') !== false; ?>
                <a class="nav-link <?php echo $isVariants ? 'active' : ''; ?>" 
                   href="variants">
                    <i class="fas fa-layer-group"></i> Variantes
                </a>
            </li>
            
            <li class="nav-item">
                <?php $isProductSizes = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/product-sizes/') !== false; ?>
                <a class="nav-link <?php echo $isProductSizes ? 'active' : ''; ?>" 
                   href="product-sizes">
                    <i class="fas fa-ruler-combined"></i> Tallas de Productos
                </a>
            </li>
            
            <li class="nav-item">
                <?php $isWholesale = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/wholesale/') !== false; ?>
                <a class="nav-link <?php echo $isWholesale ? 'active' : ''; ?>" 
                   href="wholesale">
                    <i class="fas fa-percentage"></i> Reglas Mayoristas
                </a>
            </li>
            
            <li class="nav-item">
                <?php $isServices = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/services/') !== false; ?>
                <a class="nav-link <?php echo $isServices ? 'active' : ''; ?>" 
                   href="services">
                    <i class="fas fa-tools"></i> Servicios
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Tienda</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="/" target="_blank">
                    <i class="fas fa-external-link-alt"></i> Ver Tienda
                </a>
            </li>
        </ul>
    </div>
</nav>

<style>
.sidebar {
    position: fixed;
    top: 56px;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 48px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
}

.sidebar-sticky {
    position: relative;
    top: 0;
    height: calc(100vh - 48px);
    padding-top: .5rem;
    overflow-x: hidden;
    overflow-y: auto;
}

.sidebar .nav-link {
    font-weight: 500;
    color: #333;
    padding: 10px 15px;
    border-radius: 5px;
    margin: 2px 10px;
    transition: all 0.3s ease;
}

.sidebar .nav-link:hover {
    color: #007bff;
    background-color: #f8f9fa;
}

.sidebar .nav-link.active {
    color: #007bff;
    background-color: #e3f2fd;
}

.sidebar .nav-link i {
    margin-right: 8px;
    width: 16px;
    text-align: center;
}

.sidebar-heading {
    font-size: .75rem;
    text-transform: uppercase;
}

@media (max-width: 767.98px) {
    .sidebar {
        top: 5rem;
    }
}
</style>

