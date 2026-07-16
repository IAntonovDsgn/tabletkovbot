<?php

namespace App\Infrastructure\Facade\ServiceContainer;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;

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
        return self::$container->get($id);
    }
}
