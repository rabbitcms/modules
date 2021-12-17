<?php

declare(strict_types=1);

namespace RabbitCMS\Modules\Attributes;

use Attribute;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Routing\{Route, Router, RouteRegistrar};

#[Attribute(Attribute::TARGET_METHOD)]
class Fallback extends RouterAttribute
{
    public function __invoke(ReflectionClass $class, ?ReflectionMethod $method, Router|Route|RouteRegistrar $route): Route
    {
        if ($route instanceof Router) {
            return $route->fallback([$method->class, $method->name]);
        }

        return $route->fallback();
    }
}
