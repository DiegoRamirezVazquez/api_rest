<?php

require_once '../config/database.php';
require_once '../models/User_api.php';

class UserApiResource
{
    private $db;
    private $user;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User_api($this->db);
    }

    // GET /api/v2/users
    public function index()
    {
        header("Content-Type: application/json");

        $stmt = $this->user->read();
        $num = $stmt->rowCount();

        if ($num > 0) {

            $users_arr = array();
            $users_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                $user_item = array(
                    "id" => $row["id"],
                    "username" => $row["username"],
                    "email" => $row["email"],
                    "status" => $row["status"],
                    "created_at" => $row["created_at"],
                    "updated_at" => $row["updated_at"]
                );

                array_push($users_arr["records"], $user_item);
            }

            http_response_code(200);
            echo json_encode($users_arr);

        } else {

            http_response_code(200);
            echo json_encode(array(
                "records" => array()
            ));
        }
    }

    // GET /api/v2/users/{id}
    public function show($id)
    {
        header("Content-Type: application/json");

        $this->user->id = $id;

        if ($this->user->readOne()) {

            $user_arr = array(
                "id" => $this->user->id,
                "username" => $this->user->username,
                "email" => $this->user->email,
                "status" => $this->user->status,
                "created_at" => $this->user->created_at,
                "updated_at" => $this->user->updated_at
            );

            http_response_code(200);
            echo json_encode($user_arr);

        } else {

            http_response_code(404);
            echo json_encode(array(
                "message" => "Usuario no encontrado"
            ));
        }
    }

    // POST /api/v2/users
    public function store()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (
            !empty($data->username) &&
            !empty($data->email) &&
            !empty($data->password)
        ) {

            $this->user->username = $data->username;
            $this->user->email = $data->email;

            // La contraseña se almacena mediante hash
            $this->user->password_hash = password_hash(
                $data->password,
                PASSWORD_DEFAULT
            );

            $this->user->status = !empty($data->status)
                ? $data->status
                : "ACTIVE";

            if ($this->user->create()) {

                http_response_code(201);

                echo json_encode(array(
                    "message" => "Usuario creado exitosamente",
                    "id" => $this->user->id
                ));

            } else {

                http_response_code(503);

                echo json_encode(array(
                    "message" => "No se pudo crear el usuario"
                ));
            }

        } else {

            http_response_code(400);

            echo json_encode(array(
                "message" => "Datos incompletos"
            ));
        }
    }

    // PUT /api/v2/users/{id}
    public function update($id)
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        $this->user->id = $id;

        if (
            !empty($data->username) &&
            !empty($data->email)
        ) {

            $this->user->username = $data->username;
            $this->user->email = $data->email;

            if (!empty($data->status)) {
                $this->user->status = $data->status;
            }

            // Si se proporciona una nueva contraseña,
            // se vuelve a generar el hash.
            if (!empty($data->password)) {

                $this->user->password_hash = password_hash(
                    $data->password,
                    PASSWORD_DEFAULT
                );
            }

            if ($this->user->update()) {

                http_response_code(200);

                echo json_encode(array(
                    "message" => "Usuario actualizado exitosamente"
                ));

            } else {

                http_response_code(503);

                echo json_encode(array(
                    "message" => "No se pudo actualizar el usuario"
                ));
            }

        } else {

            http_response_code(400);

            echo json_encode(array(
                "message" => "Datos incompletos"
            ));
        }
    }

    // DELETE /api/v2/users/{id}
    public function destroy($id)
    {
        header("Content-Type: application/json");

        $this->user->id = $id;

        if ($this->user->delete()) {

            http_response_code(200);

            echo json_encode(array(
                "message" => "Usuario eliminado exitosamente"
            ));

        } else {

            http_response_code(503);

            echo json_encode(array(
                "message" => "No se pudo eliminar el usuario"
            ));
        }
    }
}
?>
