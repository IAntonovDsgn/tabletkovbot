<?php

namespace App\Infrastructure\ServiceContainer;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use RuntimeException;

final class ServiceContainer
{
    private static ?Container $container = null;

    public static function set(Container $container): void
    {
        self::$container = $container;
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function get(string $id): mixed
    {
        if (self::$container === null) {
            throw new RuntimeException('Контейнер не инициализирован');
        }
        return self::$container->get($id);
    }
}
