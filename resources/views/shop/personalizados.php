<?php
// Página: Productos Personalizados (JK Grupo Textil)
// Requisitos: PHP 8+, sesiones habilitadas, carpeta /uploads/custom_orders escribible.
// Integra: WhatsApp (cotización) y Carrito (agrega un ítem "Pedido Personalizado" con precio 0).
// Notas: Requiere el JSON de ubigeo en assets/data/ubigeo_peru.json.

require_once BASE_PATH . 'config/config.php';
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'classes/Cart.php';
if (session_status() === PHP_SESSION_NONE) session_start();

/* ======================= Helpers seguridad & formato ======================= */
function asset_url_with_v($path)
{
  if (!$path) return '';
  $path = str_replace('\\', '/', $path);
  if (preg_match('#^https?://#i', $path)) return $path;
  $fs = __DIR__ . '/' . ltrim($path, '/');
  return file_exists($fs) ? $path . '?v=' . filemtime($fs) : $path;
}
function strip_tags_trim($s)
{
  return trim(strip_tags((string)($s ?? '')));
}
function sanitize_text($s, $max = 120)
{
  $s = strip_tags_trim($s);
  $s = preg_replace('/[^\p{L}\p{N}\s\.\,\-\_\#\@\(\)\/:]/u', '', $s);
  if (mb_strlen($s, 'UTF-8') > $max) $s = mb_substr($s, 0, $max, 'UTF-8');
  return $s;
}
function sanitize_longtext($s, $max = 800)
{
  $s = strip_tags_trim($s);
  $s = preg_replace('/[^\p{L}\p{N}\s\.\,\-\_\#\@\(\)\/:]/u', '', $s);
  if (mb_strlen($s, 'UTF-8') > $max) $s = mb_substr($s, 0, $max, 'UTF-8');
  return $s;
}
function validate_phone($s)
{
  $digits = preg_replace('/\D+/', '', (string)$s);
  if (strlen($digits) < 8 || strlen($digits) > 13) return '';
  return $digits;
}
function validate_date_ymd($s, $minDaysOffset = 0, $maxDaysOffset = 365)
{
  $s = trim((string)$s);
  if ($s === '') return '';
  if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $s)) return '';
  $dt = DateTime::createFromFormat('Y-m-d', $s);
  $err = DateTime::getLastErrors();
  if (!$dt || !empty($err['warning_count']) || !empty($err['error_count'])) return '';
  $today = new DateTime('today');
  $min = (clone $today)->modify("+{$minDaysOffset} days");
  $max = (clone $today)->modify("+{$maxDaysOffset} days");
  if ($dt < $min || $dt > $max) return '';
  return $dt->format('Y-m-d');
}
function validate_select($value, $allowed)
{
  return in_array($value, $allowed, true) ? $value : '';
}
function validate_color_name($value, $allowed)
{
  return array_key_exists($value, $allowed) ? $value : '';
}
function validate_hex_color($s)
{
  $s = trim((string)$s);
  if ($s === '') return '';
  if (preg_match('/^#?[0-9A-Fa-f]{6}$/', $s)) {
    if ($s[0] !== '#') $s = '#' . $s;
    return strtoupper($s);
  }
  return '';
}
function validate_ubigeo_piece($s, $max = 60)
{
  $s = strip_tags_trim($s);
  if (mb_strlen($s, 'UTF-8') > $max) return '';
  if (!preg_match('/^[\p{L}\s\-\']+$/u', $s)) return '';
  return $s;
}
function clamp_int($n, $min, $max)
{
  $n = (int)$n;
  if ($n < $min) $n = $min;
  if ($n > $max) $n = $max;
  return $n;
}

/* URLs absolutas para que WhatsApp muestre los links clicables */
function absolute_url($path)
{
  if (preg_match('#^https?://#i', $path)) return $path;
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $base   = rtrim($scheme . '://' . $host, '/');
  $path   = '/' . ltrim($path, '/');
  return $base . $path;
}

/* WhatsApp: encode robusto y endpoint compatible */
function build_whatsapp_url($phoneE164, $message)
{
  $message = str_replace(["\r\n", "\r"], "\n", (string)$message);
  $encoded = rawurlencode($message);
  return "https://api.whatsapp.com/send?phone={$phoneE164}&text={$encoded}";
}

/* ======================= Subidas de archivos ======================= */
function handle_uploads($field = 'files', $maxFiles = 5, &$upload_notes = [])
{
  $saved = [];
  $upload_notes = [];
  if (empty($_FILES[$field]) || !is_array($_FILES[$field]['name'])) return $saved;

  $allowedExt = ['pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx'];
  $allowedMime = [
    'application/pdf',
    'image/png',
    'image/jpeg',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
  ];
  $finfo = new finfo(FILEINFO_MIME_TYPE);

  $baseDir = __DIR__ . '/uploads/custom_orders';
  $monthDir = $baseDir . '/' . date('Ym');
  if (!is_dir($monthDir)) @mkdir($monthDir, 0775, true);

  $count = min((int)count($_FILES[$field]['name']), (int)$maxFiles);
  $totalSize = 0;

  for ($i = 0; $i < $count; $i++) {
    $name = (string)$_FILES[$field]['name'][$i];
    $tmp  = (string)$_FILES[$field]['tmp_name'][$i];
    $err  = (int)$_FILES[$field]['error'][$i];
    $size = (int)$_FILES[$field]['size'][$i];

    if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
      if ($name !== '') $upload_notes[] = "Archivo \"{$name}\" no se pudo subir (error del sistema).";
      continue;
    }

    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $mime = $finfo->file($tmp) ?: '';

    if (!in_array($ext, $allowedExt, true) || !in_array($mime, $allowedMime, true)) {
      $upload_notes[] = "Archivo \"{$name}\" rechazado (tipo no permitido).";
      continue;
    }
    if ($size > 15 * 1024 * 1024) {
      $upload_notes[] = "Archivo \"{$name}\" rechazado (excede 15MB).";
      continue;
    }
    $totalSize += $size;
    if ($totalSize > 40 * 1024 * 1024) {
      $upload_notes[] = "Se superó el límite total (40MB). \"{$name}\" omitido.";
      break;
    }

    $safeBase = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', pathinfo($name, PATHINFO_FILENAME));
    $finalName = uniqid('custom_', true) . '_' . $safeBase . '.' . $ext;
    $dest = $monthDir . '/' . $finalName;

    if (@move_uploaded_file($tmp, $dest)) {
      $relPath = 'uploads/custom_orders/' . date('Ym') . '/' . $finalName;
      $saved[] = $relPath;
    } else {
      $upload_notes[] = "Archivo \"{$name}\" no se pudo guardar.";
    }
  }
  return $saved;
}

