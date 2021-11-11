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

    public function __invoke(ReflectionMethod|ReflectionClass $method, RouteRegistrar|Router $route): RouteRegistrar
    {
        $route->resource($this->name, '\\'.$method->getName(), $this->options);

        return $route;
    }
}
