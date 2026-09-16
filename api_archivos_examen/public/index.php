<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../models/Archivo.php';
require_once __DIR__ . '/../resources/ArchivoResource.php';

$database = new Database();
$db = $database->getConnection();

$router = new Router();

$archivoModel = new Archivo($db);
$archivoModel->limpiarExpirados();

// Rate limiting por IP
$ip = $_SERVER['REMOTE_ADDR'];

if (!$archivoModel->verificarRateLimit($ip, 20)) {

    http_response_code(429);

    echo json_encode([
        "error" => "Demasiadas solicitudes. Intente nuevamente más tarde."
    ]);

    exit;
}

$archivoResource = new ArchivoResource($db);

$router->addRoute(
    'POST',
    '/v1/archivos',
    [$archivoResource, 'store']
);

$router->addRoute(
    'GET',
    '/v1/archivos/{id}',
    [$archivoResource, 'show']
);

$router->addRoute(
    'GET',
    '/v1/archivos/{id}/download',
    [$archivoResource, 'download']
);

$router->addRoute(
    'DELETE',
    '/v1/archivos/{id}',
    [$archivoResource, 'destroy']
);

// Ejecutar router
$router->dispatch();

?>