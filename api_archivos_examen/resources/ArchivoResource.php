<?php

require_once __DIR__ . '/../models/Archivo.php';

class ArchivoResource
{
    private $archivo;

    public function __construct($db)
    {
        $this->archivo = new Archivo($db);
    }

    // POST /api/v1/archivos
    public function store()
    {
        // Verificar que se haya enviado un archivo
        if (!isset($_FILES['file'])) {

            http_response_code(400);

            echo json_encode([
                "error" => "El archivo es obligatorio"
            ]);

            return;
        }

        $file = $_FILES['file'];

        // Verificar que la carga no haya tenido errores
        if ($file['error'] !== UPLOAD_ERR_OK) {

            http_response_code(400);

            echo json_encode([
                "error" => "Error al subir el archivo"
            ]);

            return;
        }


        // ==========================
        // expires_in
        // ==========================

        $expires_in = isset($_POST['expires_in'])
            ? (int) $_POST['expires_in']
            : 60;

        if ($expires_in < 5 || $expires_in > 1440) {

            http_response_code(400);

            echo json_encode([
                "error" => "expires_in debe estar entre 5 y 1440 minutos"
            ]);

            return;
        }


        // ==========================
        // max_downloads
        // ==========================

        $max_downloads = isset($_POST['max_downloads'])
            ? (int) $_POST['max_downloads']
            : 1;

        if ($max_downloads < 1 || $max_downloads > 100) {

            http_response_code(400);

            echo json_encode([
                "error" => "max_downloads debe estar entre 1 y 100"
            ]);

            return;
        }


        // ==========================
        // Tamaño máximo
        // ==========================

        $max_size = 10 * 1024 * 1024; // 10 MB

        if ($file['size'] > $max_size) {

            http_response_code(413);

            echo json_encode([
                "error" => "El archivo excede el tamaño máximo permitido"
            ]);

            return;
        }


        // ==========================
        // Nombre original
        // ==========================

        $nombre_original = basename($file['name']);

        if ($nombre_original === '') {

            http_response_code(400);

            echo json_encode([
                "error" => "Nombre de archivo inválido"
            ]);

            return;
        }

        // Validar nombre de archivo
        if (
            strpos($file['name'], '..') !== false ||
            preg_match('/[\/\\\\]/', $file['name'])
        ) {
            http_response_code(400);
            echo json_encode([
                "error" => "Nombre de archivo no permitido"
            ]);
            return;
        }

        // Validar caracteres permitidos
        if (!preg_match('/^[a-zA-Z0-9._ -]+$/', $nombre_original)) {
            http_response_code(400);

            echo json_encode([
                "error" => "El nombre del archivo contiene caracteres no permitidos"
            ]);

            return;
        }

        if (!preg_match('/^[a-zA-Z0-9._ -]+$/', $nombre_original)) {
            http_response_code(400);
            echo json_encode([
                "error" => "El nombre del archivo contiene caracteres no permitidos"
            ]);
            return;
        }




        // ==========================
        // MIME real
        // ==========================

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        $mime_type = $finfo->file($file['tmp_name']);


        // Tipos MIME permitidos
        $mime_permitidos = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'text/plain'
        ];

        if (!in_array($mime_type, $mime_permitidos)) {

            http_response_code(415);

            echo json_encode([
                "error" => "Tipo de archivo no permitido"
            ]);

            return;
        }


        // ==========================
        // Identificador seguro
        // ==========================

        $identificador = bin2hex(random_bytes(16));


        // ==========================
        // Nombre interno
        // ==========================

        $extension = pathinfo(
            $nombre_original,
            PATHINFO_EXTENSION
        );

        $nombre_interno = bin2hex(random_bytes(16));

        if ($extension !== '') {
            $nombre_interno .= '.' . strtolower($extension);
        }


        // ==========================
        // Token administrativo
        // ==========================

        $token_admin = bin2hex(random_bytes(32));

        $token_admin_hash = password_hash(
            $token_admin,
            PASSWORD_DEFAULT
        );


        // ==========================
        // Fecha de expiración
        // ==========================

        $fecha_expiracion = date(
            'Y-m-d H:i:s',
            time() + ($expires_in * 60)
        );


        // ==========================
        // Directorio de almacenamiento
        // ==========================

