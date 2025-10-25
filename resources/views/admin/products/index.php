<?php
require_once __DIR__ . '/../../components/admin/header.php';
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'classes/Product.php';

$db = (new Database())->getConnection();
$productModel = new Product($db);
$products = $productModel->getAll(1, 50, '', null, false);

?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Productos</h3>
        <a href="/admin/products/create" class="btn btn-warning">+ Agregar Producto</a>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Categoria</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($products as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['id']) ?></td>
                        <td>
                            <?php if (!empty($p['image_url'])): ?>
                                <img src="<?= htmlspecialchars($p['image_url']) ?>" style="height:40px;" />
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td>S/ <?= number_format($p['base_price'],2) ?></td>
                        <td><?= htmlspecialchars($p['category_name'] ?? '') ?></td>
                        <td><?= $p['is_active'] ? 'Sí' : 'No' ?></td>
                        <td>
                            <a href="/admin/products/edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                            <form action="/admin/products/delete" method="POST" style="display:inline-block" onsubmit="return confirm('Eliminar producto?');">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger">Borrar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/admin/footer.php'; ?>
