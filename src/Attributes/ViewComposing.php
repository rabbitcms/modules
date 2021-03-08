<?php

declare(strict_types=1);

namespace RabbitCMS\Modules\Attributes;

use Illuminate\View\ViewName;
use RabbitCMS\Modules\Module;
use ReflectionClass;
use ReflectionMethod;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class ViewComposing extends Event
{
    public function __construct(string $name, private bool $module = true)
    {
        parent::__construct($name);
    }

    public function getEvent(ReflectionClass $listener, ReflectionMethod $method, Module $module): ?string
    {
        $name = ViewName::normalize($this->name);

        if ($this->module) {
            $name = $module->viewName($name);
        }

        return "composing: {$name}";
    }
}
