<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/PasswordHistory.php';
require_once __DIR__ . '/../../models/PasswordPolicy.php';

class AuthResource
{
    private $db;
    private $user;
    private $passwordHistory;
    private $policy;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();

        $this->user = new User($this->db);
        $this->passwordHistory = new PasswordHistory($this->db);
        $this->policy = new PasswordPolicy($this->db);    
    
    }

    // POST /auth/register
    public function register()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (
            !isset($data->username) ||
            !isset($data->email) ||
            !isset($data->password) ||
            $data->username === "" ||
            $data->email === "" ||
            $data->password === ""
        ) {
            http_response_code(400);

            echo json_encode([
                "error" => "invalid_request",
                "message" => "Los campos username, email y password son obligatorios."
            ]);

            return;
        }

        // Comprobar si el usuario ya existe
        $this->user->username = $data->username;

        if ($this->user->findByUsername()) {
            http_response_code(409);

            echo json_encode([
                "error" => "user_exists",
                "message" => "El usuario ya existe."
            ]);

            return;
        }

        // Validar política de contraseña
        $passwordValidation = $this->validatePasswordPolicy(
            $data->password,
            $data->username
        );

        if (!$passwordValidation["valid"]) {
            http_response_code(422);

            echo json_encode([
                "error" => "password_policy_failed",
                "message" => "La contraseña no cumple con la política de seguridad.",
                "rules" => $passwordValidation["rules"]
            ]);

            return;
        }

        // Crear hash de la contraseña
        $passwordHash = password_hash(
            $data->password,
            PASSWORD_DEFAULT
        );

        // Crear UUID
        $this->user->id = $this->generateUuid();
        $this->user->username = $data->username;
        $this->user->email = $data->email;
        $this->user->password_hash = $passwordHash;
        $this->user->status = "ACTIVE";

        try {

            if (!$this->user->create()) {
                throw new Exception("No se pudo crear el usuario.");
            }

            // Guardar contraseña en historial
            $this->passwordHistory->user_id = $this->user->id;
            $this->passwordHistory->password_hash = $passwordHash;
            $this->passwordHistory->create();

            http_response_code(201);

            echo json_encode([
                "id" => $this->user->id,
                "username" => $this->user->username,
                "email" => $this->user->email,
                "createdAt" => date("c")
            ]);

        } catch (PDOException $e) {

            http_response_code(409);

            echo json_encode([
                "error" => "user_exists",
                "message" => "El usuario o correo ya existe."
            ]);

        } catch (Exception $e) {

            http_response_code(500);

            echo json_encode([
                "error" => "server_error",
                "message" => "No se pudo crear el usuario."
            ]);
        }
    }

    // POST /auth/login
    public function login()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (
            !isset($data->username) ||
            !isset($data->password) ||
            $data->username === "" ||
            $data->password === ""
        ) {
            http_response_code(400);

            echo json_encode([
                "error" => "invalid_request",
                "message" => "Los campos username y password son obligatorios."
            ]);

            return;
        }

        $this->user->username = $data->username;

        // Buscar usuario
        if (!$this->user->findByUsername()) {
            http_response_code(401);

            echo json_encode([
                "error" => "invalid_credentials",
                "message" => "Credenciales inválidas."
            ]);

            return;
        }

        // Comprobar estado
        if ($this->user->status !== "ACTIVE") {
            http_response_code(401);

            echo json_encode([
                "error" => "inactive_user",
                "message" => "La cuenta no está activa."
            ]);

            return;
        }

        // Comprobar bloqueo
        if (
            $this->user->locked_until !== null &&
            strtotime($this->user->locked_until) > time()
        ) {
            http_response_code(423);

            echo json_encode([
                "error" => "account_locked",
                "message" => "La cuenta está bloqueada temporalmente."
            ]);

            return;
        }

        // Verificar contraseña
        if (!password_verify($data->password, $this->user->password_hash)) {

            $this->user->incrementFailedAttempts();

            $this->user->failed_login_attempts++;

            if ($this->user->failed_login_attempts >= 5) {

                $this->user->lockAccount(15);

                http_response_code(423);

                echo json_encode([
                    "error" => "account_locked",
                    "message" => "La cuenta ha sido bloqueada temporalmente por múltiples intentos fallidos."
                ]);

                return;
            }

            http_response_code(401);

            echo json_encode([
                "error" => "invalid_credentials",
                "message" => "Credenciales inválidas."
            ]);

            return;
        }

        $this->user->resetFailedAttempts();
        // Login correcto
        http_response_code(200);

        echo json_encode([
            "accessToken" => $this->generateToken(),
            "tokenType" => "Bearer",
            "expiresIn" => 3600
        ]);
    }

    private function generateUuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

   private function generateToken()
    {
        $header = [
            "alg" => "HS256",
            "typ" => "JWT"
        ];

        $payload = [
            "sub" => $this->user->id,
            "username" => $this->user->username,
            "iat" => time(),
            "exp" => time() + (int)getenv('JWT_EXPIRES_IN')
        ];

        $base64UrlHeader = rtrim(
            strtr(
                base64_encode(json_encode($header)),
                '+/',
                '-_'
            ),
            '='
        );

        $base64UrlPayload = rtrim(
            strtr(
                base64_encode(json_encode($payload)),
                '+/',
                '-_'
            ),
            '='
        );

        $secret = getenv('JWT_SECRET');

        $signature = hash_hmac(
            "sha256",
            $base64UrlHeader . "." . $base64UrlPayload,
            $secret,
            true
        );

        $base64UrlSignature = rtrim(
            strtr(
                base64_encode($signature),
                '+/',
                '-_'
            ),
            '='
        );

        return $base64UrlHeader . "." .
            $base64UrlPayload . "." .
            $base64UrlSignature;
    }

    private function validatePasswordPolicy($password, $username)
    {
        if (!$this->policy->getPolicy()) {
            return [
                "valid" => false,
                "message" => "No se pudo obtener la política de contraseñas."
            ];
        }

        $rules = [];

        // Longitud mínima
        $rules[] = [
            "rule" => "minLength",
            "passed" => strlen($password) >= $this->policy->min_length
        ];

        // Longitud máxima
        $rules[] = [
            "rule" => "maxLength",
            "passed" => strlen($password) <= $this->policy->max_length
        ];

        // Mayúscula
        if ($this->policy->require_uppercase) {
            $rules[] = [
                "rule" => "hasUppercase",
                "passed" => preg_match('/[A-Z]/', $password) === 1
            ];
        }

        // Minúscula
        if ($this->policy->require_lowercase) {
            $rules[] = [
                "rule" => "hasLowercase",
                "passed" => preg_match('/[a-z]/', $password) === 1
            ];
        }

        // Número
        if ($this->policy->require_numbers) {
            $rules[] = [
                "rule" => "hasNumber",
                "passed" => preg_match('/[0-9]/', $password) === 1
            ];
        }

        // Símbolo
        if ($this->policy->require_symbols) {
            $rules[] = [
                "rule" => "hasSymbol",
                "passed" => preg_match('/[^a-zA-Z0-9]/', $password) === 1
            ];
        }

        // No permitir username dentro de password
        if ($this->policy->disallow_username_in_password) {
            $rules[] = [
                "rule" => "noUsernameInPassword",
                "passed" => stripos($password, $username) === false
            ];
        }

        // No permitir secuencias obvias
        if ($this->policy->disallow_sequential_characters) {
            $sequential = preg_match(
                '/(012|123|234|345|456|567|678|789|abc|bcd|cde|def)/i',
                $password
            ) === 1;

            $rules[] = [
                "rule" => "noSequentialChars",
                "passed" => !$sequential
            ];
        }

        foreach ($rules as $rule) {
            if (!$rule["passed"]) {
                return [
                    "valid" => false,
                    "rules" => $rules
                ];
            }
        }

        return [
            "valid" => true,
            "rules" => $rules
        ];
    }
}
?>