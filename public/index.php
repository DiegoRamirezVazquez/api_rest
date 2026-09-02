<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../core/Router.php';
require_once '../resources/v1/UserResource.php';
require_once '../resources/v1/ProductResource.php';


$basePath = "";

$router = new Router('v1', $basePath);
$userResource = new UserResource();
$productoResource = new ProductoResource();

// rutas usuarios
$router->addRoute('GET', '/users', [$userResource, 'index']);
$router->addRoute('GET', '/users/{id}', [$userResource, 'show']);
$router->addRoute('POST', '/users', [$userResource, 'store']);
$router->addRoute('PUT', '/users/{id}', [$userResource, 'update']);
$router->addRoute('DELETE', '/users/{id}', [$userResource, 'destroy']);

// rutas productos
$router->addRoute('GET', '/productos', [$productoResource, 'index']);
$router->addRoute('GET', '/productos/{id}', [$productoResource, 'show']);
$router->addRoute('POST', '/productos', [$productoResource, 'store']);
$router->addRoute('PUT', '/productos/{id}', [$productoResource, 'update']);
$router->addRoute('DELETE', '/productos/{id}', [$productoResource, 'destroy']);

$router->dispatch();
?>
