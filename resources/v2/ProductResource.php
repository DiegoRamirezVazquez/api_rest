<?php

require_once '../config/database.php';
require_once '../models/Producto.php';

class ProductoApiResource
{
    private $db;
    private $producto;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->producto = new Producto($this->db);
    }

    // GET /api/v1/productos
    public function index()
    {
        header("Content-Type: application/json");

        $stmt = $this->producto->read();
        $productos_arr = array();
        $productos_arr["records"] = array();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $producto_item = array(
                "id" => $row['id'],
                "sku" => $row['sku'],
                "name" => $row['name'],
                "description" => $row['description'],
                "price" => $row['price'],
                "stock" => $row['stock'],
                "created_at" => $row['created_at'],
                "updated_at" => $row['updated_at']
            );

            array_push($productos_arr["records"], $producto_item);
        }

        http_response_code(200);
        echo json_encode($productos_arr);
    }

    // GET /api/v1/productos/{id}
    public function show($id)
    {
        header("Content-Type: application/json");

        $this->producto->id = $id;

        if ($this->producto->readOne()) {

            $producto_arr = array(
                "id" => $this->producto->id,
                "sku" => $this->producto->sku,
                "name" => $this->producto->name,
                "description" => $this->producto->description,
                "price" => $this->producto->price,
                "stock" => $this->producto->stock,
                "created_at" => $this->producto->created_at,
                "updated_at" => $this->producto->updated_at
            );

            http_response_code(200);
            echo json_encode($producto_arr);

        } else {

            http_response_code(404);
            echo json_encode(
                array("message" => "Producto no encontrado")
            );
        }
    }

    // POST /api/v1/productos
    public function store()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (
            !empty($data->sku) &&
            !empty($data->name) &&
            isset($data->price) &&
            isset($data->stock)
        ) {

            $this->producto->sku = $data->sku;
            $this->producto->name = $data->name;
            $this->producto->description = $data->description ?? "";
            $this->producto->price = $data->price;
            $this->producto->stock = $data->stock;

            if ($this->producto->create()) {

                http_response_code(201);

                echo json_encode(array(
                    "message" => "Producto creado exitosamente",
                    "id" => $this->producto->id
                ));

            } else {

                http_response_code(503);

                echo json_encode(
                    array("message" => "No se pudo crear el producto")
                );
            }

        } else {

            http_response_code(400);

            echo json_encode(
                array("message" => "Datos incompletos")
            );
        }
    }

    // PUT /api/v1/productos/{id}
    public function update($id)
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        $this->producto->id = $id;

        if (
            !empty($data->sku) &&
            !empty($data->name) &&
            isset($data->price) &&
            isset($data->stock)
        ) {

            $this->producto->sku = $data->sku;
            $this->producto->name = $data->name;
            $this->producto->description = $data->description ?? "";
            $this->producto->price = $data->price;
            $this->producto->stock = $data->stock;

            if ($this->producto->update()) {

                http_response_code(200);

                echo json_encode(array(
                    "message" => "Producto actualizado exitosamente"
                ));

            } else {

                http_response_code(503);

                echo json_encode(array(
                    "message" => "No se pudo actualizar el producto"
                ));
            }

        } else {

            http_response_code(400);

            echo json_encode(array(
                "message" => "Datos incompletos"
            ));
        }
    }

    // DELETE /api/v1/productos/{id}
    public function destroy($id)
    {
        header("Content-Type: application/json");

        $this->producto->id = $id;

        if ($this->producto->delete()) {

            http_response_code(200);

            echo json_encode(array(
                "message" => "Producto eliminado exitosamente"
            ));

        } else {

            http_response_code(503);

            echo json_encode(array(
                "message" => "No se pudo eliminar el producto"
            ));
        }
    }
}
?>
