<?php
require_once BASE_PATH . 'config/config.php';
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'classes/StoreRating.php';
require_once BASE_PATH . 'classes/Category.php';

$database = new Database();
$db = $database->getConnection();
$storeRating = new StoreRating($db);
$category = new Category($db);

$message = '';
$error = '';

// Procesar formulario de calificación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = sanitize_input($_POST['customer_name']);
    $customer_email = sanitize_input($_POST['customer_email']);
    $rating = (int)$_POST['rating'];
    $comment = sanitize_input($_POST['comment']);

    if (empty($customer_name) || empty($customer_email) || $rating < 1 || $rating > 5) {
        $error = 'Por favor completa todos los campos obligatorios correctamente.';
    } elseif ($storeRating->hasRated($customer_email)) {
        $error = 'Ya has enviado una calificación anteriormente.';
    } else {
        if ($storeRating->addRating($customer_name, $customer_email, $rating, $comment)) {
            $message = 'Gracias por tu calificación. Será revisada y publicada pronto.';
        } else {
            $error = 'Error al enviar la calificación. Inténtalo nuevamente.';
        }
    }
}

// Obtener categorías para el menú
$categories = $category->getAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Califica Nuestra Tienda - JK Grupo Textil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/variables.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    <link href="assets/css/components.css" rel="stylesheet">
    <link href="assets/css/layout.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
    <link href="assets/css/utilities.css" rel="stylesheet">
</head>

<body>
    <?php include COMPONENT_PATH . 'header.php'; ?>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center mb-5">
                        <h1 class="display-5 fw-bold text-dark mb-3">Califica Nuestra Tienda</h1>
                        <p class="lead text-muted">Tu opinión es muy importante para nosotros</p>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="customer_name" class="form-label fw-bold">Nombre *</label>
                                        <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="customer_email" class="form-label fw-bold">Email *</label>
                                        <input type="email" class="form-control" id="customer_email" name="customer_email" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold">Calificación *</label>
                                    <div class="rating-input">
                                        <div class="star-rating">
                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                                <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                                <label for="star<?php echo $i; ?>" class="star">
                                                    <i class="fas fa-star"></i>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                        <small class="text-muted">Haz clic en las estrellas para calificar</small>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="comment" class="form-label fw-bold">Comentario</label>
                                    <textarea class="form-control" id="comment" name="comment" rows="4"
                                        placeholder="Cuéntanos sobre tu experiencia con JK Grupo Textil..."></textarea>
                                    <small class="text-muted">Opcional: Comparte más detalles sobre tu experiencia</small>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i>
                                        Enviar Calificación
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include   COMPONENT_PATH . 'footer.php'; ?>

    <style>
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            margin-bottom: 10px;
        }

        .star-rating input[type="radio"] {
            display: none;
        }

        .star-rating label {
            cursor: pointer;
            font-size: 2rem;
            color: #ddd;
            transition: color 0.2s;
            margin: 0 2px;
        }

        .star-rating label:hover,
        .star-rating label:hover~label,
        .star-rating input[type="radio"]:checked~label {
            color: #ffc107;
        }

        .rating-input {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
    </style>
</body>

</html>