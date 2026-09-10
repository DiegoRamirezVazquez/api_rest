<?php

class PasswordPolicy
{
    private $conn;
    private $table_name = "password_policy";

    public $id;
    public $min_length;
    public $max_length;
    public $require_uppercase;
    public $require_lowercase;
    public $require_numbers;
    public $require_symbols;
    public $disallow_common_passwords;
    public $disallow_username_in_password;
    public $disallow_sequential_characters;
    public $password_history_limit;
    public $expiration_days;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getPolicy()
    {
        $query = "SELECT *
                  FROM " . $this->table_name . "
                  ORDER BY id ASC
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row["id"];
            $this->min_length = $row["min_length"];
            $this->max_length = $row["max_length"];
            $this->require_uppercase = $row["require_uppercase"];
            $this->require_lowercase = $row["require_lowercase"];
            $this->require_numbers = $row["require_numbers"];
            $this->require_symbols = $row["require_symbols"];
            $this->disallow_common_passwords = $row["disallow_common_passwords"];
            $this->disallow_username_in_password = $row["disallow_username_in_password"];
            $this->disallow_sequential_characters = $row["disallow_sequential_characters"];
            $this->password_history_limit = $row["password_history_limit"];
            $this->expiration_days = $row["expiration_days"];

            return true;
        }

        return false;
    }
}
?>