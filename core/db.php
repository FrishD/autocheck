<?php
// db.php - Database connection and helper functions
require_once __DIR__ . '/../config/config.php';

class Database {
    private $conn;
    private static $instance = null;

    // Private constructor - singleton pattern
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            // Do not die here, as it will always cause a 500 error if the db is not ready
            // Instead, we can log the error and allow the application to handle it
            error_log("Database connection failed: " . $e->getMessage());
            $this->conn = null;
        }
    }

    // Get singleton instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Check if the connection is valid
    public function isConnected() {
        return $this->conn !== null;
    }

    // Get PDO connection
    public function getConnection() {
        return $this->conn;
    }

    // Execute a query with parameters
    public function query($sql, $params = []) {
        if (!$this->isConnected()) return false;
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage());
            return false;
        }
    }

    // Get a single row
    public function getRow($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : false;
    }

    // Get multiple rows
    public function getRows($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : false;
    }

    // Insert a record and return the ID
    public function insert($table, $data) {
        if (!$this->isConnected()) return false;
        $keys = array_keys($data);
        $fields = implode(',', $keys);
        $placeholders = implode(',', array_map(function($key) { return ":$key"; }, $keys));

        $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";

        if ($this->query($sql, $data)) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Update records
    public function update($table, $data, $where, $whereParams = []) {
        if (!$this->isConnected()) return false;
        $setParts = [];
        $params = [];

        foreach ($data as $key => $value) {
            $setParts[] = "$key = :set_$key";
            $params["set_$key"] = $value;
        }

        $setClause = implode(', ', $setParts);

        $sql = "UPDATE $table SET $setClause WHERE $where";

        // Merge all parameters
        $params = array_merge($params, $whereParams);

        return $this->query($sql, $params) !== false;
    }

    // Delete records
    public function delete($table, $where, $params = []) {
        if (!$this->isConnected()) return false;
        $sql = "DELETE FROM $table WHERE $where";
        return $this->query($sql, $params) !== false;
    }

    // Count records
    public function count($table, $where = '1', $params = []) {
        if (!$this->isConnected()) return 0;
        $sql = "SELECT COUNT(*) as count FROM $table WHERE $where";
        $result = $this->getRow($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }

    // Check if a table exists
    public function tableExists($tableName) {
        if (!$this->isConnected()) return false;
        try {
            $result = $this->query("SELECT 1 FROM $tableName LIMIT 1");
        } catch (PDOException $e) {
            return false;
        }
        return $result !== false;
    }

    // Escape string for security
    public function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}