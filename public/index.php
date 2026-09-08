<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../core/Router.php';

require_once __DIR__ . '/../resources/v1/UserResource.php';
require_once __DIR__ . '/../resources/v1/ProductResource.php';
require_once __DIR__ . '/../resources/v2/UserResource.php';
require_once __DIR__ . '/../resources/v2/ProductResource.php';
require_once __DIR__ . '/../resources/v2/LogoutResource.php';
require_once __DIR__ . '/../resources/v2/LoginResource.php';
require_once __DIR__ . '/../core/AuthMiddleware.php';
require_once __DIR__ . '/../resources/v2/MeResource.php';
require_once __DIR__ . '/../resources/v2/TareaResource.php';

$basePath = '';

$routerV1 = new Router('v1', $basePath);

$userResource = new UserResource();
$productoResource = new ProductoResource();

// RUTAS DE USUARIOS V1

$routerV1->addRoute('GET', '/users', [$userResource, 'index']);
$routerV1->addRoute('GET', '/users/{id}', [$userResource, 'show']);
$routerV1->addRoute('POST', '/users', [$userResource, 'store']);
$routerV1->addRoute('PUT', '/users/{id}', [$userResource, 'update']);
$routerV1->addRoute('DELETE', '/users/{id}', [$userResource, 'destroy']);


// RUTAS DE PRODUCTOS V1

$routerV1->addRoute('GET', '/productos', [$productoResource, 'index']);
$routerV1->addRoute('GET', '/productos/{id}', [$productoResource, 'show']);
$routerV1->addRoute('POST', '/productos', [$productoResource, 'store']);
$routerV1->addRoute('PUT', '/productos/{id}', [$productoResource, 'update']);
$routerV1->addRoute('DELETE', '/productos/{id}', [$productoResource, 'destroy']);


$routerV2 = new Router('v2', $basePath);

$userApiResource = new UserApiResource();
$productoApiResource = new ProductoApiResource();
$authMiddleware = new AuthMiddleware();
$logoutResource = new LogoutResource();
$loginResource = new LoginResource();
$meResource = new MeResource();
$tareaResource = new TareaResource();

$routerV2->addRoute('POST', '/login', [$loginResource, 'login']);
$routerV2->addRoute('POST', '/logout', [$logoutResource, 'logout']);
$routerV2->addRoute('GET', '/me', function () use ($authMiddleware, $meResource) {
    if (!$authMiddleware->authenticate()) {
        return;
    }
    $userId = $authMiddleware->getAuthenticatedUserId();
    return $meResource->me($userId);
});


//RUTAS DE USUARIOS V2
$routerV2->addRoute('GET', '/users', function () use ($authMiddleware, $userApiResource) {
    if (!$authMiddleware->authenticate()) {
        return;
    }
    return $userApiResource->index();
});

$routerV2->addRoute('GET', '/users/{id}', function ($id) use ($authMiddleware, $userApiResource) {
    if (!$authMiddleware->authenticate()) {
        return;
    }
    return $userApiResource->show($id);
});

$routerV2->addRoute('POST', '/users', function () use ($authMiddleware, $userApiResource) {
    if (!$authMiddleware->authenticate()) {
        return;
    }
    return $userApiResource->store();
});

$routerV2->addRoute('PUT', '/users/{id}', function ($id) use ($authMiddleware, $userApiResource) {
    if (!$authMiddleware->authenticate()) {
        return;
    }
    return $userApiResource->update($id);
});

$routerV2->addRoute('DELETE', '/users/{id}', function ($id) use ($authMiddleware, $userApiResource) {
    if (!$authMiddleware->authenticate()) {
        return;
    }
    return $userApiResource->destroy($id);
});


//RUTAS DE PRODUCTOS V2
$routerV2->addRoute('GET', '/productos', function () use ($authMiddleware, $productoApiResource) {
    if (!$authMiddleware->authenticate()) {
        return;
    }
    return $productoApiResource->index();
});

$routerV2->addRoute('GET', '/productos/{id}', function ($id) use ($authMiddleware, $productoApiResource) {
    if (!$authMiddleware->authenticate()) {
        return;
    }
    return $productoApiResource->show($id);
});

$routerV2->addRoute('POST', '/productos', function () use ($authMiddleware, $productoApiResource) {
    if (!$authMiddleware->authenticate()) {
        return;
    }
    return $productoApiResource->store();
});

$routerV2->addRoute('PUT', '/productos/{id}', function ($id) use ($authMiddleware, $productoApiResource) {
    if (!$authMiddleware->authenticate()) {
        return;
    }
    return $productoApiResource->update($id);
});

$routerV2->addRoute('DELETE', '/productos/{id}', function ($id) use ($authMiddleware, $productoApiResource) {
    if (!$authMiddleware->authenticate()) {
        return;
    }
    return $productoApiResource->destroy($id);
});


// RUTAS DE TAREAS V2

$routerV2->addRoute('GET', '/tareas', function () use ($tareaResource) {
    return $tareaResource->index();
});

$routerV2->addRoute('GET', '/tareas/{id}', function ($id) use ($tareaResource) {
    return $tareaResource->show($id);
});

$routerV2->addRoute('POST', '/tareas', function () use ($tareaResource) {
    return $tareaResource->store();
});

$routerV2->addRoute('PUT', '/tareas/{id}', function ($id) use ($tareaResource) {
    return $tareaResource->update($id);
});


$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($uri, '/api/v2/') !== false) {

    $routerV2->dispatch();

} else {

    $routerV1->dispatch();

}

?>
