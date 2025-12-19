<?php
class Database {
    private static $instance = null;
    private $pdo;

    private $host = '127.0.0.1';
    private $db   = 'projektmanagement';
    private $user = 'root'; // Standard XAMPP User
    private $pass = '';     // Standard XAMPP Password
    private $charset = 'utf8mb4';

    private function __construct() {
        // Create DB if not exists (for initial setup)
        try {
            $tempPdo = new PDO("mysql:host=$this->host;charset=$this->charset", $this->user, $this->pass);
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `$this->db`");
            $tempPdo = null;
        } catch (PDOException $e) {
            die("DB Connection failed (Initial): " . $e->getMessage());
        }

        $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }
}
