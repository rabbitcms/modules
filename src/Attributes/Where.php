<?php

declare(strict_types=1);

namespace RabbitCMS\Modules\Attributes;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;
use ReflectionMethod;
use ReflectionClass;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Where extends RouterAttribute
{
    public function __construct(protected array $where)
    {
    }

    public function __invoke(ReflectionClass $class, ?ReflectionMethod $method, Router|Route|RouteRegistrar $route): Route|RouteRegistrar
    {
        return $route->where($this->where);
    }
}
