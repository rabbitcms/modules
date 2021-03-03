<?php

declare(strict_types=1);

namespace RabbitCMS\Modules\Attributes;

use Illuminate\Support\Reflector;
use ReflectionMethod;
use ReflectionClass;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Event
{
    public function __construct(protected ?string $name = null)
    {
    }

    public function getEvent(ReflectionClass $listener, ReflectionMethod $method): ?string
    {
        if ($this->name) {
            return $this->name;
        }

        if(! isset($method->getParameters()[0])) {
            return null;
        }

        return Reflector::getParameterClassName($method->getParameters()[0]);
    }
}
