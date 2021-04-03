<?php

declare(strict_types=1);

namespace RabbitCMS\Modules\Attributes;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;
use ReflectionMethod;
use ReflectionClass;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Name extends RouterAttribute
{
    public function __construct(protected string $name)
    {
    }

    public function __invoke(
        ReflectionClass|ReflectionMethod $method,
        Router|Route|RouteRegistrar $route
    ): Route|RouteRegistrar {
        return $route->name($this->name);
    }
}