/* ======================= CSRF + Honeypot ======================= */
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
function check_csrf($token)
{
  return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/* ======================= reCAPTCHA v2 ======================= */
$RECAPTCHA_SITE_KEY   = defined('RECAPTCHA_SITE_KEY') ? RECAPTCHA_SITE_KEY : 'PUT_YOUR_SITE_KEY_HERE';
$RECAPTCHA_SECRET_KEY = defined('RECAPTCHA_SECRET_KEY') ? RECAPTCHA_SECRET_KEY : 'PUT_YOUR_SECRET_KEY_HERE';
$RECAPTCHA_ENABLED    = $RECAPTCHA_SITE_KEY !== 'PUT_YOUR_SITE_KEY_HERE' && $RECAPTCHA_SECRET_KEY !== 'PUT_YOUR_SECRET_KEY_HERE';

function verify_recaptcha($secret, $response, $remoteIp = null)
{
  if (!$response) return false;
  $postData = http_build_query([
    'secret'   => $secret,
    'response' => $response,
    'remoteip' => $remoteIp ?? ''
  ]);
  $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postData,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_SSL_VERIFYPEER => true
  ]);
  $out = curl_exec($ch);
  curl_close($ch);
  if (!$out) return false;
  $json = json_decode($out, true);
  return !empty($json['success']);
}

/* ======================= Config de negocio ======================= */
$WHATSAPP_NUMBER = '51999977257'; // Perú: 51 + número sin +
$ALLOWED_TIPO_PROD = [
  'Conjuntos',
  'Casacas cierre',
  'Poleras capucha',
  'Poleras cuello redondo',
  'Polos básicos',
  'Polos piqué (cuello camisero)',
  'Polos manga larga',
  'Joggers',
  'Otros'
];
$ALLOWED_CUELLOS = ['Camisero', 'Redondo', 'V'];
$ALLOWED_TELAS   = ['Franela 20/1', 'Jersey 20/1', 'French Terry', 'Poliestrech', 'Piqué', 'Otros'];
$ALLOWED_COLORS  = [
  'Negro' => '#000000',
  'Blanco' => '#ffffff',
  'Gris' => '#808080',
  'Rojo' => '#ff0000',
  'Azul marino' => '#001f3f',
  'Azul' => '#007bff',
  'Verde' => '#28a745',
  'Amarillo' => '#ffc107',
  'Naranja' => '#fd7e14',
  'Beige' => '#f5f5dc',
  'Marrón' => '#8b4513',
  'Borgoña' => '#800020'
];

