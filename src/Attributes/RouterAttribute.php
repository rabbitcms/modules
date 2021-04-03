<?php

declare(strict_types=1);

namespace RabbitCMS\Modules\Attributes;

use Illuminate\Routing\{Route, Router, RouteRegistrar};
use ReflectionMethod;
use ReflectionClass;

abstract class RouterAttribute
{
    abstract public function __invoke(ReflectionMethod|ReflectionClass $method, Router|RouteRegistrar $route): Route|RouteRegistrar;
}
