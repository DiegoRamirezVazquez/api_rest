<?php

class User_api
{
    private $conn;
    private $table_name = "api_users";

    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Crear usuario
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                  SET username = :username,
                      email = :email,
                      password_hash = :password_hash,
                      status = :status";

        $stmt = $this->conn->prepare($query);

        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password_hash = $this->password_hash;
        $this->status = $this->status ?: "ACTIVE";

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":status", $this->status);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    // Obtener todos los usuarios
    public function read()
    {
        $query = "SELECT id, username, email, status, created_at, updated_at
                  FROM " . $this->table_name . "
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Obtener un usuario por ID
    public function readOne()
    {
        $query = "SELECT id, username, email, status, created_at, updated_at
                  FROM " . $this->table_name . "
                  WHERE id = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id", $this->id);

        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {

            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];

            return true;
        }

        return false;
    }

    // Buscar usuario por username
    public function findByUsername()
    {
        $query = "SELECT id, username, email, password_hash, status,
                         created_at, updated_at
                  FROM " . $this->table_name . "
                  WHERE username = :username
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":username", $this->username);

        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {

            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->password_hash = $row['password_hash'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];

            return true;
        }

        return false;
    }

    // Actualizar usuario
    public function update()
    {
        $query = "UPDATE " . $this->table_name . "
                  SET username = :username,
                      email = :email,
                      status = :status
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Actualizar contraseña
    public function updatePassword()
    {
        $query = "UPDATE " . $this->table_name . "
                  SET password_hash = :password_hash
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Eliminar usuario
    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }
}
?>
