<?php

class Router
{
    private $routes = [];

    public function addRoute($method, $path, $callback)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'callback' => $callback
        ];
    }

    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        $uri = parse_url(
            $_SERVER['REQUEST_URI'],
            PHP_URL_PATH
        );

        
        $uri = preg_replace(
            '#^/api-examen#',
            '',
            $uri
        );

        foreach ($this->routes as $route) {

            $pattern = preg_replace(
                '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
                '([^/]+)',
                $route['path']
            );

            $pattern = '#' . '^' . $pattern . '$' . '#';

            if (
                $method === $route['method'] &&
                preg_match($pattern, $uri, $matches)
            ) {
                array_shift($matches);

                call_user_func_array(
                    $route['callback'],
                    $matches
                );

                return;
            }
        }

        http_response_code(404);

        echo json_encode([
            "error" => "Ruta no encontrada"
        ]);
    }
}
?>