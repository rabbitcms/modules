<?php

declare(strict_types=1);

namespace RabbitCMS\Modules\Attributes;

use Illuminate\Routing\{Router, RouteRegistrar};
use ReflectionClass;
use ReflectionMethod;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Resource extends RouterAttribute
{
    public function __construct(public string $name, public array $options = [])
    {
    }

    public function __invoke(ReflectionClass $class, ?ReflectionMethod $method, RouteRegistrar|Router $route): RouteRegistrar
    {
        $route->group(function (Router $router) use ($class) {
            $router->resource($this->name, '\\'.$class->getName(), $this->options);
        });


        return $route;
    }
}
