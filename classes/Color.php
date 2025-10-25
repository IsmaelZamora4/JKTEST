<?php

class Color {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAll() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM colors ORDER BY name ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting colors: " . $e->getMessage());
            return [];
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM colors WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting color by ID: " . $e->getMessage());
            return false;
        }
    }
    
    public function create($name, $hex_code = null) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO colors (name, hex_code) VALUES (?, ?)");
            $stmt->execute([$name, $hex_code]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating color: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $name, $hex_code = null) {
        try {
            $stmt = $this->pdo->prepare("UPDATE colors SET name = ?, hex_code = ? WHERE id = ?");
            return $stmt->execute([$name, $hex_code, $id]);
        } catch (PDOException $e) {
            error_log("Error updating color: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        try {
            // Check if color is being used in product variants
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM product_variants WHERE color_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                return ['error' => 'No se puede eliminar el color porque está siendo usado en variantes de productos'];
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM colors WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting color: " . $e->getMessage());
            return false;
        }
    }
    
    public function exists($name, $exclude_id = null) {
        try {
            $sql = "SELECT COUNT(*) FROM colors WHERE name = ?";
            $params = [$name];
            
            if ($exclude_id) {
                $sql .= " AND id != ?";
                $params[] = $exclude_id;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking if color exists: " . $e->getMessage());
            return false;
        }
    }
}
