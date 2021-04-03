<?php

declare(strict_types=1);

namespace RabbitCMS\Modules\Attributes;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;
use ReflectionMethod;
use ReflectionClass;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Prefix extends RouterAttribute
{
    public function __construct(protected string $prefix)
    {
    }

    public function __invoke(
        ReflectionClass|ReflectionMethod $method,
        Router|Route|RouteRegistrar $route
    ): Route|RouteRegistrar {
        return $route->prefix($this->prefix);
    }
}
