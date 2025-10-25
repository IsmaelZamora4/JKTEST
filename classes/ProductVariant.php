<?php
class ProductVariant {
    private $conn;
    private $table_name = "product_variants";
    private $colors_table = "colors";
    private $sizes_table = "sizes";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener todas las variantes de un producto
    public function getVariantsByProduct($product_id) {
        $query = "SELECT pv.*, c.name as color_name, c.hex_code, s.name as size_name, s.description as size_description, s.price_modifier as size_price_modifier
                  FROM " . $this->table_name . " pv
                  LEFT JOIN " . $this->colors_table . " c ON pv.color_id = c.id
                  LEFT JOIN " . $this->sizes_table . " s ON pv.size_id = s.id
                  WHERE pv.product_id = ?
                  ORDER BY c.name, s.sort_order";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener colores disponibles para un producto
    public function getAvailableColors($product_id) {
        $query = "SELECT DISTINCT c.id, c.name, c.hex_code
                  FROM " . $this->table_name . " pv
                  JOIN " . $this->colors_table . " c ON pv.color_id = c.id
                  WHERE pv.product_id = ? AND pv.stock_quantity > 0
                  ORDER BY c.name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener todas las variantes con detalles para admin
    public function getAllWithDetails($product_filter = null, $search = '') {
        $query = "SELECT pv.*, p.name as product_name, c.name as color_name, c.hex_code as color_hex, s.name as size_name
                  FROM " . $this->table_name . " pv
                  JOIN products p ON pv.product_id = p.id
                  LEFT JOIN " . $this->colors_table . " c ON pv.color_id = c.id
                  LEFT JOIN " . $this->sizes_table . " s ON pv.size_id = s.id
                  WHERE 1=1";
        
        $params = [];
        
        if ($product_filter) {
            $query .= " AND pv.product_id = ?";
            $params[] = $product_filter;
        }
        
        if (!empty($search)) {
            $query .= " AND (p.name LIKE ? OR c.name LIKE ? OR s.name LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $query .= " ORDER BY p.name, c.name, s.sort_order";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear nueva variante
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (product_id, color_id, size_id, stock_quantity, price_modifier, variant_image_url, is_available) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['product_id'],
            $data['color_id'],
            $data['size_id'],
            $data['stock_quantity'],
            $data['price_modifier'] ?? 0.00,
            $data['variant_image_url'],
            $data['is_available']
        ]);
    }

    // Actualizar variante
    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET product_id = ?, color_id = ?, size_id = ?, stock_quantity = ?, 
                      price_modifier = ?, variant_image_url = ?, is_available = ?, updated_at = CURRENT_TIMESTAMP
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['product_id'],
            $data['color_id'],
            $data['size_id'],
            $data['stock_quantity'],
            $data['price_modifier'] ?? 0.00,
            $data['variant_image_url'],
            $data['is_available'],
            $id
        ]);
    }

    // Eliminar variante
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    // Actualizar solo stock por ID
    public function updateStock($id, $stock_quantity, $is_available = null, $updated_by = null) {
        if ($is_available !== null && $updated_by !== null) {
            $sql = "UPDATE " . $this->table_name . " SET stock_quantity = ?, is_available = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$stock_quantity, $is_available, $id]);
        } else {
            $sql = "UPDATE " . $this->table_name . " SET stock_quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$stock_quantity, $id]);
        }
    }
    
    public function getByProductAndColor($product_id, $color_id) {
        $sql = "SELECT pv.*, p.name as product_name, c.name as color_name, c.hex_code as color_hex, s.name as size_name
                FROM " . $this->table_name . " pv
                JOIN products p ON pv.product_id = p.id
                JOIN " . $this->colors_table . " c ON pv.color_id = c.id
                JOIN " . $this->sizes_table . " s ON pv.size_id = s.id
                WHERE pv.product_id = ? AND pv.color_id = ?
                ORDER BY s.sort_order ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$product_id, $color_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateImageByProductColor($product_id, $color_id, $image_url) {
        $sql = "UPDATE " . $this->table_name . " SET variant_image_url = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE product_id = ? AND color_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$image_url, $product_id, $color_id]);
    }

    // Obtener variante por ID
    public function getById($id) {
        $query = "SELECT pv.*, p.name as product_name, c.name as color_name, s.name as size_name
                  FROM " . $this->table_name . " pv
                  JOIN products p ON pv.product_id = p.id
                  LEFT JOIN " . $this->colors_table . " c ON pv.color_id = c.id
                  LEFT JOIN " . $this->sizes_table . " s ON pv.size_id = s.id
                  WHERE pv.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtener tallas disponibles para un producto y color específico
    public function getAvailableSizes($product_id, $color_id = null) {
        if ($color_id) {
            $query = "SELECT DISTINCT s.id, s.name, s.description, s.price_modifier, pv.stock_quantity
                      FROM " . $this->table_name . " pv
                      JOIN " . $this->sizes_table . " s ON pv.size_id = s.id
                      WHERE pv.product_id = ? AND pv.color_id = ? AND pv.stock_quantity > 0
                      ORDER BY s.sort_order";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $product_id);
            $stmt->bindParam(2, $color_id);
        } else {
            $query = "SELECT DISTINCT s.id, s.name, s.description, s.price_modifier
                      FROM " . $this->table_name . " pv
                      JOIN " . $this->sizes_table . " s ON pv.size_id = s.id
                      WHERE pv.product_id = ? AND pv.stock_quantity > 0
                      ORDER BY s.sort_order";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $product_id);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener una variante específica
    public function getVariant($product_id, $color_id, $size_id) {
        $query = "SELECT pv.*, c.name as color_name, c.hex_code, s.name as size_name, s.description as size_description
                  FROM " . $this->table_name . " pv
                  LEFT JOIN " . $this->colors_table . " c ON pv.color_id = c.id
                  LEFT JOIN " . $this->sizes_table . " s ON pv.size_id = s.id
                  WHERE pv.product_id = ? AND pv.color_id = ? AND pv.size_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->bindParam(2, $color_id);
        $stmt->bindParam(3, $size_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Calcular precio final de una variante
    public function calculateVariantPrice($base_price, $size_price_modifier, $variant_price_modifier) {
        return $base_price + $size_price_modifier + $variant_price_modifier;
    }

    // Obtener imagen de variante o imagen por defecto del producto
    public function getVariantImage($product_id, $color_id, $default_image) {
        $query = "SELECT variant_image_url FROM " . $this->table_name . " 
                  WHERE product_id = ? AND color_id = ? AND variant_image_url IS NOT NULL 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->bindParam(2, $color_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['variant_image_url'] : $default_image;
    }

    // Verificar stock disponible
    public function checkStock($product_id, $color_id, $size_id, $quantity = 1) {
        $query = "SELECT stock_quantity FROM " . $this->table_name . " 
                  WHERE product_id = ? AND color_id = ? AND size_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->bindParam(2, $color_id);
        $stmt->bindParam(3, $size_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && $result['stock_quantity'] >= $quantity;
    }

    // Actualizar stock de una variante por atributos
    public function updateStockByAttributes($product_id, $color_id, $size_id, $quantity_change) {
        $query = "UPDATE " . $this->table_name . " 
                  SET stock_quantity = stock_quantity + ? 
                  WHERE product_id = ? AND color_id = ? AND size_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $quantity_change);
        $stmt->bindParam(2, $product_id);
        $stmt->bindParam(3, $color_id);
        $stmt->bindParam(4, $size_id);
        
        return $stmt->execute();
    }

    // Obtener variante por atributos
    public function getByAttributes($product_id, $color_id, $size_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE product_id = ? AND color_id = ? AND size_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$product_id, $color_id, $size_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtener stock total de un producto (suma de todas las variantes)
    public function getTotalStock($product_id) {
        $query = "SELECT SUM(stock_quantity) as total_stock 
                  FROM " . $this->table_name . " 
                  WHERE product_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['total_stock'] : 0;
    }

    // Obtener variantes con stock bajo (menos de X unidades)
    public function getLowStockVariants($threshold = 5) {
        $query = "SELECT pv.*, p.name as product_name, c.name as color_name, s.name as size_name
                  FROM " . $this->table_name . " pv
                  JOIN products p ON pv.product_id = p.id
                  JOIN " . $this->colors_table . " c ON pv.color_id = c.id
                  JOIN " . $this->sizes_table . " s ON pv.size_id = s.id
                  WHERE pv.stock_quantity <= ? AND pv.stock_quantity > 0
                  ORDER BY pv.stock_quantity ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $threshold);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear múltiples variantes para un producto+color con diferentes tallas
    public function createMultipleSizes($product_id, $color_id, $sizes_data, $variant_image_url = null) {
        $this->conn->beginTransaction();
        
        try {
            foreach ($sizes_data as $size_data) {
                // Verificar si ya existe esta combinación
                $existing = $this->getByAttributes($product_id, $color_id, $size_data['size_id']);
                
                if (!$existing) {
                    $data = [
                        'product_id' => $product_id,
                        'color_id' => $color_id,
                        'size_id' => $size_data['size_id'],
                        'stock_quantity' => $size_data['stock_quantity'] ?? 0,
                        'price_modifier' => $size_data['price_modifier'] ?? 0.00,
                        'variant_image_url' => $variant_image_url,
                        'is_available' => $size_data['is_available'] ?? 1
                    ];
                    
                    if (!$this->create($data)) {
                        throw new Exception('Error al crear variante para talla ' . $size_data['size_id']);
                    }
                }
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    // Obtener tallas faltantes para un producto+color específico
    public function getMissingSizes($product_id, $color_id) {
        $query = "SELECT s.id, s.name, s.description, s.price_modifier, s.sort_order
                  FROM " . $this->sizes_table . " s
                  WHERE s.id NOT IN (
                      SELECT DISTINCT pv.size_id 
                      FROM " . $this->table_name . " pv 
                      WHERE pv.product_id = ? AND pv.color_id = ?
                  )
                  ORDER BY s.sort_order";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$product_id, $color_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
