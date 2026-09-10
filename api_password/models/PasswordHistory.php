<?php

class PasswordHistory
{
    private $conn;
    private $table_name = "password_history";

    public $id;
    public $user_id;
    public $password_hash;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                  (
                      user_id,
                      password_hash
                  )
                  VALUES
                  (
                      :user_id,
                      :password_hash
                  )";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":password_hash", $this->password_hash);

        return $stmt->execute();
    }

    public function getLastPasswords($limit = 5)
    {
        $query = "SELECT password_hash
                  FROM " . $this->table_name . "
                  WHERE user_id = :user_id
                  ORDER BY created_at DESC
                  LIMIT " . (int)$limit;

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $this->user_id);

        $stmt->execute();

        return $stmt;
    }
}
?>