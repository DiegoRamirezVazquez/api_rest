<?php
date_default_timezone_set('America/Mexico_City');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Router.php';

require_once __DIR__ . '/../models/PasswordPolicy.php';
require_once __DIR__ . '/../models/PasswordHistory.php';
require_once __DIR__ . '/../models/User.php';

require_once __DIR__ . '/../resource/v1/PasswordResource.php';
require_once __DIR__ . '/../resource/v1/AuthResource.php';


$router = new Router('v1');

$passwordResource = new PasswordResource();
$authResource = new AuthResource();


// ============================================
// RUTAS DE PASSWORDS
// ============================================

$router->addRoute('POST', '/passwords/generate', function () use ($passwordResource) {
    return $passwordResource->generate();
});

$router->addRoute('POST', '/passwords/validate', function () use ($passwordResource) {
    return $passwordResource->validate();
});

$router->addRoute('GET', '/passwords/policy', function () use ($passwordResource) {
    return $passwordResource->policy();
});


// ============================================
// RUTAS DE AUTENTICACIÓN
// ============================================

$router->addRoute('POST', '/auth/register', function () use ($authResource) {
    return $authResource->register();
});

$router->addRoute('POST', '/auth/login', function () use ($authResource) {
    return $authResource->login();
});


// ============================================
// EJECUTAR ROUTER
// ============================================

$router->dispatch();

?>