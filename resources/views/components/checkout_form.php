<?php
?>

<form action="/public/izipay/checkout.php" method="POST" id="checkoutForm">
    <?php
    // Si la vista fue incluida sin pasar $checkoutAmount o $orderNumber,
    // intentar calcular el total desde la clase Cart para evitar usar 1.99 por defecto.
    if (!isset($checkoutAmount) || empty($checkoutAmount)) {
        // Intentar cargar entorno mínimo si no está ya disponible
        if (!defined('BASE_PATH')) {
            require_once __DIR__ . '/../../config/paths.php';
        }
        if (!class_exists('Cart')) {
            @require_once BASE_PATH . 'classes/Cart.php';
        }
        if (!function_exists('getPDOConnection')) {
            // intentar cargar database.php que expone Database class
            @require_once BASE_PATH . 'config/database.php';
        }

        try {
            // Crear conexión si la clase Database existe
            if (class_exists('Database')) {
                $database = new Database();
                $db = $database->getConnection();
                if (class_exists('Cart')) {
                    $cart = new Cart($db);
                    $summary = $cart->getCheckoutSummary();
                    $checkoutAmount = number_format($summary['total'] ?? 0, 2, '.', '');
                }
            }
        } catch (Exception $e) {
            // fallback: dejar el valor por defecto
            $checkoutAmount = $checkoutAmount ?? '1.99';
        }
    }

    if (!isset($orderNumber) || empty($orderNumber)) {
        $orderNumber = 'ORD' . time();
    }
    ?>

    <!-- Hidden fields populated server-side (guaranteed fallback) -->
    <input type="hidden" name="orderId" value="<?= htmlspecialchars($orderNumber) ?>">
    <input type="hidden" name="amount" value="<?= htmlspecialchars($checkoutAmount) ?>">
    <input type="hidden" name="currency" value="PEN">
    <?php
    // Incluir datos de los items del carrito en JSON para que el JS pueda construir el mensaje de WhatsApp
    if (isset($summary['items']) && is_array($summary['items'])) {
        $itemsForJs = [];
        foreach ($summary['items'] as $it) {
            $itemsForJs[] = [
                'name' => $it['name'] ?? '',
                'quantity' => isset($it['quantity']) ? (int)$it['quantity'] : 0,
                'size' => $it['size_name'] ?? '',
                'color' => $it['color_name'] ?? ''
            ];
        }
        // imprimir como un script type=application/json para evitar inyección visual
        echo "<script id=\"checkoutItemsData\" type=\"application/json\">" . json_encode($itemsForJs, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) . "</script>";
    }
    ?>
    <div class="form-section">
        <div class="section-header">
            <div class="section-icon">
                <i class="fas fa-user"></i>
            </div>
            <h2 class="section-title">Información Personal</h2>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="firstName">
                        <i class="fas fa-user"></i>
                        Nombre <span class="required-asterisk">*</span>
                    </label>
                    <input type="text" class="form-control" id="firstName" name="firstName"
                        placeholder="Ingrese su nombre" required>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="lastName">
                        <i class="fas fa-user"></i>
                        Apellido <span class="required-asterisk">*</span>
                    </label>
                    <input type="text" class="form-control" id="lastName" name="lastName"
                        placeholder="Ingrese su apellido" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Correo Electrónico <span class="required-asterisk">*</span>
                    </label>
                    <input type="email" class="form-control" id="email" name="email"
                        placeholder="ejemplo@correo.com" required>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="phoneNumber">
                        <i class="fas fa-phone"></i>
                        Teléfono <span class="required-asterisk">*</span>
                    </label>
                    <input type="tel" class="form-control" id="phoneNumber" name="phoneNumber"
                        placeholder="999 999 999" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="identityType">
                        <i class="fas fa-id-card"></i>
                        Tipo de Documento
                    </label>
                    <select class="form-control" id="identityType" name="identityType">
                        <option value="DNI">DNI</option>
                        <option value="PS">Pasaporte</option>
                        <option value="CE">Carné de Extranjería</option>
                    </select>
                </div>
            </div>

            <div class="col-md-8">
                <div class="form-group">
                    <label for="identityCode">
                        <i class="fas fa-id-badge"></i>
                        Número de Documento <span class="required-asterisk">*</span>
                    </label>
                    <input type="text" class="form-control" id="identityCode" name="identityCode"
                        placeholder="Ingrese su número de documento" required>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="section-header">
            <div class="section-icon">
                <i class="fas fa-shipping-fast"></i>
            </div>
            <h2 class="section-title">Información de Envío</h2>
        </div>

        <div class="form-group">
            <label for="address">
                <i class="fas fa-map-marker-alt"></i>
                Dirección <span class="required-asterisk">*</span>
            </label>
            <input type="text" class="form-control" id="address" name="address"
                placeholder="Nombre de la calle y número de casa" required>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="country">
                        <i class="fas fa-globe"></i>
                        País
                    </label>
                    <select class="form-control" id="country" name="country">
                        <option value="PE">Perú</option>
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="state">
                        <i class="fas fa-map"></i>
                        Departamento <span class="required-asterisk">*</span>
                    </label>
                    <select class="form-control" id="state" name="state" required>
                        <option value="">Seleccione un departamento</option>
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="province">
                        <i class="fas fa-map-signs"></i>
                        Provincia <span class="required-asterisk">*</span>
                    </label>
                    <select class="form-control" id="province" name="province" required disabled>
                        <option value="">Seleccione una provincia</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="city">
                        <i class="fas fa-city"></i>
                        Distrito <span class="required-asterisk">*</span>
                    </label>
                    <select class="form-control" id="city" name="city" required disabled>
                        <option value="">Seleccione un distrito</option>
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="zipCode">
                        <i class="fas fa-mail-bulk"></i>
                        Código Postal <span class="required-asterisk">*</span>
                    </label>
                    <input type="text" class="form-control" id="zipCode" name="zipCode"
                        placeholder="15021" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="shippingCompany">
                        <i class="fas fa-truck"></i>
                        Empresa de envío <span class="required-asterisk">*</span>
                    </label>
                    <select class="form-control" id="shippingCompany" name="shippingCompany" required>
                        <option value="">Seleccione una empresa de envío</option>
                        <option value="Shalom">Shalom</option>
                        <option value="Marvisur">Marvisur</option>
                        <option value="Nacional">Nacional</option>
                        <option value="Molina">Molina</option>
                        <option value="Móvil bus">Móvil bus</option>
                        <option value="Otros">Otros</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center d-flex justify-content-center" style="gap:12px;">
        <button type="button" id="openPaymentWidgetBtn" class="submit-btn">
            <i class="fas fa-credit-card"></i>
            Proceder al Pago
        </button>

        <!-- Botón Yape: morado, a la derecha del Proceder al Pago -->
        <button type="button" id="openYapeModalBtn" class="submit-btn" style="background:#6a1b9a;border-color:#6a1b9a;box-shadow:0 8px 24px rgba(106,27,154,0.18);">
            <i class="fas fa-qrcode"></i>
            Pago con Yape
        </button>
    </div>
