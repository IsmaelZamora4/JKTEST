<?php
/**
 * Clase para manejo de autenticación
 *
 * Nota: esta clase **no** incluye ni crea la conexión a la BD.
 * Debe instanciarse con new Auth($db) donde $db es un PDO válido.
 */

class Auth {
    private $conn;
    private $table_name = "admin_users";

    public function __construct($db) {
        $this->conn = $db;
        // Asegúrate de que la sesión esté iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Iniciar sesión de administrador
     */
    public function login($username, $password) {
        $query = "SELECT id, username, password, email FROM " . $this->table_name . " WHERE username = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $stored_password = $row['password'];

            // Verificar si la contraseña almacenada parece un hash MD5
            if (strlen($stored_password) === 32 && ctype_xdigit($stored_password)) {
                // Comparar con MD5 para permitir login temporalmente
                if (md5($password) === $stored_password) {
                    // Migrar contraseña a password_hash para futuros logins
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE " . $this->table_name . " SET password = ? WHERE id = ?";
                    $update_stmt = $this->conn->prepare($update_query);
                    $update_stmt->execute([$new_hash, $row['id']]);

                    // Crear sesión
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $row['id'];
                    $_SESSION['admin_username'] = $row['username'];
                    $_SESSION['admin_email'] = $row['email'];

                    return true;
                }
            } else {
                // Caso normal: hash con password_hash
                if (password_verify($password, $stored_password)) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $row['id'];
                    $_SESSION['admin_username'] = $row['username'];
                    $_SESSION['admin_email'] = $row['email'];
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Cerrar sesión
     */
    public function logout() {
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_email']);
        session_destroy();
        return true;
    }

    /**
     * Verificar si el usuario está autenticado
     */
    public function isLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }

    /**
     * Obtener información del usuario actual
     */
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['admin_id'],
                'username' => $_SESSION['admin_username'],
                'email' => $_SESSION['admin_email']
            ];
        }
        return null;
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword($user_id, $old_password, $new_password) {
        // Verificar contraseña actual
        $query = "SELECT password FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stored_password = $row['password'];

            // Verificar si el hash es MD5 para el cambio de contraseña
            if (strlen($stored_password) === 32 && ctype_xdigit($stored_password)) {
                if (md5($old_password) === $stored_password) {
                    // Hashear la nueva contraseña
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE " . $this->table_name . " SET password = ? WHERE id = ?";
                    $update_stmt = $this->conn->prepare($update_query);
                    $update_stmt->bindParam(1, $hashed_password);
                    $update_stmt->bindParam(2, $user_id);

                    return $update_stmt->execute();
                }
            } else {
                // Caso normal con password_hash
                if (password_verify($old_password, $stored_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE " . $this->table_name . " SET password = ? WHERE id = ?";
                    $update_stmt = $this->conn->prepare($update_query);
                    $update_stmt->bindParam(1, $hashed_password);
                    $update_stmt->bindParam(2, $user_id);

                    return $update_stmt->execute();
                }
            }
        }

        return false;
    }

    /**
     * Middleware para proteger rutas de administrador
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                // Petición AJAX
                if (!function_exists('json_response')) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'No autorizado']);
                    http_response_code(401);
                    exit;
                }
                json_response(['error' => 'No autorizado'], 401);
            } else {
                // Redirección normal
                header('Location: login.php');
                exit();
            }
        }
    }
}
?>