        $directorio = dirname(__DIR__) . '/storage/files';

        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }


        // Ruta física del archivo
        $ruta = $directorio . '/' . $nombre_interno;


        // ==========================
        // Mover archivo
        // ==========================

        if (!move_uploaded_file(
            $file['tmp_name'],
            $ruta
        )) {

            http_response_code(500);

            echo json_encode([
                "error" => "No se pudo guardar el archivo"
            ]);

            return;
        }


        // ==========================
        // Guardar metadatos
        // ==========================

        $resultado = $this->archivo->crear(
            $identificador,
            $nombre_original,
            $nombre_interno,
            $file['size'],
            $mime_type,
            $fecha_expiracion,
            $max_downloads,
            $token_admin_hash
        );


        if (!$resultado) {

            // Si la BD falla, eliminar el archivo físico
            if (file_exists($ruta)) {
                unlink($ruta);
            }

            http_response_code(500);

            echo json_encode([
                "error" => "No se pudieron guardar los metadatos"
            ]);

            return;
        }


        // ==========================
        // Respuesta
        // ==========================

        http_response_code(201);

        echo json_encode([
            "message" => "Archivo creado correctamente",
            "id" => $identificador,
            "download_url" =>
                "/api-examen/v1/archivos/" .
                $identificador .
                "/download",
            "admin_token" => $token_admin,
            "fecha_expiracion" => $fecha_expiracion,
            "max_downloads" => $max_downloads
        ]);
    }
    

    // GET /api/v1/archivos/{id}
    public function show($id)
    {
        $archivo = $this->archivo->obtenerPorIdentificador($id);

        if (!$archivo) {
            http_response_code(404);

            echo json_encode([
                "error" => "Archivo no encontrado"
            ]);

            return;
        }

        echo json_encode([
            "id" => $archivo["identificador"],
            "nombre" => $archivo["nombre_original"],
            "tamano" => $archivo["tamano"],
            "mime_type" => $archivo["mime_type"],
            "fecha_creacion" => $archivo["fecha_creacion"],
            "fecha_expiracion" => $archivo["fecha_expiracion"],
            "descargas_realizadas" => $archivo["descargas_realizadas"],
            "descargas_restantes" =>
                $archivo["max_downloads"] -
                $archivo["descargas_realizadas"]
        ]);
    }

    public function download($id)
    {
        $archivo = $this->archivo->obtenerPorIdentificador($id);

        // Archivo no encontrado
        if (!$archivo) {
            http_response_code(404);

            echo json_encode([
                "error" => "Archivo no encontrado"
            ]);

            return;
        }

        // Verificar expiración
        if (strtotime($archivo["fecha_expiracion"]) <= time()) {
            http_response_code(410);

            echo json_encode([
                "error" => "El archivo ha expirado"
            ]);

            return;
        }

        // Verificar límite de descargas
        if (
            $archivo["descargas_realizadas"] >=
            $archivo["max_downloads"]
        ) {
            http_response_code(410);

            echo json_encode([
                "error" => "Se alcanzo el maximo de descargas"
            ]);

            return;
        }

        // Ruta física del archivo
        $ruta = dirname(__DIR__) .
                '/storage/files/' .
                $archivo["nombre_interno"];

        // Verificar que exista físicamente
        if (!file_exists($ruta)) {
            http_response_code(404);

            echo json_encode([
                "error" => "Archivo no encontrado"
            ]);

            return;
        }

        // Incrementar descargas de forma segura
        $actualizado = $this->archivo
            ->incrementarDescargas($id);

        if ($actualizado === 0) {
            http_response_code(410);

            echo json_encode([
                "error" => "Se alcanzó el máximo de descargas"
            ]);

            return;
        }

        // Encabezados para la descarga
        header(
            'Content-Type: ' .
            $archivo["mime_type"]
        );

        header(
            'Content-Disposition: attachment; filename="' .
            basename($archivo["nombre_original"]) .
            '"'
        );

        header(
            'Content-Length: ' .
            filesize($ruta)
        );

        header('X-Content-Type-Options: nosniff');

        readfile($ruta);

        exit;
    }
        

    // DELETE /api/v1/archivos/{id}
    public function destroy($id)
    {
        // Obtener archivo
        $archivo = $this->archivo->obtenerPorIdentificador($id);

        if (!$archivo) {
            http_response_code(404);

            echo json_encode([
                "error" => "Archivo no encontrado"
            ]);

            return;
        }

        // Obtener token del encabezado Authorization
        $headers = getallheaders();

        $authorization = isset($headers['Authorization'])
            ? $headers['Authorization']
            : '';

        if (
            !preg_match(
                '/Bearer\s+(.+)/',
                $authorization,
                $matches
            )
        ) {
            http_response_code(401);

            echo json_encode([
                "error" => "Token de administrador requerido"
            ]);

            return;
        }

        $token = trim($matches[1]);

        // Verificar token
        if (!password_verify(
            $token,
            $archivo["token_admin_hash"]
        )) {
            http_response_code(401);

            echo json_encode([
                "error" => "Token de administrador incorrecto"
            ]);

            return;
        }

        // Ruta física del archivo
        $ruta = dirname(__DIR__) .
                '/storage/files/' .
                $archivo["nombre_interno"];

        // Eliminar archivo físico
        if (file_exists($ruta)) {
            if (!unlink($ruta)) {
                http_response_code(500);

                echo json_encode([
                    "error" => "No se pudo eliminar el archivo"
                ]);

                return;
            }
        }

        // Eliminar registro de la base de datos
        if (!$this->archivo->eliminar($id)) {
            http_response_code(500);

            echo json_encode([
                "error" => "No se pudo eliminar el registro"
            ]);

            return;
        }

        http_response_code(200);

        echo json_encode([
            "message" => "Archivo eliminado correctamente"
        ]);
    }
}