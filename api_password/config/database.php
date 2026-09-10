<?php

class Database
{
    private $host;
    private $socket;
    private $db_name;
    private $username;
    private $password;

    public $conn;

    public function __construct()
    {
        $envFile = dirname(__DIR__) . '/.env';

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '' || strpos($line, '#') === 0) {
                    continue;
                }

                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);

                    $key = trim($key);
                    $value = trim($value);

                    if (
                        strlen($value) >= 2 &&
                        (
                            ($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
                            ($value[0] === "'" && $value[strlen($value) - 1] === "'")
                        )
                    ) {
                        $value = substr($value, 1, -1);
                    }

                    putenv("$key=$value");
                }
            }
        }

        $this->host = getenv('DB_HOST');
        $this->socket = getenv('DB_SOCKET');
        $this->db_name = getenv('DB_NAME');
        $this->username = getenv('DB_USER');
        $this->password = getenv('DB_PASSWORD');
    }

    public function getConnection()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:unix_socket=" . $this->socket . ";dbname=" . $this->db_name;

            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password
            );

            $this->conn->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            $this->conn->exec("set names utf8");

        } catch (PDOException $e) {
            echo "Error de conexión: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>

