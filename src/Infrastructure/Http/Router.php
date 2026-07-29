<?php

namespace App\Infrastructure\Http;

use DI\Container;
use FastRoute\Dispatcher;

use function FastRoute\simpleDispatcher;

final readonly class Router
{
    public function __construct(
        private Container $container
    ) {
    }

    public function run(): void
    {
        $dispatcher = simpleDispatcher(require __DIR__ . '/../../../config/routes.php');
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                http_response_code(404);
                echo json_encode(['error' => 'Not Found']);
                break;

            case Dispatcher::METHOD_NOT_ALLOWED:
                http_response_code(405);
                echo json_encode(['error' => 'Method Not Allowed']);
                break;

            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];

                [$class, $method] = $handler;

                $controller = $this->container->get($class);
                $controller->$method($vars);
                break;
        }
    }
}
