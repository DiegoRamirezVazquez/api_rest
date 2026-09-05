<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Token.php';

class AuthMiddleware
{
    private $db;
    private $token;
    private $authenticatedUserId;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();

        $this->token = new Token($this->db);
    }

    public function authenticate()
    {
        header("Content-Type: application/json");

        // Obtener el header Authorization
	$authorization = $_SERVER['HTTP_AUTHORIZATION']
    	?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
    	?? '';

        // Si no existe el header
        if (empty($authorization)) {
            $this->unauthorized();
            return false;
        }

        // Verificar formato Bearer
        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            $this->unauthorized();
            return false;
        }

        // Obtener únicamente el token
        $accessToken = trim($matches[1]);

        if (empty($accessToken)) {
            $this->unauthorized();
            return false;
        }

        // Buscar token válido
        $this->token->token = $accessToken;

        if (!$this->token->findValidToken()) {
            $this->unauthorized();
            return false;
        }

        // Guardar el usuario autenticado en el contexto
        $this->authenticatedUserId = $this->token->user_id;

        return true;
    }

    public function getAuthenticatedUserId()
    {
        return $this->authenticatedUserId;
    }

    private function unauthorized()
    {
        http_response_code(401);

        echo json_encode([
            "error" => "unauthorized",
            "message" => "Token inválido, expirado o no proporcionado"
        ]);
    }
}
?>