/* ======================= Procesar POST ======================= */
$errors = [];
$upload_notes = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!empty($_POST['company_url'] ?? '')) {
    header('Location: index.php');
    exit;
  } // honeypot
  if (!check_csrf($_POST['csrf_token'] ?? '')) {
    $errors[] = 'La sesión expiró. Actualiza la página e inténtalo nuevamente.';
  }

  // reCAPTCHA
  if ($RECAPTCHA_ENABLED) {
    $captchaOk = verify_recaptcha($RECAPTCHA_SECRET_KEY, $_POST['g-recaptcha-response'] ?? '', $_SERVER['REMOTE_ADDR'] ?? null);
    if (!$captchaOk) $errors[] = 'Por favor completa el reCAPTCHA.';
  }

  $action = ($_POST['submit_action'] ?? '') === 'add_to_cart' ? 'add_to_cart' : 'quote';

  // Datos del cliente
  $nombre     = sanitize_text($_POST['nombre'] ?? '', 80);
  $whatsapp   = validate_phone($_POST['whatsapp'] ?? '');
  $dep        = validate_ubigeo_piece($_POST['departamento'] ?? '', 60);
  $prov       = validate_ubigeo_piece($_POST['provincia'] ?? '', 60);
  $dist       = validate_ubigeo_piece($_POST['distrito'] ?? '', 60);
  $colegio    = sanitize_text($_POST['colegio'] ?? '', 120);
  $fecha_lim  = validate_date_ymd($_POST['fecha_limite'] ?? '', 0, 365); // hoy..+12m

  // Detalles
  $tipo_prod   = validate_select(sanitize_text($_POST['tipo_producto'] ?? '', 40), $ALLOWED_TIPO_PROD);
  $tipo_cuello = ($_POST['tipo_cuello'] ?? '') !== '' ? validate_select(sanitize_text($_POST['tipo_cuello'] ?? '', 20), $ALLOWED_CUELLOS) : '';
  $tipo_tela   = ($_POST['tipo_tela'] ?? '') !== ''   ? validate_select(sanitize_text($_POST['tipo_tela'] ?? '', 30), $ALLOWED_TELAS)   : '';

  // Color por HEX o nombre
  $color_hex   = validate_hex_color($_POST['color_hex'] ?? '');
  $color_sel   = ($_POST['color'] ?? '') !== '' ? validate_color_name(sanitize_text($_POST['color'] ?? '', 20), $ALLOWED_COLORS) : '';
  if ($color_hex === '' && $color_sel !== '') $color_hex = strtoupper($ALLOWED_COLORS[$color_sel] ?? '');

  // Personalización (limitado)
  $MAX_ROWS = 15;
  $est_ubic = is_array($_POST['est_ubicacion'] ?? null) ? array_slice($_POST['est_ubicacion'], 0, $MAX_ROWS) : [];
  $est_qty  = is_array($_POST['est_cantidad'] ?? null)  ? array_slice($_POST['est_cantidad'], 0, $MAX_ROWS)  : [];
  $bor_ubic = is_array($_POST['bor_ubicacion'] ?? null) ? array_slice($_POST['bor_ubicacion'], 0, $MAX_ROWS) : [];
  $bor_qty  = is_array($_POST['bor_cantidad'] ?? null)  ? array_slice($_POST['bor_cantidad'], 0, $MAX_ROWS)  : [];

  $notas     = sanitize_longtext($_POST['notas'] ?? '', 800);
  $cantidad_total = clamp_int($_POST['cantidad_total'] ?? 0, 0, 100000);

  // Matriz de tallas
  $TALLAS_KEYS = ['2', '4', '6', '8', '10', '12', '14', 'XS', 'S', 'M', 'L', 'XL', 'XXL'];
  $tallas = [];
  $total_tallas = 0;
  foreach ($TALLAS_KEYS as $tk) {
    $v = clamp_int($_POST['tallas'][$tk] ?? 0, 0, 999);
    $tallas[$tk] = $v;
    $total_tallas += $v;
  }
  $usa_matriz = $total_tallas > 0;
  if ($usa_matriz) $cantidad_total = $total_tallas;

  // Reglas
  if ($nombre === '') $errors[] = 'El nombre completo es obligatorio.';
  if ($whatsapp === '') $errors[] = 'El WhatsApp es obligatorio (solo dígitos).';
  if ($dep === '' || $prov === '' || $dist === '') $errors[] = 'Seleccione Departamento, Provincia y Distrito válidos.';
  if ($tipo_prod === '') $errors[] = 'El tipo de producto es obligatorio.';
  if ($cantidad_total < 12) $errors[] = 'La cantidad mínima del pedido es de 12 unidades.';
  if (!empty($_POST['fecha_limite'] ?? '') && $fecha_lim === '') $errors[] = 'La fecha límite debe ser válida (AAAA-MM-DD), no pasada y no mayor a 12 meses.';

  // Subidas al final
  $uploaded_files = [];
  if (empty($errors)) $uploaded_files = handle_uploads('files', 5, $upload_notes);

  if (empty($errors)) {
    if ($action === 'quote') {
      $msg = "🧵 COTIZACIÓN - PRODUCTO PERSONALIZADO\n\n";
      $msg .= "👤 Cliente:\n";
      $msg .= "• Nombre: {$nombre}\n";
      $msg .= "• WhatsApp: {$whatsapp}\n";
      $msg .= "• Ubicación: {$dep} / {$prov} / {$dist}\n";
      if ($colegio !== '') $msg .= "• Colegio/Empresa: {$colegio}\n";
      if ($fecha_lim !== '') $msg .= "• Fecha límite: {$fecha_lim}\n";
      $msg .= "\n";
      $msg .= "🧬 Detalles del producto:\n";
      $msg .= "• Tipo: {$tipo_prod}\n";
      if ($tipo_cuello !== '') $msg .= "• Cuello: {$tipo_cuello}\n";
      if ($tipo_tela !== '') $msg .= "• Tela: {$tipo_tela}\n";
      if ($color_hex !== '' && $color_sel !== '')      $msg .= "• Color: {$color_sel} ({$color_hex})\n";
      elseif ($color_hex !== '')                       $msg .= "• Color: {$color_hex}\n";
      elseif ($color_sel !== '')                       $msg .= "• Color: {$color_sel}\n";
      $msg .= "• Cantidad total: {$cantidad_total}\n";

      if ($usa_matriz) {
        $msg .= "\n📏 Tallas (unidades):\n";
        foreach ($TALLAS_KEYS as $tk) if (($tallas[$tk] ?? 0) > 0) $msg .= "• {$tk}: {$tallas[$tk]}\n";
      }

      if (!empty($est_ubic)) {
        $est_det = [];
        for ($i = 0; $i < count($est_ubic); $i++) {
          $u = sanitize_text($est_ubic[$i] ?? '', 30);
          $q = clamp_int($est_qty[$i] ?? 0, 0, 999);
          if ($u !== '' && $q > 0) $est_det[] = "• {$u}: {$q} unidades";
        }
        if (!empty($est_det)) $msg .= "\n🎨 Estampado:\n" . implode("\n", $est_det) . "\n";
      }

      if (!empty($bor_ubic)) {
        $bor_det = [];
        for ($i = 0; $i < count($bor_ubic); $i++) {
          $u = sanitize_text($bor_ubic[$i] ?? '', 30);
          $q = clamp_int($bor_qty[$i] ?? 0, 0, 999);
          if ($u !== '' && $q > 0) $bor_det[] = "• {$u}: {$q} unidades";
        }
        if (!empty($bor_det)) $msg .= "\n🧵 Bordado:\n" . implode("\n", $bor_det) . "\n";
      }

      if ($notas !== '') $msg .= "\n📝 Notas:\n{$notas}\n";

      if (!empty($uploaded_files)) {
        $msg .= "\n📎 Archivos subidos (" . count($uploaded_files) . "):\n";
        foreach ($uploaded_files as $f) {
          $url = absolute_url(asset_url_with_v($f));
          $msg .= "• {$url}\n";
        }
      }

      $msg .= "\nCondición: Pedido mínimo 12 unidades.\n";
      $msg .= "Gracias por preferir JK Grupo Textil 🙌";

      $wa = build_whatsapp_url($WHATSAPP_NUMBER, $msg);
      header('Location: ' . $wa);
      exit;
    } else {
      try {
        $database = new Database();
        $db = $database->getConnection();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare("SELECT id FROM products WHERE name = 'Pedido Personalizado' LIMIT 1");
        $stmt->execute();
        $pid = (int)$stmt->fetchColumn();
        if ($pid <= 0) {
          $ins = $db->prepare("
            INSERT INTO products (name, description, base_price, category_id, image_url, has_variants, is_active, created_at, updated_at)
            VALUES ('Pedido Personalizado', 'Ítem de pedido personalizado agregado desde el configurador.', 0.00, NULL, NULL, 0, 1, NOW(), NOW())
          ");
          $ins->execute();
          $pid = (int)$db->lastInsertId();
        }

        $cart = new Cart($db);
        $qty = max(12, (int)$cantidad_total);
        $cart->addItem($pid, $qty, null, null);

        $_SESSION['custom_order_last'] = [
          'nombre' => $nombre,
          'whatsapp' => $whatsapp,
          'ubicacion' => ['departamento' => $dep, 'provincia' => $prov, 'distrito' => $dist],
          'colegio' => $colegio,
          'fecha_limite' => $fecha_lim,
          'tipo_producto' => $tipo_prod,
          'tipo_cuello' => $tipo_cuello,
          'tipo_tela' => $tipo_tela,
          'color_nombre' => $color_sel,
          'color_hex' => $color_hex,
          'cantidad_total' => $qty,
          'tallas' => $tallas,
          'estampado' => $est_ubic,
          'bordado' => $bor_ubic,
          'notas' => $notas,
          'files' => $uploaded_files
        ];
        header('Location: cart.php?success=custom_added');
        exit;
      } catch (Throwable $e) {
        $errors[] = 'No se pudo agregar al carrito. Intente nuevamente.';
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Productos Personalizados - <?php echo APP_NAME; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- CSS base -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="<?php echo asset_url_with_v('assets/css/variables.css'); ?>" rel="stylesheet">
  <link href="<?php echo asset_url_with_v('assets/css/header.css'); ?>" rel="stylesheet">
  <link href="<?php echo asset_url_with_v('assets/css/components.css'); ?>" rel="stylesheet">
  <link href="<?php echo asset_url_with_v('assets/css/layout.css'); ?>" rel="stylesheet">
  <link href="<?php echo asset_url_with_v('assets/css/responsive.css'); ?>" rel="stylesheet">
  <link href="<?php echo asset_url_with_v('assets/css/utilities.css'); ?>" rel="stylesheet">
  <style>
    .card-custom {
      border: 1px solid rgba(212, 175, 55, .25);
      border-radius: 16px;
      box-shadow: 0 8px 28px rgba(0, 0, 0, .06);
    }

    .section-title {
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 800;
      color: #111;
    }

    .badge-min {
      background: linear-gradient(135deg, #f6d365, #d4af37);
      color: #111;
      border: 1px solid rgba(212, 175, 55, .6);
    }

    .bg-gold {
      background: linear-gradient(135deg, #f6d365, #d4af37);
      color: #111 !important;
      border-bottom: 1px solid rgba(212, 175, 55, .55);
    }

    .color-swatch {
      display: inline-block;
      width: 14px;
      height: 14px;
      border-radius: 3px;
      margin-right: 6px;
      border: 1px solid rgba(0, 0, 0, .15);
      vertical-align: middle;
    }

    .row-inline>* {
      margin-bottom: 8px;
    }

    .uploader {
      border: 2px dashed #e3e3e3;
      padding: 14px;
      border-radius: 10px;
      background: #fafafa;
    }

    .uploader input[type="file"] {
      background: transparent;
      border: 0;
    }

    .help {
      color: #6c757d;
      font-size: .9rem;
    }

    .floating-actions {
      position: sticky;
      bottom: 0;
      background: #fff;
      border-top: 1px solid #eee;
      padding: 12px;
      z-index: 5;
    }

    .qty-min-text {
      font-size: .9rem;
      color: #6c757d;
    }

    .pill {
      border-radius: 12px;
      padding: 4px 10px;
      border: 1px solid #e3e3e3;
      background: #fff;
      color: #111;
    }

    /* === MOSTRAR flechitas en number === */
    .table input[type="number"] {
      appearance: auto;
      /* Chrome/Edge/Safari */
      -moz-appearance: number-input;
      /* Firefox */
    }

    .visually-hidden {
      position: absolute;
      left: -10000px;
      top: auto;
      width: 1px;
      height: 1px;
      overflow: hidden;
    }

    /* === UI Colores === */
    .color-palette {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 8px
    }

    .swatch-btn {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      border: 2px solid #e1e1e1;
      cursor: pointer;
      outline: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 1px 2px rgba(0, 0, 0, .06);
      position: relative;
      background: #fff;
    }

    .swatch-btn.active {
      border-color: #111;
      box-shadow: 0 0 0 2px rgba(0, 0, 0, .08)
    }

    .swatch-tooltip {
      position: absolute;
      bottom: 42px;
      left: 50%;
      transform: translateX(-50%);
      white-space: nowrap;
      font-size: .75rem;
      background: #111;
      color: #fff;
      padding: 2px 6px;
      border-radius: 6px;
      display: none
    }

    .swatch-btn:hover .swatch-tooltip {
      display: block
    }

    .wheel-wrap {
      display: flex;
      gap: 16px;
      align-items: center;
      flex-wrap: wrap
    }

    .wheel {
      position: relative;
      width: 220px;
      height: 220px
    }

    .wheel canvas {
      width: 220px;
      height: 220px;
      display: block;
      border-radius: 50%;
      box-shadow: 0 2px 8px rgba(0, 0, 0, .08)
    }

    .wheel .handle {
      position: absolute;
      width: 18px;
      height: 18px;
      border: 3px solid #fff;
      border-radius: 50%;
      box-shadow: 0 0 0 2px rgba(0, 0, 0, .25)
    }

    .hex-field {
      min-width: 160px
    }

    /* === Botón flotante WhatsApp === */
    .whatsapp-float {
      position: fixed;
      right: 18px;
      bottom: 18px;
      z-index: 9999;
      width: 56px;
      height: 56px;
      border-radius: 50%;
      background: #25d366;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 24px rgba(0, 0, 0, .18);
      transition: transform .15s ease;
    }

    .whatsapp-float:hover {
      transform: translateY(-2px)
    }

    .whatsapp-float i {
      color: #fff;
      font-size: 28px;
      line-height: 1
    }
  </style>
</head>

<body>
  <?php include COMPONENT_PATH . 'header.php'; ?>

  <section class="py-4 page-header-minimal">
    <div class="container">
      <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb bg-transparent p-0 mb-0">
          <li class="breadcrumb-item"><a href="index.php" class="breadcrumb-link"><i class="fas fa-home me-1"></i>Inicio</a></li>
          <li class="breadcrumb-item"><a href="products.php" class="breadcrumb-link">Productos</a></li>
          <li class="breadcrumb-item active breadcrumb-current" aria-current="page">Productos Personalizados</li>
        </ol>
      </nav>
      <h1 class="mb-0">Productos Personalizados</h1>
      <p class="text-muted mb-0">Cuéntanos tu necesidad y armamos tu pedido. Pedido mínimo: <span class="badge badge-min">12 unidades</span></p>
    </div>
  </section>

  <section class="py-4">
    <div class="container">
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
      <?php endif; ?>

      <?php if (!empty($upload_notes) && empty($errors)): ?>
        <div class="alert alert-warning"><i class="fas fa-paperclip me-2"></i><?php echo implode('<br>', array_map('htmlspecialchars', $upload_notes)); ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" id="personalizadosForm" class="needs-validation" novalidate>
        <!-- CSRF + honeypot -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
        <input type="text" name="company_url" class="visually-hidden" tabindex="-1" autocomplete="off">

        <div class="row g-4">
          <!-- Datos del cliente -->
          <div class="col-lg-6">
            <div class="card card-custom">
              <div class="card-header bg-gold">
                <div class="section-title"><i class="fas fa-user"></i>Datos del cliente</div>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label">Nombre completo *</label>
                  <input type="text" name="nombre" class="form-control" required maxlength="80" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                  <div class="invalid-feedback">Ingrese su nombre completo</div>
                </div>
                <div class="mb-3">
                  <label class="form-label">WhatsApp *</label>
                  <input type="tel" name="whatsapp" class="form-control" required inputmode="numeric" pattern="\d{8,13}" maxlength="13" value="<?php echo htmlspecialchars($_POST['whatsapp'] ?? ''); ?>">
                  <div class="invalid-feedback">Ingrese solo dígitos (8 a 13).</div>
                </div>
                <div class="row row-inline">
                  <div class="col-md-4">
                    <label class="form-label">Departamento *</label>
                    <select class="form-select" name="departamento" id="departamento" required>
                      <option value="">Seleccionar...</option>
                    </select>
                    <div class="invalid-feedback">Seleccione departamento</div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Provincia *</label>
                    <select class="form-select" name="provincia" id="provincia" required disabled>
                      <option value="">Seleccionar...</option>
                    </select>
                    <div class="invalid-feedback">Seleccione provincia</div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Distrito *</label>
                    <select class="form-select" name="distrito" id="distrito" required disabled>
                      <option value="">Seleccionar...</option>
                    </select>
                    <div class="invalid-feedback">Seleccione distrito</div>
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Colegio y/o empresa</label>
                  <input type="text" name="colegio" class="form-control" maxlength="120" value="<?php echo htmlspecialchars($_POST['colegio'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                  <label class="form-label">Fecha límite de entrega</label>
                  <input type="date" name="fecha_limite" id="fecha_limite" class="form-control" value="<?php echo htmlspecialchars($_POST['fecha_limite'] ?? ''); ?>">
                  <div class="form-text help">Debe ser una fecha válida entre hoy y los próximos 12 meses.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Detalles del producto -->
          <div class="col-lg-6">
            <div class="card card-custom h-100">
              <div class="card-header bg-gold">
                <div class="section-title"><i class="fas fa-shirt"></i>Detalles del producto</div>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label">Tipo de producto *</label>
                  <select class="form-select" name="tipo_producto" required>
                    <option value="">Seleccionar...</option>
                    <?php
                    $opts = $ALLOWED_TIPO_PROD;
                    $val = $_POST['tipo_producto'] ?? '';
                    foreach ($opts as $o) {
                      $sel = ($val === $o) ? 'selected' : '';
                      echo "<option {$sel} value=\"" . htmlspecialchars($o) . "\">" . htmlspecialchars($o) . "</option>";
                    }
                    ?>
                  </select>
                  <div class="invalid-feedback">Seleccione el tipo de producto</div>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Tipo de cuello</label>
                    <select class="form-select" name="tipo_cuello">
                      <option value="">Seleccionar...</option>
                      <?php
                      $cuellos = $ALLOWED_CUELLOS;
                      $v = $_POST['tipo_cuello'] ?? '';
                      foreach ($cuellos as $c) {
                        $sel = ($v === $c) ? 'selected' : '';
                        echo "<option {$sel} value=\"" . htmlspecialchars($c) . "\">" . htmlspecialchars($c) . "</option>";
                      }
                      ?>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Tipo de tela</label>
                    <select class="form-select" name="tipo_tela">
                      <option value="">Seleccionar...</option>
                      <?php
                      $telas = $ALLOWED_TELAS;
                      $v = $_POST['tipo_tela'] ?? '';
                      foreach ($telas as $t) {
                        $sel = ($v === $t) ? 'selected' : '';
                        echo "<option {$sel} value=\"" . htmlspecialchars($t) . "\">" . htmlspecialchars($t) . "</option>";
                      }
                      ?>
                    </select>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Color (elige por nombre o usa la rueda/HEX)</label>
                  <select class="form-select" name="color" id="colorSelect">
                    <option value="">Seleccionar...</option>
                    <?php
                    $colors = $ALLOWED_COLORS;
                    $selColor = $_POST['color'] ?? '';
                    foreach ($colors as $name => $hex) {
                      $sel = ($selColor === $name) ? 'selected' : '';
                      echo "<option {$sel} value=\"" . htmlspecialchars($name) . "\" data-hex=\"{$hex}\">" . htmlspecialchars($name) . "</option>";
                    }
                    ?>
                  </select>
                  <div class="form-text help">
                    <span id="colorPreview" class="color-swatch" style="display:none;"></span>
                    <span id="colorNamePreview"></span>
                  </div>

                  <!-- Swatches rápidos -->
                  <div id="colorPalette" class="color-palette" role="radiogroup" aria-label="Elegir color rápido"></div>

                  <!-- Rueda e input HEX -->
                  <div class="wheel-wrap mt-3">
                    <div class="wheel">
                      <canvas id="colorWheel" width="220" height="220" aria-label="Selector de color"></canvas>
                      <div id="wheelHandle" class="handle" style="left:101px; top:101px;"></div>
                    </div>
                    <div class="d-flex flex-column gap-2">
                      <div>
                        <label class="form-label mb-1">HEX</label>
                        <div class="input-group hex-field">
                          <span class="input-group-text">#</span>
                          <input type="text" class="form-control" id="hexInput" maxlength="6" placeholder="RRGGBB" value="<?php echo htmlspecialchars(ltrim($_POST['color_hex'] ?? '', '#')); ?>">
                        </div>
                        <div class="form-text">Puedes escribir el código HEX o elegir con la rueda.</div>
                      </div>
                      <div>
                        <span class="pill">Seleccionado: <strong id="pickedHex">—</strong></span>
                      </div>
                    </div>
                  </div>
                  <input type="hidden" name="color_hex" id="colorHexHidden" value="<?php echo htmlspecialchars($_POST['color_hex'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                  <label class="form-label">Subir archivo(s)</label>
                  <div class="uploader">
                    <input type="file" name="files[]" multiple accept=".pdf,.png,.jpg,.jpeg,.doc,.docx">
                    <div class="form-text help">Formatos permitidos: PDF, PNG, JPG, DOC, DOCX. Máximo 5 archivos (15MB c/u, 40MB total).</div>
                  </div>
                </div>

                <div class="mb-0">
                  <label class="form-label">Cantidad total *</label>
                  <input type="number" min="12" step="1" name="cantidad_total" id="cantidad_total" class="form-control" required value="<?php echo htmlspecialchars($_POST['cantidad_total'] ?? '12'); ?>">
                  <div class="invalid-feedback">Mínimo 12 unidades</div>
                  <div class="qty-min-text mt-1"><i class="fas fa-info-circle me-1"></i>Pedido mínimo: 12 unidades.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Matriz de Tallas -->
          <div class="col-12">
            <div class="card card-custom">
              <div class="card-header bg-gold">
                <div class="section-title"><i class="fas fa-ruler-combined"></i>Matriz de tallas</div>
              </div>
              <div class="card-body">
                <div class="row g-2 align-items-end">
                  <div class="col-12">
                    <div class="table-responsive">
                      <table class="table align-middle mb-2">
                        <thead>
                          <tr>
                            <th class="text-nowrap">2</th>
                            <th>4</th>
                            <th>6</th>
                            <th>8</th>
                            <th>10</th>
                            <th>12</th>
                            <th>14</th>
                            <th>XS</th>
                            <th>S</th>
                            <th>M</th>
                            <th>L</th>
                            <th>XL</th>
                            <th>XXL</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <?php
                            $keys = ['2', '4', '6', '8', '10', '12', '14', 'XS', 'S', 'M', 'L', 'XL', 'XXL'];
                            foreach ($keys as $k) {
                              $val = isset($_POST['tallas'][$k]) ? (int)$_POST['tallas'][$k] : 0;
                              echo '<td style="min-width:72px"><input type="number" min="0" max="999" step="1" name="tallas[' . htmlspecialchars($k) . ']" value="' . $val . '" class="form-control form-control-sm talla-input"></td>';
                            }
                            ?>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                    <div class="help">Si completas esta matriz, la <strong>cantidad total</strong> se calculará automáticamente.</div>
                    <div class="mt-2"><span class="pill">Suma de tallas: <strong id="sumaTallas">0</strong></span></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Personalización -->
          <div class="col-12">
            <div class="card card-custom">
              <div class="card-header bg-gold">
                <div class="section-title"><i class="fas fa-sparkles"></i>Personalización</div>
              </div>
              <div class="card-body">
                <div class="row">
                  <!-- Estampado -->
                  <div class="col-lg-6 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <h6 class="mb-0"><i class="fas fa-print me-2"></i>Estampado</h6>
                      <button type="button" class="btn btn-sm btn-outline-secondary" id="addEst"><i class="fas fa-plus"></i> Añadir</button>
                    </div>
                    <div id="estList"></div>
                  </div>
                  <!-- Bordado -->
                  <div class="col-lg-6 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <h6 class="mb-0"><i class="fas fa-needle me-2"></i>Bordado</h6>
                      <button type="button" class="btn btn-sm btn-outline-secondary" id="addBor"><i class="fas fa-plus"></i> Añadir</button>
                    </div>
                    <div id="borList"></div>
                  </div>
                </div>

                <div class="mt-2">
                  <label class="form-label">Notas adicionales</label>
                  <textarea name="notas" class="form-control" rows="3" maxlength="800" placeholder="Instrucciones, tallajes, colores, posiciones, etc."><?php echo htmlspecialchars($_POST['notas'] ?? ''); ?></textarea>
                </div>

                <?php if ($RECAPTCHA_ENABLED): ?>
                  <div class="mt-3">
                    <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($RECAPTCHA_SITE_KEY); ?>"></div>
                  </div>
                <?php else: ?>
                  <div class="alert alert-info mt-3">
                    <i class="fas fa-shield-halved me-1"></i>
                    reCAPTCHA desactivado: configura <code>RECAPTCHA_SITE_KEY</code> y <code>RECAPTCHA_SECRET_KEY</code> en <code>config/config.php</code>.
                  </div>
                <?php endif; ?>

              </div>
            </div>
          </div>
        </div>

        <!-- Acciones -->
        <div class="floating-actions mt-3">
          <div class="container">
            <div class="d-flex flex-column flex-md-row gap-2 justify-content-end">
              <button type="submit" name="submit_action" value="quote" class="btn btn-warning btn-lg">
                <i class="fab fa-whatsapp me-2"></i>Cotizar por WhatsApp
              </button>
              <button type="submit" name="submit_action" value="add_to_cart" class="btn btn-primary btn-lg">
                <i class="fas fa-cart-plus me-2"></i>Añadir al carrito
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </section>

  <?php include COMPONENT_PATH . 'footer.php'; ?>

  <!-- Botón flotante WhatsApp -->
  <a
    href="https://api.whatsapp.com/send?phone=51918605351&text=Hola%2C%20tengo%20una%20consulta%20sobre%20productos%20personalizados"
    class="whatsapp-float" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp">
    <i class="fab fa-whatsapp"></i>
  </a>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <?php if ($RECAPTCHA_ENABLED): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <?php endif; ?>
  <script>
    /* ------------------ Fecha: min hoy, max +365 ------------------ */
    (function setDateBounds() {
      const inp = document.getElementById('fecha_limite');
      if (!inp) return;
      const today = new Date();
      const yyyy = today.getFullYear();
      const mm = String(today.getMonth() + 1).padStart(2, '0');
      const dd = String(today.getDate()).padStart(2, '0');
      const min = `${yyyy}-${mm}-${dd}`;
      const maxDate = new Date(today);
      maxDate.setDate(maxDate.getDate() + 365);
      const yyyy2 = maxDate.getFullYear();
      const mm2 = String(maxDate.getMonth() + 1).padStart(2, '0');
      const dd2 = String(maxDate.getDate()).padStart(2, '0');
      const max = `${yyyy2}-${mm2}-${dd2}`;
      inp.setAttribute('min', min);
      inp.setAttribute('max', max);
    })();

    /* ===================== UBIGEO: Cargar y poblar ===================== */
    let UBIGEO = null; // JSON en bruto
    let MAPA = {}; // { Departamento: { Provincia: Set(Distritos) } }

    async function loadUbigeo() {
      try {
        const url = '<?php echo asset_url_with_v('assets/data/ubigeo_peru.json'); ?>';
        const res = await fetch(url, {
          cache: 'no-store'
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        UBIGEO = await res.json();
        MAPA = normalizarUbigeo(UBIGEO);
        poblarDepartamentos();
        restaurarSeleccionPrevias();
      } catch (e) {
        console.error('No se pudo cargar el ubigeo', e);
      }
    }

    function normalizarUbigeo(raw) {
      const map = {};
      // Estructura: { departments: [ { name, provinces: [ { name, districts: [] } ] } ] }
      if (raw && Array.isArray(raw.departments)) {
        raw.departments.forEach(d => {
          const dName = (d && (d.name || d.departamento))?.trim();
          if (!dName) return;
          map[dName] = map[dName] || {};
          (d.provinces || d.provincias || []).forEach(p => {
            const pName = (p && (p.name || p.provincia))?.trim();
            if (!pName) return;
            map[dName][pName] = map[dName][pName] || new Set();
            (p.districts || p.distritos || []).forEach(dd => {
              const n = (typeof dd === 'string') ? dd.trim() : (dd && (dd.name || dd.distrito))?.trim();
              if (n) map[dName][pName].add(n);
            });
          });
        });
      }
      return map;
    }

    const depSel = document.getElementById('departamento');
    const provSel = document.getElementById('provincia');
    const distSel = document.getElementById('distrito');

    function limpiarSelect(sel, placeholder = 'Seleccionar...') {
      sel.innerHTML = '';
      const opt = document.createElement('option');
      opt.value = '';
      opt.textContent = placeholder;
      sel.appendChild(opt);
    }

    function poblarDepartamentos() {
      limpiarSelect(depSel);
      const deps = Object.keys(MAPA).sort((a, b) => a.localeCompare(b, 'es'));
      deps.forEach(d => {
        const o = document.createElement('option');
        o.value = o.textContent = d;
        depSel.appendChild(o);
      });
      provSel.disabled = true;
      limpiarSelect(provSel);
      distSel.disabled = true;
      limpiarSelect(distSel);

      depSel.removeEventListener('change', onDepartamentoChange);
      depSel.addEventListener('change', onDepartamentoChange);
    }

    function onDepartamentoChange() {
      const dep = depSel.value;
      limpiarSelect(provSel);
      limpiarSelect(distSel);
      distSel.disabled = true;

      if (!dep || !MAPA[dep]) {
        provSel.disabled = true;
        return;
      }
      const provs = Object.keys(MAPA[dep]).sort((a, b) => a.localeCompare(b, 'es'));
      provs.forEach(p => {
        const o = document.createElement('option');
        o.value = o.textContent = p;
        provSel.appendChild(o);
      });
      provSel.disabled = false;

      provSel.removeEventListener('change', onProvinciaChange);
      provSel.addEventListener('change', onProvinciaChange);
    }

    function onProvinciaChange() {
      const dep = depSel.value;
      const prov = provSel.value;
      limpiarSelect(distSel);

      if (!dep || !prov || !MAPA[dep] || !MAPA[dep][prov]) {
        distSel.disabled = true;
        return;
      }
      const dists = Array.from(MAPA[dep][prov]).sort((a, b) => a.localeCompare(b, 'es'));
      dists.forEach(d => {
        const o = document.createElement('option');
        o.value = o.textContent = d;
        distSel.appendChild(o);
      });
      distSel.disabled = false;
    }

    function restaurarSeleccionPrevias() {
      const depPrev = <?php echo json_encode($_POST['departamento'] ?? ''); ?>;
      const provPrev = <?php echo json_encode($_POST['provincia'] ?? ''); ?>;
      const distPrev = <?php echo json_encode($_POST['distrito'] ?? ''); ?>;

      if (depPrev && MAPA[depPrev]) {
        depSel.value = depPrev;
        onDepartamentoChange();
        if (provPrev && MAPA[depPrev][provPrev]) {
          provSel.value = provPrev;
          onProvinciaChange();
          if (distPrev) distSel.value = distPrev;
        }
      }
    }

    /* ======================= Colores ======================= */
    const NAMED_COLORS = <?php echo json_encode($ALLOWED_COLORS, JSON_UNESCAPED_UNICODE); ?>;

    function nearestNamed(hex) {
      const rgb = hexToRgb(hex);
      if (!rgb) return null;
      let best = null;
      for (const [name, hx] of Object.entries(NAMED_COLORS)) {
        const r2 = hexToRgb(hx);
        const d = Math.sqrt(Math.pow(rgb[0] - r2[0], 2) + Math.pow(rgb[1] - r2[1], 2) + Math.pow(rgb[2] - r2[2], 2));
        if (!best || d < best.dist) best = {
          name,
          hex: hx.toUpperCase(),
          dist: d
        };
      }
      return best;
    }
    const NAME_TOLERANCE = 35;

    function bindColorPreview() {
      const sel = document.getElementById('colorSelect');
      const box = document.getElementById('colorPreview');
      const name = document.getElementById('colorNamePreview');

      function update() {
        const opt = sel.selectedOptions[0];
        if (!opt || !opt.dataset.hex) {
          box.style.display = 'none';
          name.textContent = '';
          setActiveSwatch('');
          return;
        }
        box.style.display = 'inline-block';
        box.style.backgroundColor = opt.dataset.hex;
        name.textContent = opt.textContent || '';
        setActiveSwatch(opt.value);
        setHex(opt.dataset.hex, false);
      }
      sel.addEventListener('change', update);
      update();
    }

    function renderColorPalette() {
      const palette = document.getElementById('colorPalette');
      const sel = document.getElementById('colorSelect');
      palette.innerHTML = '';
      [...sel.options].forEach(opt => {
        if (!opt.value) return;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'swatch-btn';
        btn.setAttribute('role', 'radio');
        btn.setAttribute('aria-label', opt.textContent);
        btn.dataset.value = opt.value;
        const inner = document.createElement('span');
        inner.style.width = '22px';
        inner.style.height = '22px';
        inner.style.borderRadius = '50%';
        inner.style.backgroundColor = opt.dataset.hex || '#ffffff';
        const tip = document.createElement('span');
        tip.className = 'swatch-tooltip';
        tip.textContent = opt.textContent;
        btn.append(inner, tip);
        btn.addEventListener('click', () => {
          sel.value = opt.value;
          sel.dispatchEvent(new Event('change', {
            bubbles: true
          }));
        });
        palette.appendChild(btn);
      });
      setActiveSwatch(sel.value);
    }

    function setActiveSwatch(val) {
      document.querySelectorAll('.swatch-btn').forEach(b => {
        const active = b.dataset.value === val && val !== '';
        b.classList.toggle('active', active);
        b.setAttribute('aria-checked', active ? 'true' : 'false');
      });
    }

    // === Rueda de color (HSV) ===
    const wheel = {
      canvas: null,
      ctx: null,
      handle: null,
      r: 0,
      cx: 0,
      cy: 0,
      init() {
        this.canvas = document.getElementById('colorWheel');
        this.ctx = this.canvas.getContext('2d');
        this.handle = document.getElementById('wheelHandle');
        this.r = this.canvas.width / 2;
        this.cx = this.r;
        this.cy = this.r;
        this.drawWheel();
        this.attach();
      },
      drawWheel() {
        const img = this.ctx.createImageData(this.canvas.width, this.canvas.height);
        for (let y = 0; y < this.canvas.height; y++) {
          for (let x = 0; x < this.canvas.width; x++) {
            const dx = x - this.cx;
            const dy = y - this.cy;
            const dist = Math.sqrt(dx * dx + dy * dy);
            const idx = (y * this.canvas.width + x) * 4;
            if (dist <= this.r) {
              let h = (Math.atan2(dy, dx) * 180 / Math.PI + 360) % 360;
              let s = dist / this.r;
              const rgb = hsvToRgb(h, s, 1);
              img.data[idx] = rgb[0];
              img.data[idx + 1] = rgb[1];
              img.data[idx + 2] = rgb[2];
              img.data[idx + 3] = 255;
            } else {
              img.data[idx + 3] = 0;
            }
          }
        }
        this.ctx.putImageData(img, 0, 0);
      },
      moveHandleToHex(hex) {
        const rgb = hexToRgb(hex);
        if (!rgb) return;
        const hsv = rgbToHsv(rgb[0], rgb[1], rgb[2]);
        const angle = (hsv.h) * Math.PI / 180;
        const radius = Math.min(hsv.s, 1) * this.r;
        const x = this.cx + Math.cos(angle) * radius - 9;
        const y = this.cy + Math.sin(angle) * radius - 9;
        this.handle.style.left = x + 'px';
        this.handle.style.top = y + 'px';
      },
      pickAtClientPos(clientX, clientY) {
        const rect = this.canvas.getBoundingClientRect();
        const x = clientX - rect.left;
        const y = clientY - rect.top;
        const dx = x - this.cx;
        const dy = y - this.cy;
        const dist = Math.min(Math.sqrt(dx * dx + dy * dy), this.r);
        const ang = Math.atan2(dy, dx);
        const px = this.cx + Math.cos(ang) * dist;
        const py = this.cy + Math.sin(ang) * dist;
        this.handle.style.left = (px - 9) + 'px';
        this.handle.style.top = (py - 9) + 'px';
        const h = (ang * 180 / Math.PI + 360) % 360;
        const s = dist / this.r;
        const rgb = hsvToRgb(h, s, 1);
        const hex = rgbToHex(rgb[0], rgb[1], rgb[2]);
        setHex(hex, true);
      },
      attach() {
        let dragging = false;
        const onDown = (e) => {
          dragging = true;
          this.pickAtClientPos(e.clientX ?? e.touches[0].clientX, e.clientY ?? e.touches[0].clientY);
        };
        const onMove = (e) => {
          if (!dragging) return;
          e.preventDefault();
          this.pickAtClientPos(e.clientX ?? e.touches[0].clientX, e.clientY ?? e.touches[0].clientY);
        };
        const onUp = () => dragging = false;
        this.canvas.addEventListener('mousedown', onDown);
        this.canvas.addEventListener('mousemove', onMove);
        window.addEventListener('mouseup', onUp);
        this.canvas.addEventListener('touchstart', onDown, {
          passive: false
        });
        this.canvas.addEventListener('touchmove', onMove, {
          passive: false
        });
        window.addEventListener('touchend', onUp);
      }
    };

    function hsvToRgb(h, s, v) {
      let c = v * s;
      let x = c * (1 - Math.abs(((h / 60) % 2) - 1));
      let m = v - c;
      let r, g, b;
      if (0 <= h && h < 60) {
        r = c;
        g = x;
        b = 0
      } else if (60 <= h && h < 120) {
        r = x;
        g = c;
        b = 0
      } else if (120 <= h && h < 180) {
        r = 0;
        g = c;
        b = x
      } else if (180 <= h && h < 240) {
        r = 0;
        g = x;
        b = c
      } else if (240 <= h && h < 300) {
        r = x;
        g = 0;
        b = c
      } else {
        r = c;
        g = 0;
        b = x
      }
      return [Math.round((r + m) * 255), Math.round((g + m) * 255), Math.round((b + m) * 255)];
    }

    function rgbToHsv(r, g, b) {
      r /= 255;
      g /= 255;
      b /= 255;
      const max = Math.max(r, g, b),
        min = Math.min(r, g, b);
      let h, s, v = max;
      const d = max - min;
      s = max === 0 ? 0 : d / max;
      if (max === min) h = 0;
      else {
        switch (max) {
          case r:
            h = (g - b) / d + (g < b ? 6 : 0);
            break;
          case g:
            h = (b - r) / d + 2;
            break;
          case b:
            h = (r - g) / d + 4;
            break;
        }
        h *= 60;
      }
      return {
        h,
        s,
        v
      };
    }

    function rgbToHex(r, g, b) {
      return "#" + [r, g, b].map(x => x.toString(16).padStart(2, '0')).join('').toUpperCase();
    }

    function hexToRgb(hex) {
      const m = /^#?([0-9A-Fa-f]{6})$/.exec(hex);
      if (!m) return null;
      const n = parseInt(m[1], 16);
      return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
    }

    function setHex(hex, updateNameFromHex = true) {
      if (!/^#?[0-9A-Fa-f]{6}$/.test(hex)) return;
      if (hex[0] !== '#') hex = '#' + hex;
      document.getElementById('hexInput').value = hex.slice(1);
      document.getElementById('colorHexHidden').value = hex;
      document.getElementById('pickedHex').textContent = hex;
      const box = document.getElementById('colorPreview');
      const nameEl = document.getElementById('colorNamePreview');
      box.style.display = 'inline-block';
      box.style.backgroundColor = hex;
      wheel.moveHandleToHex(hex);

      const sel = document.getElementById('colorSelect');
      if (updateNameFromHex) {
        const near = nearestNamed(hex);
        if (near && near.dist <= 35) {
          sel.value = near.name;
          setActiveSwatch(near.name);
          nameEl.textContent = near.name;
        } else {
          sel.value = '';
          setActiveSwatch('');
          nameEl.textContent = 'Personalizado';
        }
      }
    }

    function bindHexInput() {
      const inp = document.getElementById('hexInput');
      inp.addEventListener('input', () => {
        const v = inp.value.trim();
        if (/^[0-9A-Fa-f]{6}$/.test(v)) setHex('#' + v, true);
      });
    }

    // Matriz de tallas -> Cantidad total
    function bindMatrizTallas() {
      const inputs = document.querySelectorAll('.talla-input');
      const sumaEl = document.getElementById('sumaTallas');
      const qtyInput = document.getElementById('cantidad_total');

      function recalc() {
        let s = 0;
        inputs.forEach(i => s += parseInt(i.value || '0', 10));
        sumaEl.textContent = s;
        if (s > 0) qtyInput.value = s;
      }
      inputs.forEach(i => i.addEventListener('input', recalc));
      recalc();
    }

    function rowTemplate(prefix, idx) {
      return `
        <div class="row g-2 align-items-end mb-2" data-row="${prefix}-${idx}">
          <div class="col-7">
            <label class="form-label mb-1">Ubicación</label>
            <select name="${prefix}_ubicacion[]" class="form-select">
              <option value="">Seleccionar...</option>
              <option value="Pecho">Pecho</option>
              <option value="Brazo">Brazo</option>
              <option value="Espalda">Espalda</option>
            </select>
          </div>
          <div class="col-3">
            <label class="form-label mb-1">Cantidad</label>
            <input type="number" name="${prefix}_cantidad[]" class="form-control" min="0" max="999" value="0">
          </div>
          <div class="col-2">
            <button type="button" class="btn btn-outline-danger w-100 remove-row" aria-label="Eliminar fila"><i class="fas fa-trash"></i></button>
          </div>
        </div>
      `;
    }

    function setupPersonalizacion() {
      const estList = document.getElementById('estList');
      const borList = document.getElementById('borList');
      const addEst = document.getElementById('addEst');
      const addBor = document.getElementById('addBor');
      let estIdx = 0,
        borIdx = 0;
      const MAX_ROWS = 15;

      function addRow(list, prefix, idx) {
        if (list.children.length >= MAX_ROWS) return;
        const wrap = document.createElement('div');
        wrap.innerHTML = rowTemplate(prefix, idx);
        list.appendChild(wrap);
        wrap.querySelector('.remove-row').addEventListener('click', () => wrap.remove());
      }
      addEst.addEventListener('click', () => addRow(estList, 'est', ++estIdx));
      addBor.addEventListener('click', () => addRow(borList, 'bor', ++borIdx));
      addRow(estList, 'est', ++estIdx);
      addRow(borList, 'bor', ++borIdx);
    }

    // Init
    document.addEventListener('DOMContentLoaded', function() {
      loadUbigeo(); // <-- ahora sí carga y llena los selects
      bindColorPreview();
      renderColorPalette();
      wheel.init();
      bindHexInput();

      const postedHex = <?php echo json_encode($_POST['color_hex'] ?? ''); ?>;
      const sel = document.getElementById('colorSelect');
      if (postedHex) setHex(postedHex, true);
      else if (sel.value) setHex(sel.selectedOptions[0].dataset.hex, true);
      else setHex('#000000', true);

      setupPersonalizacion();
      bindMatrizTallas();
    });
  </script>
</body>

</html>