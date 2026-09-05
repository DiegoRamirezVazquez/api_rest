<?php

class Token
{
    private $conn;
    private $table_name = "api_tokens";

    public $id;
    public $user_id;
    public $token;
    public $expires_at;
    public $revoked;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Crear un nuevo token
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                  SET user_id = :user_id,
                      token = :token,
                      expires_at = :expires_at";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":token", $this->token);
        $stmt->bindParam(":expires_at", $this->expires_at);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    // Revocar tokens anteriores del usuario
    public function revokeUserTokens()
    {
        $query = "UPDATE " . $this->table_name . "
                  SET revoked = TRUE
                  WHERE user_id = :user_id
                  AND revoked = FALSE";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    // Buscar un token válido
    public function findValidToken()
    {
        $query = "SELECT id, user_id, token, expires_at, revoked, created_at
                  FROM " . $this->table_name . "
                  WHERE token = :token
                  AND revoked = FALSE
                  AND expires_at > NOW()
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":token", $this->token);

        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {

            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->token = $row['token'];
            $this->expires_at = $row['expires_at'];
            $this->revoked = $row['revoked'];
            $this->created_at = $row['created_at'];

            return true;
        }

        return false;
    }

    // Revocar un token específico
    public function revoke()
    {
        $query = "UPDATE " . $this->table_name . "
                  SET revoked = TRUE
                  WHERE token = :token";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":token", $this->token);

        return $stmt->execute();
    }
}
?>
