<?php
/**
 * classes/StoreRating.php
 * Clase para manejo de calificaciones de la tienda
 */

class StoreRating {
    private $conn;
    private $table_name = "store_ratings";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Agregar nueva calificación
     */
    public function addRating($customer_name, $customer_email, $rating, $comment = '', $order_id = null) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (customer_name, customer_email, rating, comment, order_id, created_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$customer_name, $customer_email, $rating, $comment, $order_id]);
    }

    /**
     * Obtener promedio de calificaciones
     */
    public function getAverageRating() {
        $query = "SELECT AVG(rating) as average_rating, COUNT(*) as total_reviews 
                  FROM " . $this->table_name . " 
                  WHERE is_approved = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'average_rating' => $result['average_rating'] ? round((float)$result['average_rating'], 1) : 4.7,
            'total_reviews' => (int)$result['total_reviews'] ?: 6
        ];
    }

    /**
     * Obtener calificaciones aprobadas para mostrar
     */
    public function getApprovedRatings($limit = 10) {
        $query = "SELECT customer_name, rating, comment, created_at 
                  FROM " . $this->table_name . " 
                  WHERE is_approved = 1 AND comment != '' 
                  ORDER BY created_at DESC 
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener distribución de calificaciones
     */
    public function getRatingDistribution() {
        $query = "SELECT rating, COUNT(*) as count 
                  FROM " . $this->table_name . " 
                  WHERE is_approved = 1 
                  GROUP BY rating 
                  ORDER BY rating DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $distribution[$i] = 0;
        }
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $distribution[$row['rating']] = (int)$row['count'];
        }
        
        return $distribution;
    }

    /**
     * Verificar si un email ya calificó
     */
    public function hasRated($email) {
        $query = "SELECT COUNT(*) as count 
                  FROM " . $this->table_name . " 
                  WHERE customer_email = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'] > 0;
    }

    /**
     * Obtener calificaciones pendientes de aprobación (para admin)
     */
    public function getPendingRatings() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE is_approved = 0 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Aprobar calificación
     */
    public function approveRating($rating_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_approved = 1 
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$rating_id]);
    }

    /**
     * Rechazar/eliminar calificación
     */
    public function deleteRating($rating_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$rating_id]);
    }
}
?>
