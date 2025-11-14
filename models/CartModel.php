<?php
require_once 'config/Database.php';

class CartModel {
    private $conn;
    private $table = "carts";

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function getCart($session_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE session_id = :session_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":session_id", $session_id);
        $stmt->execute();
        return $stmt;
    }

    public function getAllCarts() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getCartItem($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt;
    }

    public function createCartItem($session_id, $product_id, $quantity) {
        $query = "INSERT INTO " . $this->table . " 
                 SET session_id=:session_id, product_id=:product_id, quantity=:quantity";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":session_id", $session_id);
        $stmt->bindParam(":product_id", $product_id);
        $stmt->bindParam(":quantity", $quantity);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function updateCartItem($id, $session_id, $product_id, $quantity) {
        $query = "UPDATE " . $this->table . " 
                 SET session_id=:session_id, product_id=:product_id, quantity=:quantity 
                 WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":session_id", $session_id);
        $stmt->bindParam(":product_id", $product_id);
        $stmt->bindParam(":quantity", $quantity);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function deleteCartItem($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>