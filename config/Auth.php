<?php
require_once 'Database.php';

class Auth {
    private $conn;
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function authenticate($apiKey) {
        if (empty($apiKey)) {
            return false;
        }

        $query = "SELECT api_key, user_id FROM api_keys WHERE is_active = TRUE";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($apiKey, $row['api_key'])) {
                return $row['user_id'];
            }
        }
        return false;
    }

    public function createApiKey($userId) {
        $plainKey = bin2hex(random_bytes(8));
        $hashedKey = password_hash($plainKey, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO api_keys (api_key, user_id) VALUES (:api_key, :user_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":api_key", $hashedKey);
        $stmt->bindParam(":user_id", $userId);
        
        if ($stmt->execute()) {
            return [
                'id' => $this->conn->lastInsertId(),
                'plain_key' => $plainKey
            ];
        }
        return false;
    }

    public function getUserApiKeys($userId) {
        $query = "SELECT id, user_id, is_active, created_at FROM api_keys WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deactivateApiKey($keyId, $userId) {
        $query = "UPDATE api_keys SET is_active = FALSE WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $keyId);
        $stmt->bindParam(":user_id", $userId);
        
        return $stmt->execute();
    }

    public function migrateExistingKeys() {
        $stmt = $this->conn->query("SELECT id, api_key FROM api_keys WHERE LENGTH(api_key) < 60");
        
        $migrated = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $hashedKey = password_hash($row['api_key'], PASSWORD_DEFAULT);
            
            $updateStmt = $this->conn->prepare("UPDATE api_keys SET api_key = :hashed_key WHERE id = :id");
            $updateStmt->bindParam(":hashed_key", $hashedKey);
            $updateStmt->bindParam(":id", $row['id']);
            $updateStmt->execute();
            
            $migrated++;
        }
        
        return $migrated;
    }
}
?>