<?php

namespace App\Infrastructure\Http;

use App\Presentation\Api\SwaggerController;
use App\Presentation\Api\WebhookController;
use DI\Container;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

final readonly class Router
{
    public function __construct(
        private Container $container
    ) {
    }

    private function routes(RouteCollector $routes): void
    {
        $routes->addRoute('POST', '/webhook', [WebhookController::class, 'handle']);
        $routes->addRoute('GET', '/swagger-json', [SwaggerController::class, 'getJson']);
        $routes->addRoute('GET', '/docs', [SwaggerController::class, 'ui']);
    }

    public function __invoke(): void
    {
        $dispatcher = simpleDispatcher($this->routes(...));
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $httpMethod = is_string($requestMethod) ? $requestMethod : 'GET';

        $requestUri = $_SERVER['REQUEST_URI'] ?? null;
        $path = is_string($requestUri) ? parse_url($requestUri, PHP_URL_PATH) : null;
        $uri = rawurldecode(is_string($path) ? $path : '/');
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
                [$class, $method] = $handler;
                $controller = $this->container->get($class);
                $controller->$method();
                break;
        }
    }
}
