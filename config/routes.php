<?php

use App\Presentation\Api\WebhookController;
use App\Presentation\Api\SwaggerController;
use FastRoute\RouteCollector;

return function(RouteCollector $r) {
    $r->addRoute('POST', '/webhook', [WebhookController::class, 'indexAction']);
    $r->addRoute('GET', '/swagger-json', [SwaggerController::class, 'getJson']);
    $r->addRoute('GET', '/docs', [SwaggerController::class, 'ui']);
};
