<?php
require_once dirname(__DIR__) . '/config/config.php';

class Database {
    private $conn;
    private $error;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        if ($this->conn->connect_error) {
            $this->error = "Connection failed: " . $this->conn->connect_error;
            return false;
        }
        
        $this->conn->set_charset("utf8mb4");
        return true;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql) {
        $result = $this->conn->query($sql);
        if (!$result) {
            $this->error = "Query failed: " . $this->conn->error;
            return false;
        }
        return $result;
    }
    
    public function prepare($sql) {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->error = "Prepare failed: " . $this->conn->error;
            return false;
        }
        return $stmt;
    }
    
    public function getError() {
        return $this->error;
    }
    
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    public function insertId() {
        return $this->conn->insert_id;
    }
    
    public function affectedRows() {
        return $this->conn->affected_rows;
    }
    
    public function beginTransaction() {
        return $this->conn->begin_transaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollback();
    }
    
    public function close() {
        if ($this->conn && $this->conn->thread_id) {
            $this->conn->close();
            $this->conn = null;
        }
    }
}
?>
