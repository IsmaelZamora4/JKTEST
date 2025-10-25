<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Clase para manejo de productos
 */
class Product {
    private $conn;
    private $table_name = "products";

    public $id;
    public $name;
    public $description;
    public $base_price;
    public $category_id;
    public $image_url;
    public $has_variants;
    public $is_active;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtener todos los productos con paginación
     */
    public function getAll($page = 1, $limit = PRODUCTS_PER_PAGE, $search = '', $category_id = null, $active_only = true, $sort = 'newest') {
        $offset = ($page - 1) * $limit;
        
        $where_conditions = [];
        $params = [];
        
        if ($active_only) {
            $where_conditions[] = "p.is_active = 1";
        }
        
        if (!empty($search)) {
            $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
            $search_param = "%{$search}%";
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        if ($category_id !== null) {
            $where_conditions[] = "p.category_id = ?";
            $params[] = $category_id;
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

        // Sorting whitelist
        switch ($sort) {
            case 'price_asc':
                $order_by = 'p.base_price ASC';
                break;
            case 'price_desc':
                $order_by = 'p.base_price DESC';
                break;
            case 'name_asc':
                $order_by = 'p.name ASC';
                break;
            case 'name_desc':
                $order_by = 'p.name DESC';
                break;
            case 'newest':
            default:
                $order_by = 'p.created_at DESC';
                break;
        }

        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  {$where_clause}
                  ORDER BY {$order_by} 
                  LIMIT {$limit} OFFSET {$offset}";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Contar total de productos
     */
    public function countAll($search = '', $category_id = null, $active_only = true) {
        $where_conditions = [];
        $params = [];
        
        if ($active_only) {
            $where_conditions[] = "is_active = 1";
        }
        
        if (!empty($search)) {
            $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
            $search_param = "%{$search}%";
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        if ($category_id !== null) {
            $where_conditions[] = "category_id = ?";
            $params[] = $category_id;
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " {$where_clause}";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    /**
     * Obtener producto por ID
     */
    public function getById($id) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return null;
    }

    /**
     * Crear nuevo producto
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, description, base_price, category_id, image_url, has_variants, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->base_price = htmlspecialchars(strip_tags($this->base_price));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->has_variants = $this->has_variants ? 1 : 0;
        $this->is_active = $this->is_active ? 1 : 0;

        $stmt->bindParam(1, $this->name);
        $stmt->bindParam(2, $this->description);
        $stmt->bindParam(3, $this->base_price);
        $stmt->bindParam(4, $this->category_id);
        $stmt->bindParam(5, $this->image_url);
        $stmt->bindParam(6, $this->has_variants);
        $stmt->bindParam(7, $this->is_active);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    /**
     * Actualizar producto
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = ?, description = ?, base_price = ?, 
                      category_id = ?, image_url = ?, has_variants = ?, is_active = ? 
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->base_price = htmlspecialchars(strip_tags($this->base_price));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->has_variants = $this->has_variants ? 1 : 0;
        $this->is_active = $this->is_active ? 1 : 0;
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(1, $this->name);
        $stmt->bindParam(2, $this->description);
        $stmt->bindParam(3, $this->base_price);
        $stmt->bindParam(4, $this->category_id);
        $stmt->bindParam(5, $this->image_url);
        $stmt->bindParam(6, $this->has_variants);
        $stmt->bindParam(7, $this->is_active);
        $stmt->bindParam(8, $this->id);

        return $stmt->execute();
    }

    /**
     * Eliminar producto
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);

        return $stmt->execute();
    }

    /**
     * Obtener productos relacionados
     */
    public function getRelated($category_id, $exclude_id, $limit = 4) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE category_id = ? AND id != ? AND is_active = 1 
                  ORDER BY RAND() LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $category_id);
        $stmt->bindParam(2, $exclude_id);
        $stmt->bindParam(3, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar stock (deprecated - usar ProductVariant para productos con variantes)
     * Solo para productos tradicionales sin variantes
     */
    public function updateStock($product_id, $quantity) {
        // Este método ya no se usa para productos con variantes
        // El stock se maneja en product_variants o product_sizes
        return false;
    }

    public function getProductsWithOffers($limit = 6) {
        try {
            // Para productos con variantes, calculamos stock total desde product_variants
            // Para productos sin variantes, usamos product_sizes
            $query = "SELECT p.*, c.name as category_name,
                      CASE 
                        WHEN p.has_variants = 1 THEN 
                          COALESCE((SELECT SUM(pv.stock_quantity) FROM product_variants pv WHERE pv.product_id = p.id), 0)
                        ELSE 
                          COALESCE((SELECT SUM(ps.stock_quantity) FROM product_sizes ps WHERE ps.product_id = p.id), 0)
                      END as total_stock
                      FROM " . $this->table_name . " p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      WHERE p.is_active = 1 
                      HAVING total_stock <= 30 AND total_stock > 0
                      ORDER BY total_stock ASC, p.created_at DESC 
                      LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            error_log("Database Error in getProductsWithOffers: " . $exception->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener productos más recientes
     * @param int $limit Número máximo de productos a obtener
     * @return array Array de productos
     */
    public function getRecentProducts($limit = 8) {
        try {
            $query = "SELECT p.*, c.name as category_name 
                      FROM " . $this->table_name . " p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      WHERE p.is_active = 1 
                      ORDER BY p.created_at DESC 
                      LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            error_log("Database Error in getRecentProducts: " . $exception->getMessage());
            return [];
        }
    }

    /**
     * Obtener productos sin variantes (has_variants = 0 o NULL)
     */
    public function getProductsWithoutVariants() {
        try {
            $query = "SELECT id, name FROM " . $this->table_name . " 
                      WHERE (has_variants = 0 OR has_variants IS NULL) AND is_active = 1 
                      ORDER BY name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting products without variants: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener productos destacados (los que tienen más stock, simulando popularidad)
     * @param int $limit Número máximo de productos a obtener
     * @return array Array de productos
     */
    public function getFeaturedProducts($limit = 8) {
        try {
            $query = "SELECT p.*, c.name as category_name,
                      CASE 
                        WHEN p.has_variants = 1 THEN 
                          COALESCE((SELECT SUM(pv.stock_quantity) FROM product_variants pv WHERE pv.product_id = p.id), 0)
                        ELSE 
                          COALESCE((SELECT SUM(ps.stock_quantity) FROM product_sizes ps WHERE ps.product_id = p.id), 0)
                      END as total_stock
                      FROM " . $this->table_name . " p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      WHERE p.is_active = 1 
                      HAVING total_stock > 10
                      ORDER BY total_stock DESC, p.created_at DESC 
                      LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            error_log("Database Error in getFeaturedProducts: " . $exception->getMessage());
            return [];
        }
    }

    /**
     * Obtener todos los productos con paginación y filtro de modalidad
     */
    public function getAllWithModalidad($page = 1, $limit = PRODUCTS_PER_PAGE, $search = '', $category_id = null, $active_only = true, $sort = 'newest', $modalidad_filter = null) {
        $offset = ($page - 1) * $limit;
        
        $where_conditions = [];
        $params = [];
        
        if ($active_only) {
            $where_conditions[] = "p.is_active = 1";
        }
        
        if (!empty($search)) {
            $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
            $search_param = "%{$search}%";
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        if ($category_id !== null) {
            $where_conditions[] = "p.category_id = ?";
            $params[] = $category_id;
        }
        
        // Filtro de modalidad
        if ($modalidad_filter !== null && !empty($modalidad_filter)) {
            $placeholders = str_repeat('?,', count($modalidad_filter) - 1) . '?';
            $where_conditions[] = "p.category_id IN ($placeholders)";
            $params = array_merge($params, $modalidad_filter);
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

        // Sorting whitelist
        switch ($sort) {
            case 'price_asc':
                $order_by = 'p.base_price ASC';
                break;
            case 'price_desc':
                $order_by = 'p.base_price DESC';
                break;
            case 'name_asc':
                $order_by = 'p.name ASC';
                break;
            case 'name_desc':
                $order_by = 'p.name DESC';
                break;
            case 'newest':
            default:
                $order_by = 'p.created_at DESC';
                break;
        }

        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  {$where_clause}
                  ORDER BY {$order_by} 
                  LIMIT {$limit} OFFSET {$offset}";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Contar total de productos con filtro de modalidad
     */
    public function countAllWithModalidad($search = '', $category_id = null, $active_only = true, $modalidad_filter = null) {
        $where_conditions = [];
        $params = [];
        
        if ($active_only) {
            $where_conditions[] = "is_active = 1";
        }
        
        if (!empty($search)) {
            $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
            $search_param = "%{$search}%";
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        if ($category_id !== null) {
            $where_conditions[] = "category_id = ?";
            $params[] = $category_id;
        }
        
        // Filtro de modalidad
        if ($modalidad_filter !== null && !empty($modalidad_filter)) {
            $placeholders = str_repeat('?,', count($modalidad_filter) - 1) . '?';
            $where_conditions[] = "category_id IN ($placeholders)";
            $params = array_merge($params, $modalidad_filter);
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " {$where_clause}";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
?>

