<?php
/**
 * Clase Service para manejar los servicios de personalización
 */
class Service {
    private $conn;
    private $table_name = "services";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtener todos los servicios con filtros para admin
     */
    public function getAll($status_filter = null, $search = '') {
        $query = "SELECT * FROM " . $this->table_name . " WHERE 1=1";
        $params = [];
        
        if ($status_filter !== null) {
            $query .= " AND is_active = ?";
            $params[] = $status_filter;
        }
        
        if (!empty($search)) {
            $query .= " AND (name LIKE ? OR description LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener todos los servicios activos (para frontend)
     */
    public function getAllActive() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE is_active = 1 
                  ORDER BY name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener servicios disponibles para un producto específico
     */
    public function getByProduct($product_id) {
        $query = "SELECT s.*, ps.is_default 
                  FROM " . $this->table_name . " s
                  INNER JOIN product_services ps ON s.id = ps.service_id
                  WHERE ps.product_id = :product_id 
                  AND s.is_active = 1
                  ORDER BY ps.is_default DESC, s.name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener un servicio por ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Calcular precio con modificador de servicio
     */
    public function calculatePrice($base_price, $service_id) {
        $service = $this->getById($service_id);
        
        if (!$service) {
            return $base_price;
        }
        
        $modifier = $service['price_modifier'];
        
        if ($modifier > 0) {
            // Añadir porcentaje o cantidad fija
            if ($modifier < 1) {
                // Es un porcentaje (ej: 0.15 = 15%)
                return $base_price * (1 + $modifier);
            } else {
                // Es una cantidad fija
                return $base_price + $modifier;
            }
        } else if ($modifier < 0) {
            // Descuento
            if ($modifier > -1) {
                // Es un porcentaje de descuento
                return $base_price * (1 + $modifier);
            } else {
                // Es una cantidad fija de descuento
                return max(0, $base_price + $modifier);
            }
        }
        
        return $base_price;
    }

    /**
     * Obtener servicio por defecto para un producto
     */
    public function getDefaultForProduct($product_id) {
        $query = "SELECT s.* 
                  FROM " . $this->table_name . " s
                  INNER JOIN product_services ps ON s.id = ps.service_id
                  WHERE ps.product_id = :product_id 
                  AND ps.is_default = 1
                  AND s.is_active = 1
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crear nuevo servicio
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, description, service_type, base_price, is_active) 
                  VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['service_type'],
            $data['base_price'],
            $data['is_active']
        ]);
    }

    /**
     * Actualizar servicio
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = ?, description = ?, service_type = ?, 
                      base_price = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['service_type'],
            $data['base_price'],
            $data['is_active'],
            $id
        ]);
    }

    /**
     * Eliminar servicio
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
}
?>
