<?php
/**
 * classes/ProductSize.php
 * Clase para manejo de tallas y stock de productos tradicionales (sin variantes de color)
 */

class ProductSize {
    private $conn;
    private $table_name = "product_sizes";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtener todas las tallas de un producto
     */
    public function getByProduct($product_id) {
        $query = "SELECT ps.*, s.name as size_name 
                  FROM " . $this->table_name . " ps
                  JOIN sizes s ON ps.size_id = s.id
                  WHERE ps.product_id = ? AND ps.stock_quantity > 0
                  ORDER BY s.sort_order ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$product_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener talla específica de un producto
     */
    public function getByProductAndSize($product_id, $size_id) {
        $query = "SELECT ps.*, s.name as size_name 
                  FROM " . $this->table_name . " ps
                  JOIN sizes s ON ps.size_id = s.id
                  WHERE ps.product_id = ? AND ps.size_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$product_id, $size_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crear nueva talla para producto
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (product_id, size_id, stock_quantity, is_available) 
                  VALUES (?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['product_id'],
            $data['size_id'],
            $data['stock_quantity'],
            $data['is_available']
        ]);
    }

    /**
     * Obtener todas las tallas con detalles para admin
     */
    public function getAllWithDetails($product_filter = null, $search = '') {
        $query = "SELECT ps.*, p.name as product_name, s.name as size_name
                  FROM " . $this->table_name . " ps
                  JOIN products p ON ps.product_id = p.id
                  JOIN sizes s ON ps.size_id = s.id
                  WHERE p.has_variants = 0";

        $params = [];

        if ($product_filter) {
            $query .= " AND ps.product_id = ?";
            $params[] = $product_filter;
        }

        if (!empty($search)) {
            $query .= " AND (p.name LIKE ? OR s.name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY p.name, s.sort_order";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener stock total de un producto
     */
    public function getTotalStock($product_id) {
        $query = "SELECT SUM(stock_quantity) as total_stock 
                  FROM " . $this->table_name . " 
                  WHERE product_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$product_id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['total_stock'] : 0;
    }

    /**
     * Verificar disponibilidad de stock
     */
    public function checkStock($product_id, $size_id, $quantity) {
        $query = "SELECT stock_quantity 
                  FROM " . $this->table_name . " 
                  WHERE product_id = ? AND size_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$product_id, $size_id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            return false;
        }
        
        return (int)$result['stock_quantity'] >= $quantity;
    }

    /**
     * Obtener tallas con stock bajo (menos de 5 unidades)
     */
    public function getLowStockSizes($product_id, $threshold = 5) {
        $query = "SELECT ps.*, s.name as size_name 
                  FROM " . $this->table_name . " ps
                  JOIN sizes s ON ps.size_id = s.id
                  WHERE ps.product_id = ? AND ps.stock_quantity <= ? AND ps.stock_quantity > 0
                  ORDER BY ps.stock_quantity ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$product_id, $threshold]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar stock específico (para administración)
     */
    public function setStock($product_id, $size_id, $new_stock) {
        $query = "UPDATE " . $this->table_name . " 
                  SET stock_quantity = ? 
                  WHERE product_id = ? AND size_id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$new_stock, $product_id, $size_id]);
    }

    /**
     * Actualizar talla
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET product_id = ?, size_id = ?, stock_quantity = ?, 
                      is_available = ?, updated_at = CURRENT_TIMESTAMP
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['product_id'],
            $data['size_id'],
            $data['stock_quantity'],
            $data['is_available'],
            $id
        ]);
    }

    /**
     * Eliminar talla por ID
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    /**
     * Actualizar solo stock por ID
     */
    public function updateStockById($id, $stock_quantity) {
        $query = "UPDATE " . $this->table_name . " 
                  SET stock_quantity = ?, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$stock_quantity, $id]);
    }

    /**
     * Obtener talla por ID
     */
    public function getById($id) {
        $query = "SELECT ps.*, p.name as product_name, s.name as size_name
                  FROM " . $this->table_name . " ps
                  JOIN products p ON ps.product_id = p.id
                  JOIN sizes s ON ps.size_id = s.id
                  WHERE ps.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar si existe combinación producto-talla
     */
    public function getByAttributes($product_id, $size_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE product_id = ? AND size_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$product_id, $size_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener todas las tallas disponibles para un producto (incluyendo sin stock)
     */
    public function getAllByProduct($product_id) {
        $query = "SELECT ps.*, s.name as size_name 
                  FROM " . $this->table_name . " ps
                  JOIN sizes s ON ps.size_id = s.id
                  WHERE ps.product_id = ?
                  ORDER BY s.sort_order ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$product_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
