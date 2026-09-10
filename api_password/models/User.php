<?php

class User
{
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $status;
    public $failed_login_attempts;
    public $locked_until;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function findByUsername()
    {
        $query = "SELECT *
                  FROM " . $this->table_name . "
                  WHERE username = :username
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":username", $this->username);

        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row["id"];
            $this->username = $row["username"];
            $this->email = $row["email"];
            $this->password_hash = $row["password_hash"];
            $this->status = $row["status"];
            $this->failed_login_attempts = $row["failed_login_attempts"];
            $this->locked_until = $row["locked_until"];
            $this->created_at = $row["created_at"];
            $this->updated_at = $row["updated_at"];

            return true;
        }

        return false;
    }

    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                  (
                      id,
                      username,
                      email,
                      password_hash,
                      status
                  )
                  VALUES
                  (
                      :id,
                      :username,
                      :email,
                      :password_hash,
                      :status
                  )";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":status", $this->status);

        return $stmt->execute();
    }

    public function incrementFailedAttempts()
    {
        $query = "UPDATE " . $this->table_name . "
                SET failed_login_attempts = failed_login_attempts + 1
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    public function resetFailedAttempts()
    {
        $query = "UPDATE " . $this->table_name . "
                SET failed_login_attempts = 0,
                    locked_until = NULL
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    public function lockAccount($minutes = 15)
    {
        $query = "UPDATE " . $this->table_name . "
                SET locked_until = DATE_ADD(NOW(), INTERVAL :minutes MINUTE)
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":minutes", $minutes, PDO::PARAM_INT);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }
}
?>