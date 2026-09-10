<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/PasswordPolicy.php';

class PasswordResource
{
    private $db;
    private $policy;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();

        $this->policy = new PasswordPolicy($this->db);
    }

    // POST /passwords/generate
    public function generate()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        // Valores por defecto
        $length = isset($data->length) ? (int)$data->length : 16;
        $includeUppercase = isset($data->includeUppercase) ? (bool)$data->includeUppercase : true;
        $includeLowercase = isset($data->includeLowercase) ? (bool)$data->includeLowercase : true;
        $includeNumbers = isset($data->includeNumbers) ? (bool)$data->includeNumbers : true;
        $includeSymbols = isset($data->includeSymbols) ? (bool)$data->includeSymbols : true;
        $excludeSimilarCharacters = isset($data->excludeSimilarCharacters)
            ? (bool)$data->excludeSimilarCharacters
            : false;
        $excludeAmbiguousSymbols = isset($data->excludeAmbiguousSymbols)
            ? (bool)$data->excludeAmbiguousSymbols
            : false;
        $count = isset($data->count) ? (int)$data->count : 1;

        // Validar longitud
        if ($length < 8 || $length > 128) {
            http_response_code(400);

            echo json_encode([
                "error" => "invalid_parameters",
                "message" => "La longitud debe estar entre 8 y 128 caracteres."
            ]);

            return;
        }

        // Validar cantidad
        if ($count < 1 || $count > 50) {
            http_response_code(400);

            echo json_encode([
                "error" => "invalid_parameters",
                "message" => "La cantidad debe estar entre 1 y 50."
            ]);

            return;
        }

        // Validar tipos de caracteres
        if (
            !$includeUppercase &&
            !$includeLowercase &&
            !$includeNumbers &&
            !$includeSymbols
        ) {
            http_response_code(400);

            echo json_encode([
                "error" => "invalid_parameters",
                "message" => "Debe habilitar al menos un tipo de carácter."
            ]);

            return;
        }

        $passwords = [];

        for ($i = 0; $i < $count; $i++) {

            $characters = "";

            if ($includeUppercase) {
                $characters .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            }

            if ($includeLowercase) {
                $characters .= "abcdefghijklmnopqrstuvwxyz";
            }

            if ($includeNumbers) {
                $characters .= "0123456789";
            }

            if ($includeSymbols) {
                $characters .= "!@#$%^&*()-_=+[]{}";
            }

            // Excluir caracteres similares
            if ($excludeSimilarCharacters) {
                $characters = str_replace(
                    ["l", "I", "1", "O", "0"],
                    "",
                    $characters
                );
            }

            // Excluir símbolos ambiguos
            if ($excludeAmbiguousSymbols) {
                $characters = str_replace(
                    ["{", "}", "[", "]", "(", ")", "'"],
                    "",
                    $characters
                );
            }

            if ($characters === "") {
                http_response_code(422);

                echo json_encode([
                    "error" => "generation_failed",
                    "message" => "La combinación de parámetros no permite generar una contraseña válida."
                ]);

                return;
            }

            $password = "";

            for ($j = 0; $j < $length; $j++) {
                $index = random_int(0, strlen($characters) - 1);
                $password .= $characters[$index];
            }

            $passwords[] = $password;
        }

        http_response_code(200);

        echo json_encode([
            "passwords" => $passwords,
            "criteria" => [
                "length" => $length,
                "includeUppercase" => $includeUppercase,
                "includeLowercase" => $includeLowercase,
                "includeNumbers" => $includeNumbers,
                "includeSymbols" => $includeSymbols,
                "excludeSimilarCharacters" => $excludeSimilarCharacters,
                "excludeAmbiguousSymbols" => $excludeAmbiguousSymbols,
                "count" => $count
            ]
        ]);
    }

    // POST /passwords/validate
    public function validate()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (
            !isset($data->password) ||
            $data->password === ""
        ) {
            http_response_code(400);

            echo json_encode([
                "error" => "invalid_request",
                "message" => "El campo password es obligatorio."
            ]);

            return;
        }

        $password = $data->password;
        $username = isset($data->username) ? $data->username : null;

        // Obtener política
        if (!$this->policy->getPolicy()) {
            http_response_code(500);

            echo json_encode([
                "error" => "policy_error",
                "message" => "No se pudo obtener la política de contraseñas."
            ]);

            return;
        }

        $rules = [];
        $suggestions = [];
        $passedRules = 0;
        $totalRules = 0;

        // Longitud mínima
        $passed = strlen($password) >= $this->policy->min_length;

        $rules[] = [
            "rule" => "minLength",
            "description" => "Debe tener al menos " . $this->policy->min_length . " caracteres",
            "passed" => $passed
        ];

        $totalRules++;

        if ($passed) {
            $passedRules++;
        } else {
            $suggestions[] = "Aumenta la longitud de la contraseña.";
        }

        // Mayúsculas
        if ($this->policy->require_uppercase) {

            $passed = preg_match('/[A-Z]/', $password);

            $rules[] = [
                "rule" => "hasUppercase",
                "description" => "Debe incluir al menos una letra mayúscula",
                "passed" => (bool)$passed
            ];

            $totalRules++;

            if ($passed) {
                $passedRules++;
            } else {
                $suggestions[] = "Agrega al menos una letra mayúscula.";
            }
        }

        // Minúsculas
        if ($this->policy->require_lowercase) {

            $passed = preg_match('/[a-z]/', $password);

            $rules[] = [
                "rule" => "hasLowercase",
                "description" => "Debe incluir al menos una letra minúscula",
                "passed" => (bool)$passed
            ];

            $totalRules++;

            if ($passed) {
                $passedRules++;
            } else {
                $suggestions[] = "Agrega al menos una letra minúscula.";
            }
        }

        // Números
        if ($this->policy->require_numbers) {

            $passed = preg_match('/[0-9]/', $password);

            $rules[] = [
                "rule" => "hasNumber",
                "description" => "Debe incluir al menos un número",
                "passed" => (bool)$passed
            ];

            $totalRules++;

            if ($passed) {
                $passedRules++;
            } else {
                $suggestions[] = "Agrega al menos un número.";
            }
        }

        // Símbolos
        if ($this->policy->require_symbols) {

            $passed = preg_match('/[^a-zA-Z0-9]/', $password);

            $rules[] = [
                "rule" => "hasSymbol",
                "description" => "Debe incluir al menos un símbolo especial",
                "passed" => (bool)$passed
            ];

            $totalRules++;

            if ($passed) {
                $passedRules++;
            } else {
                $suggestions[] = "Agrega al menos un símbolo especial.";
            }
        }


        // Contraseñas comunes
        if ($this->policy->disallow_common_passwords) {

            $commonPasswords = [
                "password",
                "password123",
                "Password123!",
                "123456",
                "12345678",
                "123456789",
                "qwerty",
                "qwerty123",
                "admin",
                "admin123",
                "welcome",
                "welcome123",
                "letmein",
                "abc123"
            ];

            $passed = !in_array(
                strtolower($password),
                array_map('strtolower', $commonPasswords)
            );

            $rules[] = [
                "rule" => "notCommonPassword",
                "description" => "No debe ser una contraseña común",
                "passed" => $passed
            ];

            $totalRules++;

            if ($passed) {
                $passedRules++;
            } else {
                $suggestions[] = "Evita utilizar contraseñas comunes o fáciles de adivinar.";
            }
        }
        // Usuario dentro de contraseña
        if (
            $this->policy->disallow_username_in_password &&
            $username !== null
        ) {

            $passed = stripos($password, $username) === false;

            $rules[] = [
                "rule" => "noUsernameInPassword",
                "description" => "La contraseña no debe contener el nombre de usuario",
                "passed" => $passed
            ];

            $totalRules++;

            if ($passed) {
                $passedRules++;
            } else {
                $suggestions[] = "Evita utilizar el nombre de usuario dentro de la contraseña.";
            }
        }

        // Secuencias simples
        if ($this->policy->disallow_sequential_characters) {

            $sequential = preg_match(
                '/(0123|1234|2345|3456|4567|5678|6789|abcd|bcde|cdef)/i',
                $password
            );

            $passed = !$sequential;

            $rules[] = [
                "rule" => "noSequentialChars",
                "description" => "No debe contener secuencias obvias",
                "passed" => $passed
            ];

            $totalRules++;

            if ($passed) {
                $passedRules++;
            } else {
                $suggestions[] = "Evita secuencias como 1234 o abcd.";
            }
        }

        // Calcular score
        $score = $totalRules > 0
            ? (int)(($passedRules / $totalRules) * 100)
            : 0;

        if ($score < 20) {
            $strength = "muy_debil";
        } elseif ($score < 40) {
            $strength = "debil";
        } elseif ($score < 70) {
            $strength = "media";
        } elseif ($score < 90) {
            $strength = "fuerte";
        } else {
            $strength = "muy_fuerte";
        }

        $isValid = $score === 100;

        http_response_code(200);

        echo json_encode([
            "password" => $password,
            "isValid" => $isValid,
            "strength" => $strength,
            "score" => $score,
            "rules" => $rules,
            "suggestions" => $suggestions
        ]);
    }

    // GET /passwords/policy
    public function policy()
    {
        header("Content-Type: application/json");

        if (!$this->policy->getPolicy()) {
            http_response_code(500);

            echo json_encode([
                "error" => "policy_error",
                "message" => "No se pudo obtener la política de contraseñas."
            ]);

            return;
        }

        http_response_code(200);

        echo json_encode([
            "minLength" => (int)$this->policy->min_length,
            "maxLength" => (int)$this->policy->max_length,
            "requireUppercase" => (bool)$this->policy->require_uppercase,
            "requireLowercase" => (bool)$this->policy->require_lowercase,
            "requireNumbers" => (bool)$this->policy->require_numbers,
            "requireSymbols" => (bool)$this->policy->require_symbols,
            "disallowCommonPasswords" => (bool)$this->policy->disallow_common_passwords,
            "disallowUsernameInPassword" => (bool)$this->policy->disallow_username_in_password,
            "disallowSequentialCharacters" => (bool)$this->policy->disallow_sequential_characters,
            "passwordHistoryLimit" => (int)$this->policy->password_history_limit,
            "expirationDays" => (int)$this->policy->expiration_days
        ]);
    }
}
?>