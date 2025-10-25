<?php
// Obtener el nombre de la página actual para marcar como activa
$current_page = basename($_SERVER['PHP_SELF']);
$isProductsContext = in_array($current_page, ['products.php', 'arma-tu-pack.php']); // Productos y Arma tu Pack comparten menú
$isPersonalizados = ($current_page === 'personalizados.php');
$isCatalogs       = ($current_page === 'catalogs.php');

// Función helper para asset_url_with_v si no está definida
if (!function_exists('asset_url_with_v')) {
    function asset_url_with_v($path)
    {
        if (!$path) return '';
        $path = str_replace('\\', '/', $path);
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $fs = __DIR__ . '/../' . ltrim($path, '/');
        if (file_exists($fs)) {
            return $path . '?v=' . filemtime($fs);
        }
        return $path;
    }
}

// Variables para el buscador
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
?>

<!-- Texto promocional -->
<div class="promo-bar">
    <div class="container">
        <div class="promo-content">
            <span>✨ Buenos precios en productos textiles personalizados • Delivery disponible a todo el Perú ✨</span>
        </div>
    </div>
</div>

<!-- Header JK Jackets -->
<header class="site-header">
    <!-- Header Top: Logo + Buscador + Teléfono + Carrito -->
    <div class="header-top">
        <div class="container">
            <div class="header-top-content">
                <!-- Mobile menu button (left on small screens) - SOLO EN MÓVILES -->
                <button class="navbar-toggler mobile-menu-btn" type="button" aria-controls="mainMenu" aria-expanded="false" aria-label="Abrir menú">
                    <span class="hamburger">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>

                <!-- Logo -->
                <a class="navbar-brand d-flex align-items-center" href="index.php">
                    <img src="<?php echo asset_url_with_v('assets/logo.gif'); ?>" alt="<?php echo defined('APP_NAME') ? APP_NAME : 'JK Jackets'; ?>" class="logo">
                </a>

                <!-- Buscador -->
                <form class="search-form" method="GET" action="products.php" role="search">
                    <div class="input-group">
                        <input class="form-control" type="search" name="search"
                            placeholder="Buscar productos..."
                            value="<?php echo htmlspecialchars($search ?? ''); ?>" aria-label="Buscar productos">
                        <button class="btn" type="submit" aria-label="Buscar" style="background: #f0bd36 !important; color: #000000 !important; border: none !important; display: flex !important; align-items: center !important; justify-content: center !important;">
                            <i class="fas fa-search" style="color: #000000 !important; font-size: 1rem !important; display: block !important;"></i>
                        </button>
                    </div>
                </form>

                <!-- Controles derecha: Carrito + WhatsApp + Teléfono -->
                <div class="d-flex align-items-center gap-3 header-controls">
                    <!-- Carrito (primero) -->
                    <a href="cart" class="cart-btn" aria-label="Ver carrito">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count" id="cart-count">0</span>
                        <span class="cart-amount">S/ <span id="cart-total">0.00</span></span>
                    </a>

                    <!-- WhatsApp (segundo) -->
                    <a href="https://wa.me/51999977257?text=Hola%2C%20me%20interesa%20conocer%20m%C3%A1s%20sobre%20sus%20productos%20textiles" target="_blank" class="whatsapp-btn" aria-label="Contactar por WhatsApp">
                        <i class="fab fa-whatsapp"></i>
                    </a>

                    <!-- Teléfono (tercero) -->
                    <a href="tel:+51999977257" class="phone-btn" aria-label="Llamar por teléfono">
                        <i class="fas fa-phone"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Header Bottom: Navbar -->
    <div class="header-bottom">
        <div class="container">
            <nav class="navbar navbar-expand-lg">
                <!-- Navigation Menu -->
                <div class="collapse navbar-collapse" id="mainMenu" style="background: #000000 !important; border: none !important;">
                    <ul class="navbar-nav" style="background: transparent !important;">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php" style="color: #f0bd36 !important;">Inicio</a>
                        </li>
                        <li class="nav-item dropdown dropdown-hover">
                            <a class="nav-link dropdown-toggle <?php echo $isProductsContext ? 'active' : ''; ?>" href="javascript:void(0)" id="productosDropdown" style="color: #f0bd36 !important;">
                                Productos
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="productosDropdown" style="background: #000000 !important; border: 1px solid #f0bd36;">
                                <li>
                                    <a class="dropdown-item" href="products.php?pricing=mayorista" style="color: #f0bd36 !important;">
                                        <i class="fas fa-warehouse me-2"></i>Mayorista
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="products.php?pricing=unidad" style="color: #f0bd36 !important;">
                                        <i class="fas fa-shopping-cart me-2"></i>Por Unidad
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider" style="border-color: #f0bd36;">
                                </li>
                                <!-- Nueva opción: Arma tu Pack (configurador independiente) -->
                                <li>
                                    <a class="dropdown-item" href="arma-tu-pack.php" style="color: #f0bd36 !important;">
                                        <i class="fas fa-sliders-h me-2"></i>Arma tu Pack
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Productos personalizados (sección propia solicitada) -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo $isPersonalizados ? 'active' : ''; ?>" href="personalizados.php" style="color: #f0bd36 !important;">Productos personalizados</a>
                        </li>

                        <!-- Catálogos PDF -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo $isCatalogs ? 'active' : ''; ?>" href="catalogs.php" style="color: #f0bd36 !important;">Catálogos</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'services.php') ? 'active' : ''; ?>" href="services.php" style="color: #f0bd36 !important;">Servicios</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>" href="about.php" style="color: #f0bd36 !important;">Nosotros</a>
                        </li>
                        <li class="nav-item dropdown dropdown-hover">
                            <a class="nav-link dropdown-toggle" href="javascript:void(0)" id="helpDropdown" style="color: #f0bd36 !important;">
                                Ayuda
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="helpDropdown" style="background: #000000 !important; border: 1px solid #f0bd36;">
                                <li><a class="dropdown-item" href="order-tracking.php" style="color: #f0bd36 !important;">
                                        <i class="fas fa-truck me-2"></i>Seguimiento de Pedidos
                                    </a></li>
                                <li><a class="dropdown-item" href="shipping-policies.php" style="color: #f0bd36 !important;">
                                        <i class="fas fa-shipping-fast me-2"></i>Políticas de Envío
                                    </a></li>
                                <li><a class="dropdown-item" href="returns.php" style="color: #f0bd36 !important;">
                                        <i class="fas fa-undo me-2"></i>Cambios y Devoluciones
                                    </a></li>
                                <li><a class="dropdown-item" href="faq.php" style="color: #f0bd36 !important;">
                                        <i class="fas fa-question-circle me-2"></i>Preguntas Frecuentes
                                    </a></li>
                                <li>
                                    <hr class="dropdown-divider" style="border-color: #f0bd36;">
                                </li>
                                <li><a class="dropdown-item" href="contact.php" style="color: #f0bd36 !important;">
                                        <i class="fas fa-envelope me-2"></i>Contáctanos
                                    </a></li>
                            </ul>
                        </li>
                    </ul>

                    <!-- Pegatina de envío gratis -->
                    <div class="navbar-shipping-sticker ms-auto">
                        <div class="shipping-sticker-content">
                            <div class="shipping-icon">
                                <i class="fas fa-truck"></i>
                            </div>
                            <span>Delivery disponible a todo el Perú</span>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </div>
