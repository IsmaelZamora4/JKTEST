<?php
class Database
{
  private ?PDO $conn = null;

  // Local (XAMPP)
  private string $host_local = 'localhost';
  private string $db_local   = 'tcnsite_jkj';
  private string $user_local = 'root';
  private string $pass_local = '';

  // Producción (Hestia)
  private string $host_prod = 'localhost';
  private string $db_prod   = 'jkgr_bd';
  private string $user_prod = 'jkgr_usu';
  private string $pass_prod = 'Tt|OIH(b0zC3@l5o'; // <-- tu clave real
  private string $charset   = 'utf8mb4';
  private int    $port      = 3306;

  private function isProduction(): bool
  {
    return (isset($_SERVER['HTTP_HOST']) && stripos($_SERVER['HTTP_HOST'], 'jkgrupotextil.com') !== false)
      || (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production');
  }

  public function getConnection(): PDO
  {
    if ($this->conn) return $this->conn;

    if ($this->isProduction()) {
      $host = $this->host_prod;
      $db = $this->db_prod;
      $user = $this->user_prod;
      $pass = $this->pass_prod;
    } else {
      $host = $this->host_local;
      $db = $this->db_local;
      $user = $this->user_local;
      $pass = $this->pass_local;
    }

    $dsn = "mysql:host={$host};port={$this->port};dbname={$db};charset={$this->charset}";
    $opts = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
      $this->conn = new PDO($dsn, $user, $pass, $opts);
    } catch (PDOException $e) {
      // En prod no “muere” la app ni muestra credenciales
      error_log('[DB] ' . $e->getMessage());
      throw new RuntimeException('No se pudo conectar a la base de datos.');
    }
    return $this->conn;
  }
}
