<!-- Footer JK Jackets -->
<link rel="stylesheet" href="assets/css/footer.css">
<footer class="footer-gradient text-white py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="footer-brand mb-3">
                    <img src="assets/logo.png" alt="JK Jackets" class="footer-logo me-3">
                    <h5 class="fw-bold d-inline-block">JK Jackets</h5>
                </div>
                <p class="footer-description">Especialistas en confección de poleras, casacas, joggers y polos personalizados. Calidad, innovación y puntualidad para emprendedores y empresas.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/GrupoTextilJK" target="_blank" class="footer-social-link me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/jkgrupotextil?igsh=YzNqNTFra2I2ZHJ0" target="_blank" class="footer-social-link me-3"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.tiktok.com/@jkgrupotextil" target="_blank" class="footer-social-link me-3"><i class="fab fa-tiktok"></i></a>
                    <a href="https://wa.me/51999977257" target="_blank" class="footer-social-link"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6 mb-4">
                <details class="footer-details">
                    <summary class="footer-heading fw-bold mb-2">Enlaces</summary>
                    <ul class="footer-links list-unstyled mb-0">
                        <li><a href="index.php" class="footer-link">Inicio</a></li>
                        <li><a href="products.php" class="footer-link">Productos</a></li>
                        <li><a href="cart.php" class="footer-link">Carrito</a></li>
                        <li><a href="about.php" class="footer-link">Nosotros</a></li>
                        <li><a href="contact.php" class="footer-link">Contacto</a></li>
                    </ul>
                </details>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <details class="footer-details">
                    <summary class="footer-heading fw-bold mb-2">Servicios</summary>
                    <ul class="footer-links list-unstyled mb-0">
                        <li><a href="products.php?category=1" class="footer-link">Poleras Personalizadas</a></li>
                        <li><a href="products.php?category=2" class="footer-link">Casacas Corporativas</a></li>
                        <li><a href="products.php?category=3" class="footer-link">Joggers Modernos</a></li>
                        <li><a href="products.php?category=4" class="footer-link">Polos Empresariales</a></li>
                        <li><a href="services.php?type=dtf" class="footer-link">Impresión DTF</a></li>
                        <li><a href="services.php?type=sublimacion" class="footer-link">Sublimación</a></li>
                        <li><a href="services.php?type=bordados" class="footer-link">Bordados</a></li>
                    </ul>
                </details>
            </div>

            <div class="col-lg-3 mb-4">
                <details class="footer-details">
                    <summary class="footer-heading fw-bold mb-2">Contacto</summary>
                    <div class="footer-contact">
                        <p class="mb-2"><i class="fas fa-envelope footer-icon me-2"></i> info@jkjackets.com</p>
                        <p class="mb-2"><i class="fas fa-phone footer-icon me-2"></i> +51 999977257</p>
                        <p class="mb-2"><i class="fas fa-map-marker-alt footer-icon me-2"></i> Huancayo, Perú</p>
                        <p class="mb-2"><i class="fas fa-truck footer-icon me-2"></i> Delivery disponible a todo el Perú</p>
                        <p class="mb-0"><i class="fas fa-clock footer-icon me-2"></i> Lun - Sáb: 9:00 AM - 8:00 PM</p>
                    </div>
                </details>
            </div>
        </div>

        <hr class="footer-divider my-4">

        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="footer-copyright mb-0">&copy; 2024 JK Jackets. Todos los derechos reservados.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="footer-developer">
                    <span class="footer-dev-text">Desarrollado por</span>
                    <a href="https://tecnovedadesweb.com/" target="_blank" class="footer-dev-link ms-2">
                        <strong>TECnovedades</strong>
                        <i class="fas fa-external-link-alt ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Floating WhatsApp Button -->
<a href="https://wa.me/51999977257?text=Hola,%20me%20interesa%20conocer%20m%C3%A1s%20sobre%20sus%20productos%20textiles" class="whatsapp-float" target="_blank" aria-label="Contactar por WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/cart-sync.js"></script>
<script src="assets/js/main.js"></script>
<!-- Footer behavior: expand details on wide screens for column layout -->
<script>
    (function(){
        function syncFooterDetails(){
            var details = document.querySelectorAll('.footer-details');
            if(!details) return;
            var openOnWide = window.innerWidth >= 768;
            details.forEach(function(d){
                try{
                    d.open = openOnWide;
                }catch(e){/* ignore */}
            });
        }
        // run on load
        if(document.readyState === 'loading'){
            document.addEventListener('DOMContentLoaded', syncFooterDetails);
        } else { syncFooterDetails(); }
        // adjust on resize with debounce
        var t;
        window.addEventListener('resize', function(){ clearTimeout(t); t = setTimeout(syncFooterDetails, 150); });
    })();
</script>

