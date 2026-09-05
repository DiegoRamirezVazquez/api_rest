<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Token.php';

class LogoutResource
{
    private $db;
    private $token;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();

        $this->token = new Token($this->db);
    }

    public function logout()
    {
        header("Content-Type: application/json");

        // Obtener el header Authorization
        $authorization = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        // Verificar que exista
        if (empty($authorization)) {
            http_response_code(401);

            echo json_encode([
                "error" => "unauthorized",
                "message" => "Token no proporcionado"
            ]);

            return;
        }

        // Verificar formato Bearer
        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            http_response_code(401);

            echo json_encode([
                "error" => "unauthorized",
                "message" => "Token inválido"
            ]);

            return;
        }

        // Obtener el token
        $accessToken = trim($matches[1]);

        $this->token->token = $accessToken;

        // Buscar el token
        if (!$this->token->findValidToken()) {
            http_response_code(401);

            echo json_encode([
                "error" => "unauthorized",
                "message" => "Token inválido o expirado"
            ]);

            return;
        }

        // Revocar el token
        if (!$this->token->revoke()) {
            http_response_code(500);

            echo json_encode([
                "error" => "logout_failed",
                "message" => "No se pudo cerrar la sesión"
            ]);

            return;
        }

        // Respuesta exitosa
        http_response_code(200);

        echo json_encode([
            "message" => "Sesión cerrada correctamente"
        ]);
    }
}
?>
