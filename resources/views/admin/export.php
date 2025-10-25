<?php

require_once BASE_PATH . 'config/config.php';
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'classes/Auth.php';
require_once BASE_PATH . 'classes/Product.php';
require_once BASE_PATH . 'classes/Category.php';
require_once BASE_PATH . 'classes/Size.php';
require_once BASE_PATH . 'classes/Color.php';
vrequire_once BASE_PATH . 'classes/ProductVariant.php';

// Inicializar conexión a la base de datos
$database = new Database();
$pdo = $database->getConnection();

$auth = new Auth($pdo);
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$type = $_GET['type'] ?? 'products';

// Configurar headers para descarga CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $type . '_' . date('Y-m-d_H-i-s') . '.csv"');

// Crear output stream
$output = fopen('php://output', 'w');

// BOM para UTF-8 (para Excel)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

switch ($type) {
    case 'products':
        $product = new Product($pdo);
        $products = $product->getAll(1, 1000, '', null);
        
        // Headers CSV
        fputcsv($output, [
            'ID',
            'Nombre',
            'Descripción',
            'Precio Base',
            'Categoría',
            'Tiene Variantes',
            'Estado',
            'Fecha Creación',
            'Imagen'
        ]);
        
        // Datos
        foreach ($products as $prod) {
            fputcsv($output, [
                $prod['id'],
                $prod['name'],
                $prod['description'],
                $prod['base_price'],
                $prod['category_name'] ?? 'Sin categoría',
                $prod['has_variants'] ? 'Sí' : 'No',
                $prod['is_active'] ? 'Activo' : 'Inactivo',
                $prod['created_at'],
                $prod['image_url'] ?? ''
            ]);
        }
        break;
        
    case 'categories':
        $category = new Category($pdo);
        $categories = $category->getAll();
        
        // Headers CSV
        fputcsv($output, [
            'ID',
            'Nombre',
            'Descripción',
            'Estado',
            'Fecha Creación'
        ]);
        
        // Datos
        foreach ($categories as $cat) {
            fputcsv($output, [
                $cat['id'],
                $cat['name'],
                $cat['description'] ?? '',
                $cat['is_active'] ? 'Activo' : 'Inactivo',
                $cat['created_at'] ?? ''
            ]);
        }
        break;
        
    case 'variants':
        $variant = new ProductVariant($pdo);
        $variants = $variant->getAllWithDetails();
        
        // Headers CSV
        fputcsv($output, [
            'ID',
            'Producto',
            'Color',
            'Talla',
            'Stock',
            'Precio Modificador',
            'Imagen',
            'Fecha Creación'
        ]);
        
        // Datos
        foreach ($variants as $var) {
            fputcsv($output, [
                $var['id'],
                $var['product_name'] ?? '',
                $var['color_name'] ?? '',
                $var['size_name'] ?? '',
                $var['stock'] ?? 0,
                $var['price_modifier'] ?? 0,
                $var['image_url'] ?? '',
                $var['created_at'] ?? ''
            ]);
        }
        break;
        
    case 'dashboard':
        // Estadísticas del dashboard
        $product = new Product($pdo);
        $category = new Category($pdo);
        $size = new Size($pdo);
        $color = new Color($pdo);
        $variant = new ProductVariant($pdo);
        
        $total_products = count($product->getAll(1, 1000, '', null));
        $active_products = count($product->getAll(1, 1000, '', null, true));
        $total_categories = count($category->getAll());
        $total_sizes = count($size->getAll());
        $total_colors = count($color->getAll());
        $total_variants = count($variant->getAllWithDetails());
        
        // Headers CSV
        fputcsv($output, [
            'Métrica',
            'Valor',
            'Fecha Exportación'
        ]);
        
        // Datos
        $date = date('Y-m-d H:i:s');
        fputcsv($output, ['Total Productos', $total_products, $date]);
        fputcsv($output, ['Productos Activos', $active_products, $date]);
        fputcsv($output, ['Total Categorías', $total_categories, $date]);
        fputcsv($output, ['Total Tallas', $total_sizes, $date]);
        fputcsv($output, ['Total Colores', $total_colors, $date]);
        fputcsv($output, ['Total Variantes', $total_variants, $date]);
        break;
        
    default:
        fputcsv($output, ['Error', 'Tipo de exportación no válido']);
        break;
}

fclose($output);
exit();
?>
