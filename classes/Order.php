<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Clase para manejo de pedidos
 */
class Order
{
    private $conn;
    private $table_name = "orders";

    public $id;
    public $customer_id;
    public $total_amount;
    public $status;
    public $payment_status;
    public $shipping_address;
    public $notes;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Crear nuevo pedido
     */
    public function create($customer_data, $cart_items, $totals)
    {
        try {
            $this->conn->beginTransaction();

            // 1. Crear o obtener cliente
            $customer_id = $this->createOrGetCustomer($customer_data);

            // 2. Crear pedido
            $query = "INSERT INTO " . $this->table_name . " 
                      (customer_id, total_amount, status, payment_status, shipping_address, notes) 
                      VALUES (?, ?, 'pending', 'pending', ?, ?)";

            $stmt = $this->conn->prepare($query);
            $shipping_address = json_encode($customer_data);
            $notes = $customer_data['notes'] ?? '';

            $stmt->bindParam(1, $customer_id);
            $stmt->bindParam(2, $totals['total']);
            $stmt->bindParam(3, $shipping_address);
            $stmt->bindParam(4, $notes);

            if (!$stmt->execute()) {
                throw new Exception('Error al crear el pedido');
            }

            $order_id = $this->conn->lastInsertId();

            // 3. Crear items del pedido
            $this->createOrderItems($order_id, $cart_items);

            // 4. Actualizar stock de productos
            // $this->updateProductStock($cart_items);

            $this->conn->commit();
            return $order_id;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Crear o obtener cliente
     */
    private function createOrGetCustomer($customer_data)
    {
        // Verificar si el cliente ya existe por email
        $query = "SELECT id FROM customers WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $customer_data['email']);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['id'];
        }

        // Crear nuevo cliente
        $insert_query = "INSERT INTO customers 
                         (first_name, last_name, email, phone, address, city, postal_code, country) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $insert_stmt = $this->conn->prepare($insert_query);
        $insert_stmt->bindParam(1, $customer_data['first_name']);
        $insert_stmt->bindParam(2, $customer_data['last_name']);
        $insert_stmt->bindParam(3, $customer_data['email']);
        $insert_stmt->bindParam(4, $customer_data['phone']);
        $insert_stmt->bindParam(5, $customer_data['address']);
        $insert_stmt->bindParam(6, $customer_data['city']);
        $insert_stmt->bindParam(7, $customer_data['postal_code']);
        $insert_stmt->bindParam(8, $customer_data['country']);

        if (!$insert_stmt->execute()) {
            throw new Exception('Error al crear el cliente');
        }

        return $this->conn->lastInsertId();
    }

    /**
     * Crear items del pedido
     */
    private function createOrderItems($order_id, $cart_items)
    {
        $query = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price, size_id) 
                  VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);

        foreach ($cart_items as $item) {
            $total_price = $item['price'] * $item['quantity'];
            $size_id = isset($item['size_id']) && is_numeric($item['size_id']) ? $item['size_id'] : null;

            $stmt->bindParam(1, $order_id);
            $stmt->bindParam(2, $item['product_id']);
            $stmt->bindParam(3, $item['quantity']);
            $stmt->bindParam(4, $item['price']);
            $stmt->bindParam(5, $total_price);
            $stmt->bindParam(6, $size_id, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                throw new Exception('Error al crear los items del pedido');
            }
        }
    }

    /**
     * Actualizar stock de productos
     */
    private function updateProductStock($cart_items)
    {
        $query = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        foreach ($cart_items as $item) {
            $stmt->bindParam(1, $item['quantity']);
            $stmt->bindParam(2, $item['product_id']);

            if (!$stmt->execute()) {
                throw new Exception('Error al actualizar el stock del producto: ' . $item['name']);
            }
        }
    }

    /**
     * Obtener pedido por ID
     */
    public function getById($id)
    {
        $query = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone 
                  FROM " . $this->table_name . " o 
                  LEFT JOIN customers c ON o.customer_id = c.id 
                  WHERE o.id = ? LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return null;
    }

    /**
     * Obtener items de un pedido
     */
    public function getOrderItems($order_id)
    {
        $query = "SELECT oi.*, p.name, p.image_url 
                  FROM order_items oi 
                  JOIN products p ON oi.product_id = p.id 
                  WHERE oi.order_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $order_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener todos los pedidos con paginación
     */
    public function getAll($page = 1, $limit = 10, $status = null)
    {
        $offset = ($page - 1) * $limit;

        $where_clause = "";
        $params = [];

        if ($status) {
            $where_clause = "WHERE o.status = ?";
            $params[] = $status;
        }

        $query = "SELECT o.*, c.first_name, c.last_name, c.email 
                  FROM " . $this->table_name . " o 
                  LEFT JOIN customers c ON o.customer_id = c.id 
                  {$where_clause}
                  ORDER BY o.created_at DESC 
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
     * Contar total de pedidos
     */
    public function countAll($status = null)
    {
        $where_clause = "";
        $params = [];

        if ($status) {
            $where_clause = "WHERE status = ?";
            $params[] = $status;
        }

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
     * Actualizar estado del pedido
     */
    public function updateStatus($order_id, $status, $payment_status = null)
    {
        $query = "UPDATE " . $this->table_name . " SET status = ?";
        $params = [$status];

        if ($payment_status) {
            $query .= ", payment_status = ?";
            $params[] = $payment_status;
        }

        $query .= " WHERE id = ?";
        $params[] = $order_id;

        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Simular procesamiento de pago
     */
    public function processPayment($order_id, $payment_data)
    {
        // Simulación de procesamiento de pago
        // En un entorno real, aquí se integraría con un procesador de pagos

        $success = true; // Simular éxito del pago

        if ($success) {
            $this->updateStatus($order_id, 'processing', 'paid');
            return ['success' => true, 'transaction_id' => uniqid('txn_')];
        } else {
            $this->updateStatus($order_id, 'pending', 'failed');
            return ['success' => false, 'error' => 'Error en el procesamiento del pago'];
        }
    }
}
