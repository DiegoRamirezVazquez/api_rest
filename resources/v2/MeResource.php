<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User_api.php';

class MeResource
{
    private $db;
    private $user;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();

        $this->user = new User_api($this->db);
    }

    public function me($userId)
    {
        header("Content-Type: application/json");

        $this->user->id = $userId;

        if (!$this->user->readOne()) {
            http_response_code(404);

            echo json_encode([
                "error" => "user_not_found",
                "message" => "Usuario no encontrado"
            ]);

            return;
        }

        http_response_code(200);

        echo json_encode([
            "id" => $this->user->id,
            "username" => $this->user->username,
            "email" => $this->user->email,
            "status" => $this->user->status,
            "created_at" => $this->user->created_at,
            "updated_at" => $this->user->updated_at
        ]);
    }
}
?>
