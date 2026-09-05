<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User_api.php';
require_once __DIR__ . '/../../models/Token.php';

class LoginResource
{
    private $db;
    private $user;
    private $token;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();

        $this->user = new User_api($this->db);
        $this->token = new Token($this->db);
    }

    public function login()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        // Verificar que se reciban usuario y contraseña
        if (!isset($data->username) || !isset($data->password)) {

            http_response_code(401);

            echo json_encode([
                "error" => "invalid_credentials",
                "message" => "Usuario o contraseña incorrectos"
            ]);

            return;
        }

        // Buscar usuario
        $this->user->username = $data->username;

        if (!$this->user->findByUsername()) {

            http_response_code(401);

            echo json_encode([
                "error" => "invalid_credentials",
                "message" => "Usuario o contraseña incorrectos"
            ]);

            return;
        }

        // Verificar que el usuario esté activo
        if ($this->user->status !== "ACTIVE") {

            http_response_code(401);

            echo json_encode([
                "error" => "invalid_credentials",
                "message" => "Usuario o contraseña incorrectos"
            ]);

            return;
        }

        // Verificar contraseña
        if (!password_verify($data->password, $this->user->password_hash)) {

            http_response_code(401);

            echo json_encode([
                "error" => "invalid_credentials",
                "message" => "Usuario o contraseña incorrectos"
            ]);

            return;
        }

        // ==========================================
        // ACTIVIDAD 2: GENERAR Y GUARDAR TOKEN
        // ==========================================

        // Asignar el ID del usuario al objeto Token
        $this->token->user_id = $this->user->id;

        // Revocar tokens anteriores de este usuario
        $this->token->revokeUserTokens();

        // Generar token aleatorio de 32 bytes
        $this->token->token = bin2hex(random_bytes(32));

        // Expiración: 60 minutos
        $this->token->expires_at = date(
            "Y-m-d H:i:s",
            strtotime("+60 minutes")
        );

        // Guardar token
        if (!$this->token->create()) {

            http_response_code(500);

            echo json_encode([
                "error" => "token_creation_failed",
                "message" => "No se pudo generar el token"
            ]);

            return;
        }

        // ==========================================
        // RESPUESTA
        // ==========================================

        http_response_code(200);

        echo json_encode([
            "access_token" => $this->token->token,
            "token_type" => "Bearer",
            "expires_at" => $this->token->expires_at
        ]);
    }
}
?>
