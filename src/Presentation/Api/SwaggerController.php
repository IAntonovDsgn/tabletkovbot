<?php

namespace App\Presentation\Api;

use OpenApi\Generator;

class SwaggerController
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

        echo <<<HTML
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <title>Swagger UI - Tabletkov Bot</title>
            <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui.css" />
            <style>body { margin: 0; padding: 0; }</style>
        </head>
        <body>
            <div id="swagger-ui"></div>
            <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-bundle.js"></script>
            <script>
                window.onload = function() {
                    window.ui = SwaggerUIBundle({
                        url: "/swagger-json",
                        dom_id: '#swagger-ui',
                        deepLinking: true,
                        presets: [
                            SwaggerUIBundle.presets.apis,
                            SwaggerUIBundle.SwaggerUIStandalonePreset
                        ],
                        layout: "BaseLayout",
                    });
                };
            </script>
        </body>
        </html>
        HTML;
    }
}
