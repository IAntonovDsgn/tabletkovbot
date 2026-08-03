<?php

namespace App\Presentation\Api;

use OpenApi\Generator;

final readonly class SwaggerController
{
    public function getJson(): void
    {
        $pathsToScan = [
            __DIR__,
            dirname(__DIR__, 2) . '/Application/BotManager'
        ];

        $generator = new Generator();
        $openapi = $generator->generate($pathsToScan);

        header('Content-Type: application/json; charset=utf-8');
        echo $openapi->toJson();
    }

    public function ui(): void
    {
        header('Content-Type: text/html; charset=utf-8');
        require __DIR__ . '/Templates/swagger-ui.html';
    }
}