</form>

<!-- Modal / iframe container for embedded payment -->
<div id="paymentWidgetModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;padding:24px;">
    <div style="width:820px;max-width:98%;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.45);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #eee;background:#fff;">
            <strong style="font-size:16px;color:#333">Pago</strong>
            <button id="closePaymentWidget" aria-label="Cerrar" style="background:none;border:0;font-size:20px;line-height:1;color:#666">✕</button>
        </div>
        <div style="position:relative;background:#fff;">
            <div id="paymentWidgetLoader" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.9);z-index:5;">
                <div style="text-align:center">
                    <div style="width:48px;height:48px;border-radius:50%;border:4px solid #e9e9e9;border-top-color:#d4b200;animation:spin 1s linear infinite;margin:0 auto"></div>
                    <div style="margin-top:8px;color:#666">Cargando formulario de pago...</div>
                </div>
            </div>
            <iframe id="paymentWidgetIframe" name="payment-widget-frame" src="about:blank" style="width:100%;height:640px;border:0;display:block;"></iframe>
        </div>
    </div>
</div>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
/* make iframe content take full modal area */
#paymentWidgetModal iframe{display:block;width:100%;height:640px}
@media (max-width:600px){ #paymentWidgetModal iframe{height:90vh} }
/* Yape button disabled visual */
.yape-disabled{ opacity:0.64; filter:grayscale(8%); }
/* shake animation for attention */
.shake{ animation:shakeX 520ms cubic-bezier(.36,.07,.19,.97) both; }
@keyframes shakeX{ 10%,90%{ transform: translateX(-1px); } 20%,80%{ transform: translateX(2px); } 30%,50%,70%{ transform: translateX(-4px); } 40%,60%{ transform: translateX(4px); } }

/* toast in animation */
@keyframes toastIn{ from{ transform: translateY(-6px); opacity:0 } to{ transform: translateY(0); opacity:1 } }
</style>

<!-- Modal para pago con Yape -->
<div id="yapeModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:10000;align-items:center;justify-content:center;padding:18px;">
    <div style="width:680px;max-width:96%;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.45);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #eee;background:#fff;">
            <strong style="font-size:16px;color:#333">Pago con Yape</strong>
            <button id="closeYapeModal" aria-label="Cerrar" style="background:none;border:0;font-size:20px;line-height:1;color:#666">✕</button>
        </div>
        <div style="padding:22px;text-align:center;">
            <div style="max-width:420px;margin:0 auto;background:#fff;padding:14px;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,0.06);">
                <img id="yapeQrImage" src="assets/images/yape_qr.jpg" alt="QR Yape" style="width:100%;height:auto;border-radius:6px;background:#fff;">
                <!-- Espacio reservado para QR -->
            </div>

            <div style="margin-top:14px;font-size:18px;color:#333;">
                <div>Por favor paga el monto:</div>
                <div id="yapeAmount" style="font-weight:700;margin-top:6px;color:#6a1b9a;font-size:22px;">S/ 0.00</div>
            </div>

            <div style="display:flex;gap:10px;justify-content:center;margin-top:12px;">
                <button id="yapeWhatsappBtn" type="button" class="submit-btn" style="background:#25D366;border-color:#25D366;">
                    <i class="fab fa-whatsapp"></i>&nbsp;Abrir chat de WhatsApp
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Provide a global validator used by multiple inline scripts to avoid ReferenceError
function isCheckoutFormComplete(){
    var checkoutForm = document.getElementById('checkoutForm');
    if(!checkoutForm) return false;
    var requiredEls = checkoutForm.querySelectorAll('[required]');
    for(var i=0;i<requiredEls.length;i++){
        var el = requiredEls[i];
        if(el.disabled) return false;
        var val = (el.value || '').toString().trim();
        if(val === '') return false;
    }
    return true;
}

var openPaymentWidgetBtn = document.getElementById('openPaymentWidgetBtn');

function updatePaymentButtonState(){
    if(!openPaymentWidgetBtn) return;
    if(isCheckoutFormComplete()){
        openPaymentWidgetBtn.classList.remove('yape-disabled');
        openPaymentWidgetBtn.setAttribute('aria-disabled','false');
        openPaymentWidgetBtn.title = '';
    } else {
        openPaymentWidgetBtn.classList.add('yape-disabled');
        openPaymentWidgetBtn.setAttribute('aria-disabled','true');
        openPaymentWidgetBtn.title = 'Complete el formulario de checkout antes de proceder al pago';
    }
}

// listen to form inputs (checkoutForm listener already attached earlier, just call update now)
if(typeof updateYapeButtonState === 'function'){
    // ensure both buttons reflect initial state
    updateYapeButtonState();
}
updatePaymentButtonState();

if(checkoutForm){
    checkoutForm.addEventListener('input', updatePaymentButtonState);
    checkoutForm.addEventListener('change', updatePaymentButtonState);
}

if(openPaymentWidgetBtn){
    openPaymentWidgetBtn.addEventListener('click', function(e){
        if(!isCheckoutFormComplete()){
            showYapeStyleIncompleteToast(openPaymentWidgetBtn);
            return;
        }

        // proceed with original embed behavior
        var modal = document.getElementById('paymentWidgetModal');
        var iframe = document.getElementById('paymentWidgetIframe');
        var loader = document.getElementById('paymentWidgetLoader');
        modal.style.display = 'flex';
        loader.style.display = 'flex';

        // Crear form temporal dentro del modal para enviar POST al iframe
        var embedUrl = '/public/izipay/checkout.php?embed=1';
        var tempForm = document.createElement('form');
        tempForm.method = 'POST';
        tempForm.action = embedUrl;
        tempForm.target = iframe.name || 'payment-widget-frame';
        tempForm.style.display = 'none';

        // Copiar inputs del formulario principal
        var mainForm = document.getElementById('checkoutForm');
        var inputs = mainForm.querySelectorAll('input[name], select[name], textarea[name]');
        inputs.forEach(function(inp){
            var clone = document.createElement('input');
            clone.type = 'hidden';
            clone.name = inp.name;
            clone.value = inp.value;
            tempForm.appendChild(clone);
        });

        document.body.appendChild(tempForm);

        // Ensurar nombre del iframe antes de enviar
        iframe.name = tempForm.target;

        // Escuchar carga del iframe para ocultar loader
        iframe.onload = function(){
            // dar pequeño delay para que widget interno termine de re-renderizar
            setTimeout(function(){ loader.style.display = 'none'; }, 400);
        };

        // Enviar el form (POST). En algunos navegadores, submit inmediato funciona; guardamos un timeout corto por seguridad.
        setTimeout(function(){
            try { tempForm.submit(); } catch (err) { console.error(err); }
            // remover el form después de un segundo
            setTimeout(function(){ if(tempForm.parentNode) tempForm.parentNode.removeChild(tempForm); }, 1200);
        }, 50);
    });
}

document.getElementById('closePaymentWidget').addEventListener('click', function(){
    var modal = document.getElementById('paymentWidgetModal');
    var iframe = document.getElementById('paymentWidgetIframe');
    var loader = document.getElementById('paymentWidgetLoader');
    modal.style.display = 'none';
    loader.style.display = 'flex';
    iframe.src = 'about:blank';
});
</script>

<script>
// Yape modal logic
(function(){
    var openBtn = document.getElementById('openYapeModalBtn');
    var modal = document.getElementById('yapeModal');
    var closeBtn = document.getElementById('closeYapeModal');
    var amountEl = document.getElementById('yapeAmount');
    var qrImg = document.getElementById('yapeQrImage');
    var whatsappBtn = document.getElementById('yapeWhatsappBtn');
    var whatsappLink = document.getElementById('yapeWhatsappLink');
    var checkoutForm = document.getElementById('checkoutForm');

    // Número de WhatsApp del sitio (formato internacional sin +): 51999977257
    var whatsappNumber = '51999977257';

    // Check whether the checkout form is complete
    function isCheckoutFormComplete(){
        if(!checkoutForm) return false;
        var requiredEls = checkoutForm.querySelectorAll('[required]');
        for(var i=0;i<requiredEls.length;i++){
            var el = requiredEls[i];
            if(el.disabled) return false;
            var val = (el.value || '').toString().trim();
            if(val === '') return false;
        }
        return true;
    }

    function updateYapeButtonState(){
        if(!openBtn) return;
        if(isCheckoutFormComplete()){
            openBtn.classList.remove('yape-disabled');
            openBtn.setAttribute('aria-disabled','false');
            openBtn.title = '';
        } else {
            // visual disabled state but still clickable to show friendly animation/message
            openBtn.classList.add('yape-disabled');
            openBtn.setAttribute('aria-disabled','true');
            openBtn.title = 'Complete el formulario de checkout antes de usar Yape';
        }
    }

    // Muestra la misma animación y mensaje que usa el botón "Pago con Yape"
    function showYapeStyleIncompleteToast(targetBtn){
        if(!targetBtn) return;
        // shake
        targetBtn.classList.remove('shake');
        void targetBtn.offsetWidth; // force reflow
        targetBtn.classList.add('shake');

        // remove existing
        var existing = document.getElementById('yapeAlert');
        if(existing){ existing.parentNode.removeChild(existing); }

        var alert = document.createElement('div');
        alert.id = 'yapeAlert';
        alert.style.position = 'absolute';
        alert.style.zIndex = 12000;
        alert.style.left = (targetBtn.getBoundingClientRect().left + window.scrollX) + 'px';
        alert.style.top = (targetBtn.getBoundingClientRect().top + window.scrollY - 54) + 'px';
        alert.style.background = '#fffbeb';
        alert.style.border = '1px solid #ffe7a3';
        alert.style.color = '#8a6d00';
        alert.style.padding = '12px 14px';
        alert.style.borderRadius = '8px';
        alert.style.boxShadow = '0 8px 24px rgba(0,0,0,0.08)';
        alert.style.fontSize = '14px';
        alert.style.animation = 'toastIn 360ms ease-out';
        alert.textContent = 'Por favor complete todos los datos del formulario antes de proceder al pago con Yape.';
        document.body.appendChild(alert);
        setTimeout(function(){ if(alert && alert.parentNode) alert.parentNode.removeChild(alert); }, 4200);
    }

    // Attach listeners to form inputs to validate
    if(checkoutForm){
        checkoutForm.addEventListener('input', updateYapeButtonState);
        checkoutForm.addEventListener('change', updateYapeButtonState);
        // initial state
        updateYapeButtonState();
    }

    function formatCurrency(amount){
        // ensure it's a number and format with 2 decimals
        var n = parseFloat(('' + amount).replace(',', '.')) || 0;
        return 'S/ ' + n.toFixed(2);
    }

    // Click handler: if form incomplete show animated warning; otherwise open modal
    openBtn && openBtn.addEventListener('click', function(){
        if(!isCheckoutFormComplete()){
            showYapeStyleIncompleteToast(openBtn);
            return;
        }

        // Leer el monto desde el input hidden del formulario
        var amountInput = document.querySelector('#checkoutForm input[name="amount"]');
        var amt = amountInput ? amountInput.value : '0.00';
        amountEl.textContent = formatCurrency(amt);

        // Mostrar modal
        if(modal) modal.style.display = 'flex';
    });

    closeBtn && closeBtn.addEventListener('click', function(){
        if(modal) modal.style.display = 'none';
    });

    // Cuando el usuario pulse abrir chat de WhatsApp: prellenar mensaje (orden, monto, productos y datos del cliente)
    whatsappBtn && whatsappBtn.addEventListener('click', function(){
        try{
            var fields = {
                nombre: document.querySelector('#firstName') ? document.querySelector('#firstName').value.trim() : '',
                apellido: document.querySelector('#lastName') ? document.querySelector('#lastName').value.trim() : '',
                correo: document.querySelector('#email') ? document.querySelector('#email').value.trim() : '',
                telefono: document.querySelector('#phoneNumber') ? document.querySelector('#phoneNumber').value.trim() : '',
                direccion: document.querySelector('#address') ? document.querySelector('#address').value.trim() : '',
                departamento: document.querySelector('#state') ? document.querySelector('#state').value.trim() : '',
                provincia: document.querySelector('#province') ? document.querySelector('#province').value.trim() : '',
                distrito: document.querySelector('#city') ? document.querySelector('#city').value.trim() : '',
                cp: document.querySelector('#zipCode') ? document.querySelector('#zipCode').value.trim() : '',
                envio: document.querySelector('#shippingCompany') ? document.querySelector('#shippingCompany').value.trim() : '',
                idTipo: document.querySelector('#identityType') ? document.querySelector('#identityType').value.trim() : '',
                idNumero: document.querySelector('#identityCode') ? document.querySelector('#identityCode').value.trim() : ''
            };

            var amountText = amountEl ? amountEl.textContent : '';
            var orderInput = document.querySelector('#checkoutForm input[name="orderId"]');
            var orderNum = orderInput ? orderInput.value : '';

            var messageLines = [];
            messageLines.push('Hola,');
            messageLines.push('He realizado un pago con Yape y envío la información de la compra:');
            messageLines.push('');
            messageLines.push('• Orden: ' + orderNum);
            messageLines.push('• Monto: ' + amountText);

            // agregar productos si existen
            // Intentar leer items embebidos; si no existen, solicitar al endpoint
            (function(cb){
                try{
                    var itemsScript = document.getElementById('checkoutItemsData');
                    if(itemsScript){
                        var items = JSON.parse(itemsScript.textContent || itemsScript.innerText || '[]');
                        return cb(items);
                    }
                }catch(e){ /* continue to fetch */ }

                // Fallback: fetch items from server
                fetch('/public/api/cart_items.php', { method: 'GET', cache: 'no-store' })
                .then(function(r){ return r.json(); })
                .then(function(json){
                    if(json && json.success && Array.isArray(json.items)) return cb(json.items);
                    return cb([]);
                }).catch(function(){ return cb([]); });
            })(function(items){
                if(items && items.length){
                    messageLines.push('');
                    messageLines.push('Productos:');
                    items.forEach(function(it){
                        var parts = [];
                        if(it.quantity) parts.push(it.quantity + ' x');
                        parts.push(it.name || 'Producto');
                        var meta = [];
                        if(it.size) meta.push('Talla: ' + it.size);
                        if(it.color) meta.push('Color: ' + it.color);
                        if(meta.length) parts.push('(' + meta.join(', ') + ')');
                        messageLines.push('• ' + parts.join(' '));
                    });
                }

                // After items processed, continue building the rest of the message and open WA
                messageLines.push('');
                messageLines.push('Datos del cliente:');
                messageLines.push('• Nombre: ' + fields.nombre + ' ' + fields.apellido);
                if(fields.correo) messageLines.push('• Correo: ' + fields.correo);
                if(fields.telefono) messageLines.push('• Teléfono: ' + fields.telefono);
                if(fields.idTipo || fields.idNumero) messageLines.push('• Documento: ' + fields.idTipo + ' - ' + fields.idNumero);
                messageLines.push('');
                messageLines.push('Dirección de envío:');
                if(fields.direccion) messageLines.push('• Dirección: ' + fields.direccion);
                if(fields.departamento || fields.provincia || fields.distrito) messageLines.push('• Departamento/Provincia/Distrito: ' + fields.departamento + ' / ' + fields.provincia + ' / ' + fields.distrito);
                if(fields.cp) messageLines.push('• Código Postal: ' + fields.cp);
                if(fields.envio) messageLines.push('• Empresa de envío: ' + fields.envio);

                var final = messageLines.join('\n');
                var url = 'https://wa.me/' + whatsappNumber + '?text=' + encodeURIComponent(final);
                window.open(url, '_blank');
            });

            // Return here so we don't run the rest of the original code below (we handle opening WA in callback)
            return;

            messageLines.push('');
            messageLines.push('Datos del cliente:');
            messageLines.push('• Nombre: ' + fields.nombre + ' ' + fields.apellido);
            if(fields.correo) messageLines.push('• Correo: ' + fields.correo);
            if(fields.telefono) messageLines.push('• Teléfono: ' + fields.telefono);
            if(fields.idTipo || fields.idNumero) messageLines.push('• Documento: ' + fields.idTipo + ' - ' + fields.idNumero);
            messageLines.push('');
            messageLines.push('Dirección de envío:');
            if(fields.direccion) messageLines.push('• Dirección: ' + fields.direccion);
            if(fields.departamento || fields.provincia || fields.distrito) messageLines.push('• Departamento/Provincia/Distrito: ' + fields.departamento + ' / ' + fields.provincia + ' / ' + fields.distrito);
            if(fields.cp) messageLines.push('• Código Postal: ' + fields.cp);
            if(fields.envio) messageLines.push('• Empresa de envío: ' + fields.envio);

            var final = messageLines.join('\n');
            var url = 'https://wa.me/' + whatsappNumber + '?text=' + encodeURIComponent(final);
            window.open(url, '_blank');
        }catch(err){
            // fallback: open chat empty
            window.open('https://wa.me/' + whatsappNumber, '_blank');
        }
    });

    // Cerrar modal si el usuario hace click fuera del cuadro
    window.addEventListener('click', function(e){
        if(!modal) return;
        if(e.target === modal) modal.style.display = 'none';
    });

})();
</script>