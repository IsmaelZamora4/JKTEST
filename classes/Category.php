<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Clase para manejo de categorías
 */
class Category {
    private $conn;
    private $table_name = "categories";

    public $id;
    public $name;
    public $description;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtener todas las categorías
     */
    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener categoría por ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return null;
    }

    /**
     * Crear nueva categoría
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (name, description) VALUES (?, ?)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));

        $stmt->bindParam(1, $this->name);
        $stmt->bindParam(2, $this->description);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    /**
     * Actualizar categoría
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET name = ?, description = ? WHERE id = ?";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(1, $this->name);
        $stmt->bindParam(2, $this->description);
        $stmt->bindParam(3, $this->id);

        return $stmt->execute();
    }

    /**
     * Eliminar categoría
     */
    public function delete() {
        // Verificar si hay productos asociados
        $check_query = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(1, $this->id);
        $check_stmt->execute();
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return false; // No se puede eliminar si hay productos asociados
        }
        
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);

        return $stmt->execute();
    }

    public function getAllWithProductCount() {
        try {
            $query = "SELECT c.*, COUNT(p.id) as product_count 
                      FROM " . $this->table_name . " c 
                      LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                      WHERE c.is_active = 1 
                      GROUP BY c.id 
                      ORDER BY c.name ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            error_log("Database Error in getAllWithProductCount: " . $exception->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener categorías activas simple
     * @return array Array de categorías activas
     */
    public function getActiveCategories() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE is_active = 1 ORDER BY name ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            error_log("Database Error in getActiveCategories: " . $exception->getMessage());
            return [];
        }
    }
}
?>

