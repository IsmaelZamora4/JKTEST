<?php
/**
 * Clase Size para manejar las tallas de productos
 */
class Size {
    private $conn;
    private $table_name = "sizes";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtener todas las tallas (para admin) o solo activas (para tienda)
     */
    public function getAll($active_only = false) {
        $query = "SELECT * FROM " . $this->table_name;
        if ($active_only) {
            $query .= " WHERE is_active = 1";
        }
        $query .= " ORDER BY sort_order ASC, name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener tallas disponibles para un producto específico
     */
    public function getByProduct($product_id) {
        $query = "SELECT s.*, ps.stock_quantity, ps.is_available 
                  FROM " . $this->table_name . " s
                  INNER JOIN product_sizes ps ON s.id = ps.size_id
                  WHERE ps.product_id = :product_id 
                  AND s.is_active = 1 
                  AND ps.is_available = 1
                  ORDER BY s.sort_order ASC, s.name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener una talla por ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar stock de una talla específica para un producto
     */
    public function checkStock($product_id, $size_id) {
        $query = "SELECT stock_quantity, is_available 
                  FROM product_sizes 
                  WHERE product_id = :product_id AND size_id = :size_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':size_id', $size_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['is_available'] && $result['stock_quantity'] > 0) {
            return $result['stock_quantity'];
        }
        
        return 0;
    }

    /**
     * Actualizar stock de una talla
     */
    public function updateStock($product_id, $size_id, $quantity) {
        $query = "UPDATE product_sizes 
                  SET stock_quantity = stock_quantity - :quantity,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE product_id = :product_id AND size_id = :size_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':size_id', $size_id);
        
        return $stmt->execute();
    }

    /**
     * Crear nueva talla
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, description, price_modifier, sort_order, is_active) 
                  VALUES (:name, :description, :price_modifier, :sort_order, :is_active)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':price_modifier', $data['price_modifier']);
        $stmt->bindParam(':sort_order', $data['sort_order']);
        $stmt->bindParam(':is_active', $data['is_active']);
        
        return $stmt->execute();
    }

    /**
     * Actualizar talla existente
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, 
                      description = :description, 
                      price_modifier = :price_modifier, 
                      sort_order = :sort_order, 
                      is_active = :is_active,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':price_modifier', $data['price_modifier']);
        $stmt->bindParam(':sort_order', $data['sort_order']);
        $stmt->bindParam(':is_active', $data['is_active']);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Eliminar talla
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Actualizar estado de talla
     */
    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_active = :status, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>
