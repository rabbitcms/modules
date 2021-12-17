<?php

declare(strict_types=1);

namespace RabbitCMS\Modules\Attributes;

use Illuminate\Routing\{Route, Router, RouteRegistrar};
use ReflectionMethod;
use ReflectionClass;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Method extends RouterAttribute
{
    public function __construct(protected $method = 'ANY', protected string $path = '', protected array $options = [])
    {
    }

    public function __invoke(ReflectionClass $class, ?ReflectionMethod $method, Router|RouteRegistrar $route): Route
    {
        $group = function (Router $router) use ($class, &$route, $method) {
            $route = $router->match($this->method, $this->path, [$class->name, $method->name]);
        };

        if ($route instanceof RouteRegistrar) {
            $route->group(function (Router $router) use ($group) {
                $router->group($this->options, $group);
            });
        } else {
            $route->group($this->options, $group);
        }

        return $route;
    }
}
