<?php

declare(strict_types=1);

namespace App\Core;

use DI\ContainerBuilder;

class Container
{
    private \DI\Container $container;

    public function __construct()
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions(__DIR__ . '/../../config/di.php');
        $this->container = $builder->build();
    }

    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    public function set(string $id, mixed $entry): void
    {
        $this->container->set($id, $entry);
    }

    public function call(callable $callable, array $parameters = []): mixed
    {
        return $this->container->call($callable, $parameters);
    }

    public function make(string $name, array $parameters = []): mixed
    {
        return $this->container->make($name, $parameters);
    }
}
