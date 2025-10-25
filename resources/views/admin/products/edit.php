<?php
require_once __DIR__ . '/../../components/admin/header.php';
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'classes/Product.php';

$db = (new Database())->getConnection();
$productModel = new Product($db);
$errors = [];

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: /admin/products'); exit; }

$existing = $productModel->getById($id);
if (!$existing) { header('Location: /admin/products'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productModel->id = $id;
    $productModel->name = $_POST['name'] ?? $existing['name'];
    $productModel->description = $_POST['description'] ?? $existing['description'];
    $productModel->base_price = $_POST['base_price'] ?? $existing['base_price'];
    $productModel->category_id = $_POST['category_id'] ?? $existing['category_id'];
    $productModel->image_url = $existing['image_url'];
    $productModel->has_variants = isset($_POST['has_variants']) ? 1 : 0;
    $productModel->is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!empty($_FILES['image']['tmp_name'])) {
        $destDir = '/public/assets/images/products/';
        $filename = uniqid('prod_') . '_' . basename($_FILES['image']['name']);
        $target = __DIR__ . '/../../../../public' . $destDir . $filename;
        @mkdir(dirname($target), 0755, true);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $productModel->image_url = $destDir . $filename;
        }
    }

    if ($productModel->update()) {
        header('Location: /admin/products'); exit;
    } else {
        $errors[] = 'No se pudo actualizar';
    }
}

?>
<div class="container-fluid py-3">
    <h3>Editar Producto</h3>
    <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php echo implode('<br>', $errors); ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label>Nombre</label>
            <input class="form-control" name="name" required value="<?= htmlspecialchars($existing['name']) ?>">
        </div>
        <div class="mb-3">
            <label>Descripción</label>
            <textarea class="form-control" name="description"><?= htmlspecialchars($existing['description']) ?></textarea>
        </div>
        <div class="mb-3">
            <label>Precio base</label>
            <input class="form-control" name="base_price" type="number" step="0.01" value="<?= htmlspecialchars($existing['base_price']) ?>">
        </div>
        <div class="mb-3">
            <label>Imagen</label>
            <?php if (!empty($existing['image_url'])): ?><div><img src="<?= htmlspecialchars($existing['image_url']) ?>" style="height:60px;margin-bottom:8px"></div><?php endif; ?>
            <input type="file" name="image" class="form-control">
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="has_variants" class="form-check-input" id="has_variants" <?= $existing['has_variants'] ? 'checked' : '' ?>>
            <label for="has_variants" class="form-check-label">Tiene variantes</label>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" <?= $existing['is_active'] ? 'checked' : '' ?>>
            <label for="is_active" class="form-check-label">Activo</label>
        </div>
        <button class="btn btn-primary">Guardar</button>
    </form>
</div>

<?php require_once __DIR__ . '/../../components/admin/footer.php'; ?>