</header>

<style>
    /* DISEÑO MEJORADO PARA MENÚ MÓVIL - MÁS COMPACTO Y ELEGANTE */
    @media (max-width: 991px) {
        /* Menu panel: más compacto, no ocupa toda la pantalla */
        .navbar-collapse {
            background: rgba(0,0,0,0.98) !important;
            padding: 16px !important;
            border-radius: 16px !important;
            border: 2px solid #f0bd36 !important;
            box-shadow: 0 10px 35px rgba(0,0,0,0.8), 0 0 25px rgba(240,189,54,0.3) !important;
            margin-top: 10px !important;
            position: fixed !important;
            left: 15px !important;
            right: 15px !important;
            top: 75px !important;
            max-height: 75vh !important; /* Limita la altura máxima */
            min-height: auto !important;
            z-index: 1100 !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            transform-origin: top center;
            opacity: 0;
            visibility: hidden;
            transform: scale(0.95) translateY(-15px);
        }
        
        .navbar-collapse.show {
            opacity: 1;
            visibility: visible;
            transform: scale(1) translateY(0);
        }
        
        /* Lista de navegación más compacta */
        .navbar-nav {
            padding: 0 !important;
            margin: 0 !important;
        }
        
        .navbar-nav .nav-item {
            margin-bottom: 4px !important;
        }
        
        .navbar-nav .nav-link {
            color: #f0bd36 !important;
            background: transparent !important;
            padding: 12px 16px !important;
            margin-bottom: 0 !important;
            border-radius: 8px !important;
            border: 1px solid transparent !important;
            display: flex !important;
            align-items: center !important;
            width: 100% !important;
            font-weight: 600 !important;
            font-size: 0.95rem !important;
            text-transform: none !important;
            letter-spacing: 0.3px !important;
            transition: all 0.25s ease !important;
            position: relative !important;
        }
        
        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link:focus {
            background: #f0bd36 !important;
            color: #000000 !important;
            border-color: #f0bd36 !important;
            transform: translateX(4px) !important;
            box-shadow: 0 3px 12px rgba(240,189,54,0.4) !important;
        }
        
        .navbar-nav .nav-link.active {
            background: rgba(240,189,54,0.15) !important;
            border-color: #f0bd36 !important;
            color: #f0bd36 !important;
        }
        
        /* Dropdowns más compactos */
        .dropdown-menu {
            background: rgba(0,0,0,0.95) !important;
            border: 1px solid #f0bd36 !important;
            border-radius: 8px !important;
            box-shadow: 0 5px 20px rgba(0,0,0,0.6) !important;
            position: static !important;
            margin-top: 8px !important;
            margin-left: 10px !important;
            margin-right: 10px !important;
            padding: 8px 0 !important;
            max-width: calc(100% - 20px) !important;
        }
        
        .dropdown-item {
            color: #f0bd36 !important;
            background: transparent !important;
            padding: 10px 20px !important;
            border-radius: 6px !important;
            margin: 2px 8px !important;
            font-weight: 500 !important;
            font-size: 0.9rem !important;
            border: 1px solid transparent !important;
            transition: all 0.25s ease !important;
            display: flex !important;
            align-items: center !important;
        }
        
        .dropdown-item:hover,
        .dropdown-item:focus {
            background: #f0bd36 !important;
            color: #000000 !important;
            border-color: #f0bd36 !important;
            transform: translateX(4px) !important;
            box-shadow: 0 2px 8px rgba(240,189,54,0.3) !important;
        }
        
        .dropdown-divider {
            border-color: #f0bd36 !important;
            opacity: 0.3 !important;
            margin: 8px 16px !important;
        }
        
        /* Efecto de brillo para los iconos */
        .navbar-nav .nav-link i,
        .dropdown-item i {
            color: #f0bd36 !important;
            margin-right: 12px !important;
            width: 18px !important;
            text-align: center !important;
            filter: drop-shadow(0 0 2px rgba(240,189,54,0.5)) !important;
            font-size: 0.9rem !important;
        }
        
        .navbar-nav .nav-link:hover i,
        .dropdown-item:hover i {
            color: #000000 !important;
            filter: none !important;
        }
        
        /* Indicador de dropdown */
        .nav-link.dropdown-toggle::after {
            margin-left: auto !important;
            border-top: 0.3em solid #f0bd36 !important;
            border-right: 0.3em solid transparent !important;
            border-left: 0.3em solid transparent !important;
            transition: transform 0.25s ease !important;
        }
        
        .nav-link.dropdown-toggle.active::after {
            transform: rotate(-180deg) !important;
        }
    }

    /* Mobile menu button (hamburger) - SOLO VISIBLE EN MÓVILES */
    .mobile-menu-btn {
        display: none;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: #f0bd36;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        margin-left: 8px;
        transition: all 0.3s ease;
    }
    
    .mobile-menu-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 16px rgba(0,0,0,0.25);
    }
    
    .mobile-menu-btn .hamburger {
        display: inline-block;
        width: 20px;
        height: 16px;
        position: relative;
    }
    
    .mobile-menu-btn .hamburger span {
        display: block;
        height: 2px;
        background: #000;
        border-radius: 2px;
        position: absolute;
        left: 0;
        right: 0;
        transition: all .22s ease;
    }
    
    .mobile-menu-btn .hamburger span:nth-child(1) { top: 0; }
    .mobile-menu-btn .hamburger span:nth-child(2) { top: 7px; }
    .mobile-menu-btn .hamburger span:nth-child(3) { top: 14px; }
    
    .mobile-menu-btn:focus {
        outline: 3px solid rgba(0,0,0,0.08);
        outline-offset: 2px;
    }

    /* When menu is open, animate hamburger to X */
    .mobile-menu-btn.open .hamburger span:nth-child(1) {
        transform: translateY(7px) rotate(45deg);
    }
    
    .mobile-menu-btn.open .hamburger span:nth-child(2) {
        opacity: 0;
    }
    
    .mobile-menu-btn.open .hamburger span:nth-child(3) {
        transform: translateY(-7px) rotate(-45deg);
    }

    /* Header layout para móviles */
    @media (max-width: 991px) {
        .header-top {
            padding: 12px 0;
        }
        
        .header-top .header-top-content {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 70px;
        }
        
        /* Botón menú a la IZQUIERDA */
        .mobile-menu-btn {
            display: inline-flex;
            order: 1;
            margin-left: 12px;
        }
        
        /* Logo CENTRADO */
        .header-top .navbar-brand {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1120;
            order: 2;
        }
        
        .header-top .logo {
            height: 55px;
            max-width: 160px;
            object-fit: contain;
        }
        
        /* Controles a la DERECHA */
        .header-controls {
            order: 3;
            gap: 8px;
            z-index: 1110;
            margin-right: 12px;
        }
        
        /* Hide big search on mobile to free space */
        .header-top .search-form {
            display: none !important;
        }
        
        /* CARRITO MÁS COMPACTO */
        .cart-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #fff;
            padding: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: 1px solid rgba(240,189,54,0.8);
            transition: all 0.3s ease;
        }
        
        .cart-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        }
        
        .cart-btn i {
            font-size: 16px;
            color: #000;
        }
        
        .cart-btn .cart-amount {
            display: none !important;
        }
        
        .cart-btn .cart-count {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #f0bd36;
            color: #000;
            padding: 2px 6px;
            border-radius: 50%;
            font-size: .7rem;
            font-weight: 700;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* WhatsApp button styling */
        .whatsapp-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #25D366;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-size: 18px;
            transition: all 0.3s ease;
        }
        
        .whatsapp-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        }
        
        /* Phone button styling */
        .phone-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f0bd36;
            color: #000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .phone-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        }
    }

    /* Ajustes para pantallas muy pequeñas */
    @media (max-width: 480px) {
        .header-top {
            padding: 10px 0;
        }
        
        .header-top .header-top-content {
            height: 65px;
        }

        /* Menú aún más compacto en móviles pequeños */
        .navbar-collapse {
            left: 10px !important;
            right: 10px !important;
            top: 70px !important;
            max-height: 70vh !important;
            padding: 14px !important;
        }

        .navbar-nav .nav-link {
            padding: 10px 14px !important;
            font-size: 0.9rem !important;
        }

        .dropdown-menu {
            margin-left: 8px !important;
            margin-right: 8px !important;
        }

        .dropdown-item {
            padding: 8px 16px !important;
            font-size: 0.85rem !important;
        }

        /* Botón menú con buen espaciado */
        .mobile-menu-btn {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            margin-left: 10px;
        }

        /* Logo centrado */
        .header-top .logo {
            height: 58px;
            max-width: 165px;
        }

        /* Controles derecha con espaciado */
        .header-controls {
            gap: 8px;
            margin-right: 10px;
        }

        /* Botones más compactos */
        .cart-btn,
        .whatsapp-btn,
        .phone-btn {
            width: 44px;
            height: 44px;
        }
        
        .cart-btn i {
            font-size: 16px;
        }
        
        .whatsapp-btn {
            font-size: 18px;
        }
        
        .phone-btn {
            font-size: 16px;
        }
    }
    
    /* Estilos para desktop */
    @media (min-width: 992px) {
        .mobile-menu-btn {
            display: none !important;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const navbarToggler = document.querySelector('.mobile-menu-btn');
    const navbarCollapse = document.querySelector('#mainMenu');
    
    if (navbarToggler && navbarCollapse) {
        
        // Remover cualquier event listener previo de Bootstrap
        navbarToggler.removeAttribute('data-bs-toggle');
        navbarToggler.removeAttribute('data-bs-target');
        
        // Variable para controlar el estado
        let isMenuOpen = false;
        
        // Función para cerrar menú
        function closeMenu() {
            if (!isMenuOpen) return;
            
            navbarCollapse.classList.remove('show');
            navbarToggler.setAttribute('aria-expanded', 'false');
            navbarToggler.classList.remove('open');
            isMenuOpen = false;
        }
        
        // Función para abrir menú
        function openMenu() {
            if (isMenuOpen) return;
            
            navbarCollapse.classList.add('show');
            navbarToggler.setAttribute('aria-expanded', 'true');
            navbarToggler.classList.add('open');
            isMenuOpen = true;
        }
        
        // Función para alternar menú
        function toggleMenu() {
            if (isMenuOpen) {
                closeMenu();
            } else {
                openMenu();
            }
        }
        
        // Event listener para el toggle
        navbarToggler.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            toggleMenu();
        });
        
        // Cerrar al hacer clic en enlaces del menú
        navbarCollapse.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (!this.classList.contains('dropdown-toggle')) {
                    closeMenu();
                }
            });
        });
        
        // Cerrar al hacer clic fuera del menú
        document.addEventListener('click', function(event) {
            if (!navbarCollapse.contains(event.target) && 
                !navbarToggler.contains(event.target) && 
                isMenuOpen) {
                closeMenu();
            }
        });
        
        // Cerrar con tecla Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && isMenuOpen) {
                closeMenu();
            }
        });
        
        // Cerrar al cambiar tamaño de ventana (si se vuelve a desktop)
        window.addEventListener('resize', function() {
            if (window.innerWidth > 991 && isMenuOpen) {
                closeMenu();
            }
        });
        
        // Mejorar la experiencia táctil en móviles
        navbarCollapse.addEventListener('touchmove', function(e) {
            e.stopPropagation();
        }, { passive: true });
    }
});
</script>
