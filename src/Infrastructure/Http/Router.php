<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Presentation\Api\Swagger\SwaggerController;
use App\Presentation\Api\WebhookController;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use RuntimeException;

use function FastRoute\simpleDispatcher;

final readonly class Router
{
    public function __construct(
        private Container $container
    ) {}

    private function routes(RouteCollector $routes): void
    {
        $routes->addRoute('POST', '/webhook', [WebhookController::class, 'handle']);
        $routes->addRoute('GET', '/swagger-json', [SwaggerController::class, 'getJson']);
        $routes->addRoute('GET', '/docs', [SwaggerController::class, 'ui']);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
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
                if (
                    !is_array($handler)
                    || !isset($handler[0], $handler[1])
                    || !is_string($handler[0])
                    || !is_string($handler[1])
                ) {
                    throw new RuntimeException('Invalid route handler configuration.');
                }

                $controller = $this->container->get($handler[0]);
                if (!is_object($controller) || !method_exists($controller, $handler[1])) {
                    throw new RuntimeException(
                        sprintf('Handler %s::%s() is not resolvable.', $handler[0], $handler[1])
                    );
                }
                $controller->{$handler[1]}();
                break;
        }
    }
}
