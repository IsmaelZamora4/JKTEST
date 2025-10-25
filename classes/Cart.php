<?php
/**
 * classes/Cart.php
 * Clase para manejo del carrito - versión actualizada con cookie persistente
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Cart {
    private $conn;
    private $session_id;

    public function __construct($db) {
        $this->conn = $db;
        $this->session_id = $this->getOrCreateSessionId();
    }

    /**
     * Obtener o crear ID de sesión para el carrito
     * Ahora: restaura desde cookie 'sgu_cart_token' si existe; si no, crea y setea cookie.
     */
    private function getOrCreateSessionId() {
        // 1) Si ya está en sesión, devolverlo
        if (isset($_SESSION['cart_session_id']) && !empty($_SESSION['cart_session_id'])) {
            return $_SESSION['cart_session_id'];
        }

        // 2) Si hay cookie persistente, restaurarla en la sesión y asegurar fila en DB
        if (isset($_COOKIE['sgu_cart_token']) && !empty($_COOKIE['sgu_cart_token'])) {
            $token = $_COOKIE['sgu_cart_token'];
            $_SESSION['cart_session_id'] = $token;

            // Asegurar existencia en DB
            $check = $this->conn->prepare("SELECT id FROM cart_sessions WHERE session_id = ? LIMIT 1");
            $check->execute([$token]);
            if ($check->rowCount() === 0) {
                $ins = $this->conn->prepare("INSERT INTO cart_sessions (session_id) VALUES (?)");
                $ins->execute([$token]);
            }

            return $_SESSION['cart_session_id'];
        }

        // 3) No existe: crear nuevo token en sesión y DB, y setear cookie persistente
        $newToken = uniqid('cart_', true);
        $_SESSION['cart_session_id'] = $newToken;

        $insert = $this->conn->prepare("INSERT INTO cart_sessions (session_id) VALUES (?)");
        $insert->execute([$newToken]);

        // Cookie por 30 días; Secure si estamos en HTTPS
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        setcookie('sgu_cart_token', $newToken, time() + 30*24*3600, '/', '', $secure, true);

        return $_SESSION['cart_session_id'];
    }

    /**
     * Agregar producto al carrito con talla
     */
    public function addItem($product_id, $quantity = 1, $size_id = null, $color_id = null) {
        // DEBUG: Log de entrada
        error_log("=== DEBUG Cart::addItem ===");
        error_log("Product ID: " . $product_id);
        error_log("Quantity: " . $quantity);
        error_log("Size ID: " . ($size_id ?? 'NULL'));
        error_log("Color ID: " . ($color_id ?? 'NULL'));
        error_log("Session ID: " . $this->session_id);
        
        // Obtener precio del producto
        $price_query = "SELECT base_price FROM products WHERE id = ? AND is_active = 1";
        $price_stmt = $this->conn->prepare($price_query);
        $price_stmt->execute([$product_id]);
        $product = $price_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            error_log("ERROR: Product not found or inactive");
            return false; // Producto no encontrado o inactivo
        }
        
        $base_price = (float)$product['base_price'];
        
        // Obtener modificador de precio por talla si existe
        $price_modifier = 0;
        if ($size_id) {
            $size_query = "SELECT price_modifier FROM sizes WHERE id = ?";
            $size_stmt = $this->conn->prepare($size_query);
            $size_stmt->execute([$size_id]);
            $size = $size_stmt->fetch(PDO::FETCH_ASSOC);
            if ($size) {
                $price_modifier = (float)$size['price_modifier'];
            }
        }
        
        $final_price = $base_price + $price_modifier;
        error_log("Final price: " . $final_price);

        // Verificar si el producto con la misma talla y color ya existe en el carrito
        $query = "SELECT id, quantity FROM cart_items WHERE session_id = ? AND product_id = ? AND (size_id = ? OR (size_id IS NULL AND ? IS NULL)) AND (color_id = ? OR (color_id IS NULL AND ? IS NULL))";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->session_id, $product_id, $size_id, $size_id, $color_id, $color_id]);
        
        error_log("Checking for existing item with query: " . $query);
        error_log("Query params: [" . $this->session_id . ", " . $product_id . ", " . ($size_id ?? 'NULL') . ", " . ($size_id ?? 'NULL') . ", " . ($color_id ?? 'NULL') . ", " . ($color_id ?? 'NULL') . "]");
        error_log("Found existing items: " . $stmt->rowCount());

        if ($stmt->rowCount() > 0) {
            // Actualizar cantidad existente
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $new_quantity = $row['quantity'] + $quantity;
            error_log("Updating existing item ID " . $row['id'] . " from quantity " . $row['quantity'] . " to " . $new_quantity);

            $update_query = "UPDATE cart_items SET quantity = ?, price = ?, updated_at = NOW() WHERE id = ?";
            $update_stmt = $this->conn->prepare($update_query);
            $result = $update_stmt->execute([$new_quantity, $final_price, $row['id']]);
            error_log("Update result: " . ($result ? 'SUCCESS' : 'FAILED'));
            return $result;
        } else {
            // Agregar nuevo producto con talla y color
            error_log("Inserting new cart item");
            $insert_query = "INSERT INTO cart_items (session_id, product_id, size_id, color_id, quantity, price) VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $this->conn->prepare($insert_query);
            $result = $insert_stmt->execute([$this->session_id, $product_id, $size_id, $color_id, $quantity, $final_price]);
            error_log("Insert result: " . ($result ? 'SUCCESS' : 'FAILED'));
            if ($result) {
                error_log("New cart item ID: " . $this->conn->lastInsertId());
            }
            return $result;
        }
    }

    /**
     * Actualizar cantidad de un producto en el carrito por cart_item_id
     */
    public function updateQuantity($cart_item_id, $quantity) {
        if ($quantity <= 0) {
            return $this->removeItemById($cart_item_id);
        }

        $query = "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ? AND session_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$quantity, $cart_item_id, $this->session_id]);
    }

    /**
     * Actualizar cantidad de un producto en el carrito por product_id (método legacy)
     */
    public function updateQuantityByProduct($product_id, $quantity) {
        if ($quantity <= 0) {
            return $this->removeItem($product_id);
        }

        $query = "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE session_id = ? AND product_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$quantity, $this->session_id, $product_id]);
    }

    /**
     * Eliminar producto del carrito por product_id
     */
    public function removeItem($product_id) {
        $query = "DELETE FROM cart_items WHERE session_id = ? AND product_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$this->session_id, $product_id]);
    }

    /**
     * Eliminar item específico del carrito por cart_item_id
     */
    public function removeItemById($cart_item_id) {
        $query = "DELETE FROM cart_items WHERE id = ? AND session_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$cart_item_id, $this->session_id]);
    }

    /**
     * Obtener todos los productos del carrito con información de tallas
     */
    public function getItems() {
        $query = "SELECT ci.id as cart_item_id, ci.product_id, ci.size_id, ci.color_id, ci.quantity, ci.price,
                         p.name, p.base_price, p.image_url, p.has_variants,
                         s.name as size_name, s.price_modifier, c.name as color_name,
                         pv.variant_image_url,
                         999 as stock_quantity
                  FROM cart_items ci 
                  JOIN products p ON ci.product_id = p.id 
                  LEFT JOIN sizes s ON ci.size_id = s.id
                  LEFT JOIN colors c ON ci.color_id = c.id
                  LEFT JOIN product_variants pv ON ci.product_id = pv.product_id AND ci.color_id = pv.color_id AND ci.size_id = pv.size_id
                  LEFT JOIN product_sizes ps ON ci.product_id = ps.product_id AND ci.size_id = ps.size_id
                  WHERE ci.session_id = ? AND p.is_active = 1
                  ORDER BY ci.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->session_id]);

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular precio final considerando modificadores de talla y determinar imagen correcta
        foreach ($items as &$item) {
            // Usar el precio guardado en cart_items si existe, sino calcular
            if (isset($item['price']) && $item['price'] > 0) {
                $item['final_price'] = (float)$item['price'];
            } else {
                $base_price = (float)$item['base_price'];
                $price_modifier = (float)($item['price_modifier'] ?? 0);
                $item['final_price'] = $base_price + $price_modifier;
            }
            $item['line_total'] = $item['final_price'] * $item['quantity'];
            
            // Determinar la imagen correcta: variante si existe, sino imagen del producto
            $item['display_image'] = !empty($item['variant_image_url']) ? $item['variant_image_url'] : $item['image_url'];
        }
        
        return $items;
    }

    /**
     * Obtener total del carrito considerando modificadores de precio por talla
     */
    public function getTotal() {
        $query = "SELECT SUM(ci.quantity * (p.base_price + COALESCE(s.price_modifier, 0))) as total 
                  FROM cart_items ci 
                  JOIN products p ON ci.product_id = p.id 
                  LEFT JOIN sizes s ON ci.size_id = s.id
                  WHERE ci.session_id = ? AND p.is_active = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->session_id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ? (float)$row['total'] : 0;
    }

    /**
     * Obtener resumen del carrito (subtotal, envío, total)
     */
    public function getSummary() {
        $subtotal = $this->getTotal();
        $shipping = ($subtotal >= 150) ? 0 : 15; // Envío gratis para compras >= S/150
        $tax = $subtotal * 0.18; // IGV 18% (Perú)
        $total = $subtotal + $shipping + $tax;
        
        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total,
            'items_count' => $this->getTotalItems()
        ];
    }

    /**
     * Obtener cantidad total de productos
     */
    public function getTotalItems() {
        $query = "SELECT SUM(quantity) as total_items 
                  FROM cart_items 
                  WHERE session_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->session_id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_items'] ? (int)$row['total_items'] : 0;
    }

    /**
     * Limpiar carrito
     */
    public function clearCart() {
        $query = "DELETE FROM cart_items WHERE session_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$this->session_id]);
    }

    /**
     * Verificar disponibilidad de stock
     */
    public function validateStock() {
        $items = $this->getItems();
        $errors = [];

        foreach ($items as $item) {
            if ($item['quantity'] > $item['stock_quantity']) {
                $errors[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'requested' => $item['quantity'],
                    'available' => $item['stock_quantity']
                ];
            }
        }

        return $errors;
    }

    /**
     * Obtener resumen del carrito para checkout
     */
    public function getCheckoutSummary() {
        $items = $this->getItems();
        $subtotal = $this->getTotal();
        $shipping = $subtotal >= 150 ? 0 : 15; // Envío gratis en compras +S/150
        $tax = $subtotal * 0.18; // IGV 18% (Perú)
        $total = $subtotal + $shipping + $tax;

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total,
            'total_items' => $this->getTotalItems()
        ];
    }
}
?>
