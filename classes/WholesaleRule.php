<?php
/**
 * Clase WholesaleRule para manejar reglas de descuentos mayoristas
 */
class WholesaleRule {
    private $conn;
    private $table_name = "wholesale_rules";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtener reglas de descuento para una categoría
     */
    public function getRulesByCategory($category_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE category_id = :category_id 
                  AND rule_type = 'category' 
                  AND is_active = 1 
                  ORDER BY min_quantity ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener reglas de descuento para un producto específico
     */
    public function getRulesByProduct($product_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE product_id = :product_id 
                  AND rule_type = 'product' 
                  AND is_active = 1 
                  ORDER BY min_quantity ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcular precio mayorista basado en cantidad y reglas
     */
    public function calculateWholesalePrice($product_id, $category_id, $base_price, $quantity) {
        // Primero buscar reglas específicas del producto
        $product_rules = $this->getRulesByProduct($product_id);
        
        // Si no hay reglas específicas, usar reglas de categoría
        if (empty($product_rules)) {
            $rules = $this->getRulesByCategory($category_id);
        } else {
            $rules = $product_rules;
        }

        $best_discount = 0;
        
        // Encontrar el mejor descuento aplicable
        foreach ($rules as $rule) {
            if ($quantity >= $rule['min_quantity']) {
                $best_discount = max($best_discount, $rule['discount_percentage']);
            }
        }

        // Aplicar descuento si existe
        if ($best_discount > 0) {
            $discount_amount = ($base_price * $best_discount) / 100;
            return $base_price - $discount_amount;
        }

        return $base_price;
    }

    /**
     * Obtener todas las reglas con filtros para admin
     */
    public function getAll($category_filter = null, $status_filter = null) {
        $query = "SELECT wr.*, 
                         c.name as category_name
                  FROM " . $this->table_name . " wr
                  LEFT JOIN categories c ON wr.category_id = c.id
                  WHERE 1=1";
        
        $params = [];
        
        if ($category_filter !== null) {
            $query .= " AND wr.category_id = ?";
            $params[] = $category_filter;
        }
        
        if ($status_filter !== null) {
            $query .= " AND wr.is_active = ?";
            $params[] = $status_filter;
        }
        
        $query .= " ORDER BY wr.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener regla por ID
     */
    public function getById($id) {
        $query = "SELECT wr.*, 
                         c.name as category_name
                  FROM " . $this->table_name . " wr
                  LEFT JOIN categories c ON wr.category_id = c.id
                  WHERE wr.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crear nueva regla
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (category_id, min_quantity, discount_type, discount_value, is_active) 
                  VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['category_id'],
            $data['min_quantity'],
            $data['discount_type'],
            $data['discount_value'],
            $data['is_active']
        ]);
    }

    /**
     * Actualizar regla
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET category_id = ?, min_quantity = ?, discount_type = ?, 
                      discount_value = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['category_id'],
            $data['min_quantity'],
            $data['discount_type'],
            $data['discount_value'],
            $data['is_active'],
            $id
        ]);
    }

    /**
     * Eliminar regla
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    /**
     * Actualizar estado
     */
    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_active = ?, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$status, $id]);
    }

    /**
     * Crear nueva regla de descuento
     */
    public function createRule($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (category_id, product_id, rule_type, min_quantity, discount_percentage) 
                  VALUES (:category_id, :product_id, :rule_type, :min_quantity, :discount_percentage)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':category_id', $data['category_id']);
        $stmt->bindParam(':product_id', $data['product_id']);
        $stmt->bindParam(':rule_type', $data['rule_type']);
        $stmt->bindParam(':min_quantity', $data['min_quantity']);
        $stmt->bindParam(':discount_percentage', $data['discount_percentage']);
        
        return $stmt->execute();
    }

    /**
     * Actualizar regla existente
     */
    public function updateRule($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET category_id = :category_id,
                      product_id = :product_id,
                      rule_type = :rule_type,
                      min_quantity = :min_quantity,
                      discount_percentage = :discount_percentage,
                      is_active = :is_active
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':category_id', $data['category_id']);
        $stmt->bindParam(':product_id', $data['product_id']);
        $stmt->bindParam(':rule_type', $data['rule_type']);
        $stmt->bindParam(':min_quantity', $data['min_quantity']);
        $stmt->bindParam(':discount_percentage', $data['discount_percentage']);
        $stmt->bindParam(':is_active', $data['is_active']);
        
        return $stmt->execute();
    }

    /**
     * Eliminar regla (desactivar)
     */
    public function deleteRule($id) {
        $query = "UPDATE " . $this->table_name . " SET is_active = 0 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
}
?>
