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
// function asset_url_with_v($path)
// {
//     if (!$path) return '';
//     $path = str_replace('\\', '/', $path);
//     if (preg_match('#^https?://#i', $path)) {
//         return $path;
//     }
//     $fs = __DIR__ . '/' . ltrim($path, '/');
//     if (file_exists($fs)) {
//         return $path . '?v=' . filemtime($fs);
//     }
//     return $path;
// }
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Sobre Nosotros</title>
    <meta name="description" content="Conoce la historia de <?php echo APP_NAME; ?>, especialistas en confección textil personalizada: poleras, casacas, joggers y polos con servicios de DTF, sublimación y bordados.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- CSS modular cargado directamente para mejor rendimiento -->
    <link href="assets/css/variables.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    <link href="assets/css/components.css" rel="stylesheet">
    <link href="assets/css/layout.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <link href="assets/css/utilities.css" rel="stylesheet">
    <!-- Estilos específicos de la página About -->
    <link href="assets/css/about.css" rel="stylesheet">
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
                        Nosotros
                    </li>
                </ol>
            </nav>

            <div class="row">
                <div class="col-12">
                    <h1 class="page-title-minimal mb-2">Sobre Nosotros</h1>
                    <p class="page-subtitle-minimal mb-0">
                        Conoce nuestra historia y los valores que nos definen
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Nuestra Historia -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="fw-bold mb-4">¿Quiénes somos?</h2>
                    <p class="lead text-muted mb-4">
                        JK Grupo Textil es una empresa de confección textil personalizada ubicada en Huancayo, Perú, especializada en la creación de prendas de alta calidad.
                    </p>
                    <p class="mb-4">
                        Nos especializamos en DTF, sublimación y bordados para poleras, casacas, joggers y polos personalizados. Ofrecemos servicios de delivery en las zonas de Huancayo - Tambo - Chilca, garantizando la mejor experiencia para nuestros clientes.
                    </p>
                    <p class="mb-0">
                        Con una calificación de 4.9/5 estrellas y más de 500 clientes satisfechos, nos enorgullecemos de ofrecer productos textiles personalizados de excelente calidad tanto para ventas mayoristas como por unidad.
                    </p>
                </div>
                <div class="col-lg-6">
                    <img src="assets/images/jk-jackets-about.jpg" alt="JK Grupo Textil - Confección Textil" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </section>

    <!-- Nuestros Valores -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <span class="badge bg-primary text-white px-3 py-2 rounded-pill mb-3">
                    <i class="fas fa-heart me-2"></i>Nuestros Valores
                </span>
                <h2 class="fw-bold text-dark mb-3">Los Pilares de Nuestra Empresa</h2>
                <p class="text-muted lead">Los principios que nos definen y guían cada decisión</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-lg text-center position-relative overflow-hidden">
                        <div class="position-absolute top-0 start-0 w-100 h-2 bg-primary"></div>
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-inline-block">
                                    <i class="fas fa-gem fa-2x text-primary"></i>
                                </div>
                            </div>
                            <h5 class="fw-bold mb-3 text-primary">Calidad</h5>
                            <p class="text-muted mb-0">
                                Priorizamos la calidad de los materiales y la confección de nuestros productos textiles, garantizando durabilidad y excelencia en cada prenda.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-lg text-center position-relative overflow-hidden">
                        <div class="position-absolute top-0 start-0 w-100 h-2 bg-success"></div>
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <div class="bg-success bg-opacity-10 rounded-circle p-3 d-inline-block">
                                    <i class="fas fa-handshake fa-2x text-success"></i>
                                </div>
                            </div>
                            <h5 class="fw-bold mb-3 text-success">Innovación</h5>
                            <p class="text-muted mb-0">
                                Estamos a la vanguardia en diseño y tecnología aplicada a la producción de poleras, casacas, joggers y polos personalizados.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-lg text-center position-relative overflow-hidden">
                        <div class="position-absolute top-0 start-0 w-100 h-2 bg-info"></div>
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <div class="bg-info bg-opacity-10 rounded-circle p-3 d-inline-block">
                                    <i class="fas fa-eye fa-2x text-info"></i>
                                </div>
                            </div>
                            <h5 class="fw-bold mb-3 text-info">Servicio al Cliente</h5>
                            <p class="text-muted mb-0">
                                Ofrecemos un excelente servicio de atención al cliente antes, durante y después de la compra, garantizando su satisfacción.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-lg text-center position-relative overflow-hidden">
                        <div class="position-absolute top-0 start-0 w-100 h-2 bg-warning"></div>
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <div class="bg-warning bg-opacity-10 rounded-circle p-3 d-inline-block">
                                    <i class="fas fa-heart fa-2x text-warning"></i>
                                </div>
                            </div>
                            <h5 class="fw-bold mb-3 text-warning">Puntualidad</h5>
                            <p class="text-muted mb-0">
                                Mostramos compromiso en la puntualidad de entrega de nuestros productos, cumpliendo con los tiempos acordados con nuestros clientes.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-lg text-center position-relative overflow-hidden">
                        <div class="position-absolute top-0 start-0 w-100 h-2 bg-success"></div>
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <div class="bg-success bg-opacity-10 rounded-circle p-3 d-inline-block">
                                    <i class="fas fa-leaf fa-2x text-success"></i>
                                </div>
                            </div>
                            <h5 class="fw-bold mb-3 text-success">Sostenibilidad</h5>
                            <p class="text-muted mb-0">
                                Consideramos el impacto ambiental de nuestros procesos productivos y buscamos soluciones sostenibles en la confección textil.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-lg text-center position-relative overflow-hidden">
                        <div class="position-absolute top-0 start-0 w-100 h-2 bg-danger"></div>
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <div class="bg-danger bg-opacity-10 rounded-circle p-3 d-inline-block">
                                    <i class="fas fa-heart fa-2x text-danger"></i>
                                </div>
                            </div>
                            <h5 class="fw-bold mb-3 text-danger">Pasión</h5>
                            <p class="text-muted mb-0">
                                Mostramos pasión por la creación de prendas textiles personalizadas y por el trabajo bien hecho en cada proyecto.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Nuestra Misión -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 order-lg-2">
                    <h2 class="fw-bold mb-4">Nuestra Misión</h2>
                    <p class="lead text-primary mb-4">
                        "Crear prendas textiles personalizadas de alta calidad que reflejen la identidad única de cada cliente"
                    </p>
                    <p class="mb-4">
                        Nuestra misión es ser el aliado estratégico de emprendedores, empresas y particulares en la creación de prendas textiles personalizadas. Nos especializamos en poleras, casacas, joggers y polos con técnicas de DTF, sublimación y bordados de la más alta calidad.
                    </p>
                    <p class="mb-4">
                        Trabajamos tanto con modalidad mayorista como por unidad, adaptándonos a las necesidades específicas de cada cliente. Nuestro compromiso es entregar productos que superen las expectativas, combinando diseño innovador, materiales de primera calidad y técnicas de personalización de vanguardia.
                    </p>
                </div>
                <div class="col-lg-6 order-lg-1">
                    <div class="position-relative">
                        <div class="bg-gradient-primary rounded p-5 text-center text-white">
                            <div class="mb-4">
                                <i class="fas fa-tshirt fa-4x mb-3"></i>
                            </div>
                            <h4 class="fw-bold mb-3">JK Grupo Textil</h4>
                            <p class="mb-0">Confección Textil Personalizada</p>
                            <div class="mt-3">
                                <span class="badge bg-light text-primary me-2">DTF</span>
                                <span class="badge bg-light text-primary me-2">Sublimación</span>
                                <span class="badge bg-light text-primary">Bordados</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- Call to Action -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="card bg-light">
                        <div class="card-body text-center py-5">
                            <h3 class="fw-bold mb-3">¿Listo para personalizar tus prendas?</h3>
                            <p class="text-muted mb-4">Descubre nuestra amplia gama de productos textiles personalizados y encuentra la opción perfecta para tu proyecto</p>
                            <div class="d-flex justify-content-center gap-3 flex-wrap">
                                <a href="products.php" class="btn btn-primary btn-lg btn-cta">
                                    <i class="fas fa-store me-2"></i>Ver Productos
                                </a>
                                <a href="contact.php" class="btn btn-outline-primary btn-lg btn-cta-outline">
                                    <i class="fas fa-envelope me-2"></i>Contáctanos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include COMPONENT_PATH . 'footer.php'; ?>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Animaciones y reveal para About -->
    <script>
        (function(){
            // Simple scroll reveal using IntersectionObserver
            const revealSelectors = ['.page-header-minimal', 'section.py-5', '.card.h-100', '.card.bg-light'];
            const revealEls = document.querySelectorAll(revealSelectors.join(','));
            if('IntersectionObserver' in window) {
                const obs = new IntersectionObserver((entries)=>{
                    entries.forEach(entry=>{
                        if(entry.isIntersecting){
                            entry.target.classList.add('is-visible');
                            obs.unobserve(entry.target);
                        }
                    });
                },{root:null,rootMargin:'0px 0px -8% 0px',threshold:0.12});
                revealEls.forEach(el=>{ el.classList.add('reveal'); obs.observe(el); });
            } else {
                // Fallback: just show
                revealEls.forEach(el=> el.classList.add('is-visible'));
            }

            // Small hover micro-interactions for cards
            document.querySelectorAll('.card.h-100').forEach(card=>{
                card.addEventListener('mouseenter', ()=> card.classList.add('hovered'));
                card.addEventListener('mouseleave', ()=> card.classList.remove('hovered'));
            });
        })();
    </script>
</body>

</html>