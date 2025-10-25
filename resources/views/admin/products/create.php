<?php
require_once __DIR__ . '/../../components/admin/header.php';
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'classes/Product.php';

$db = (new Database())->getConnection();
$product = new Product($db);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product->name = $_POST['name'] ?? '';
    $product->description = $_POST['description'] ?? '';
    $product->base_price = $_POST['base_price'] ?? 0;
    $product->category_id = $_POST['category_id'] ?? null;
    $product->image_url = '';
    $product->has_variants = isset($_POST['has_variants']) ? 1 : 0;
    $product->is_active = isset($_POST['is_active']) ? 1 : 0;

    // handle upload
    if (!empty($_FILES['image']['tmp_name'])) {
        $destDir = '/public/assets/images/products/';
        $filename = uniqid('prod_') . '_' . basename($_FILES['image']['name']);
        $target = __DIR__ . '/../../../../public' . $destDir . $filename;
        @mkdir(dirname($target), 0755, true);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $product->image_url = $destDir . $filename;
        }
    }

    if ($product->create()) {
        header('Location: /admin/products');
        exit;
    } else {
        $errors[] = 'No se pudo crear el producto';
    }
}

?>
<div class="container-fluid py-3">
    <h3>Agregar Producto</h3>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php echo implode('<br>', $errors); ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label>Nombre</label>
            <input class="form-control" name="name" required>
        </div>
        <div class="mb-3">
            <label>Descripción</label>
            <textarea class="form-control" name="description"></textarea>
        </div>
        <div class="mb-3">
            <label>Precio base</label>
            <input class="form-control" name="base_price" type="number" step="0.01" value="0">
        </div>
        <div class="mb-3">
            <label>Imagen</label>
            <input type="file" name="image" class="form-control">
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="has_variants" class="form-check-input" id="has_variants">
            <label for="has_variants" class="form-check-label">Tiene variantes</label>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" checked>
            <label for="is_active" class="form-check-label">Activo</label>
        </div>
        <button class="btn btn-primary">Crear</button>
    </form>
</div>

<?php require_once __DIR__ . '/../../components/admin/footer.php'; ?>
