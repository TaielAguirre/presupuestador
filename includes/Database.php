<?php
namespace App;

class Database {
    private static $instance = null;
    private $connection = null;
    
    private function __construct() {
        $config = require __DIR__ . '/../config/database.php';
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $this->connection = new \PDO($dsn, $config['username'], $config['password'], $options);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function fetchAll($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $query = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );
        
        $stmt = $this->connection->prepare($query);
        $stmt->execute(array_values($data));
        
        return $this->connection->lastInsertId();
    }
    
    public function update($table, $data, $where, $params = []) {
        $fields = array_map(function($field) {
            return "$field = ?";
        }, array_keys($data));
        
        $query = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $fields),
            $where
        );
        
        $stmt = $this->connection->prepare($query);
        $stmt->execute(array_merge(array_values($data), $params));
        
        return $stmt->rowCount();
    }
    
    public function delete($table, $where, $params = []) {
        $query = sprintf("DELETE FROM %s WHERE %s", $table, $where);
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
} 