<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Tarea.php';

class TareaResource
{
    private $db;
    private $tarea;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();

        $this->tarea = new Tarea($this->db);
    }

    // GET /tareas
    public function index()
    {
        header("Content-Type: application/json");

        $stmt = $this->tarea->read();

        $tareas = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tarea = [
                "id" => (int) $row["id"],
                "titulo" => $row["titulo"],
                "completada" => (bool) $row["completada"],
                "fecha_creacion" => $row["fecha_creacion"]
            ];

            $tareas[] = $tarea;
        }

        http_response_code(200);
        echo json_encode($tareas);
    }

    // GET /tareas/{id}
    public function show($id)
    {
        header("Content-Type: application/json");

        $this->tarea->id = $id;

        if (!$this->tarea->readOne()) {
            http_response_code(404);

            echo json_encode([
                "message" => "Tarea no encontrada"
            ]);

            return;
        }

        http_response_code(200);

        echo json_encode([
            "id" => (int) $this->tarea->id,
            "titulo" => $this->tarea->titulo,
            "completada" => (bool) $this->tarea->completada,
            "fecha_creacion" => $this->tarea->fecha_creacion
        ]);
    }

    // POST /tareas
    public function store()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (
            !isset($data->titulo) ||
            !isset($data->completada)
        ) {
            http_response_code(400);

            echo json_encode([
                "message" => "Los campos titulo y completada son obligatorios"
            ]);

            return;
        }

        $this->tarea->titulo = $data->titulo;
        $this->tarea->completada = $data->completada;

        if ($this->tarea->create()) {

            $this->tarea->readOne();

            http_response_code(201);

            echo json_encode([
                "id" => (int) $this->tarea->id,
                "titulo" => $this->tarea->titulo,
                "completada" => (bool) $this->tarea->completada,
                "fecha_creacion" => $this->tarea->fecha_creacion
            ]);

            return;
        }

        http_response_code(500);

        echo json_encode([
            "message" => "No se pudo crear la tarea"
        ]);
    }

    // PUT /tareas/{id}
    public function update($id)
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (
            !isset($data->titulo) ||
            !isset($data->completada)
        ) {
            http_response_code(400);

            echo json_encode([
                "message" => "Los campos titulo y completada son obligatorios"
            ]);

            return;
        }

        $this->tarea->id = $id;

        if (!$this->tarea->readOne()) {
            http_response_code(404);

            echo json_encode([
                "message" => "Tarea no encontrada"
            ]);

            return;
        }

        $this->tarea->titulo = $data->titulo;
        $this->tarea->completada = $data->completada;

        if ($this->tarea->update()) {

            $this->tarea->readOne();

            http_response_code(200);

            echo json_encode([
                "id" => (int) $this->tarea->id,
                "titulo" => $this->tarea->titulo,
                "completada" => (bool) $this->tarea->completada,
                "fecha_creacion" => $this->tarea->fecha_creacion
            ]);

            return;
        }

        http_response_code(500);

        echo json_encode([
            "message" => "No se pudo actualizar la tarea"
        ]);
    }
}
?>