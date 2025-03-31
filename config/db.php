<?php
require 'vendor/autoload.php';

class MongoDBConnection {
    private static $instance = null;
    private $client;
    private $db;
    
    private function __construct() {
        try {
            $this->client = new MongoDB\Client("mongodb://localhost:27017/");
            $this->db = $this->client->stock_chatbot;
            
            // Test the connection
            $this->db->command(['ping' => 1]);
        } catch (MongoDB\Driver\Exception\Exception $e) {
            die("MongoDB Connection Error: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new MongoDBConnection();
        }
        return self::$instance;
    }
    
    public function getDatabase() {
        return $this->db;
    }
}
?>