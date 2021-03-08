<?php

declare(strict_types=1);

namespace RabbitCMS\Modules\Attributes;

use Illuminate\Support\Reflector;
use RabbitCMS\Modules\Module;
use ReflectionClass;
use ReflectionMethod;

#[\Attribute(\Attribute::TARGET_METHOD)]
class EloquentEvent extends Event
{
    public const BOOTING = 'booting';
    public const BOOTED = 'booted';
    public const CREATING = 'creating';
    public const CREATED = 'created';
    public const UPDATING = 'updating';
    public const UPDATED = 'updated';
    public const DELETING = 'deleting';
    public const DELETED = 'deleted';
    public const SAVING = 'saving';
    public const SAVED = 'saved';
    public const RETRIEVED = 'retrieved';
    public const REPLICATING = 'replicating';
    public const RESTORING = 'restoring';
    public const RESTORED = 'restored';
    public const FORCE_DELETED = 'forceDeleted';

    public function __construct(string $name, private ?string $model = null)
    {
        parent::__construct($name);
    }

    public function getEvent(ReflectionClass $listener, ReflectionMethod $method, Module $module): ?string
    {
        if ($this->model === null) {
            $this->model = Reflector::getParameterClassName($method->getParameters()[0]);
        }

        return "eloquent.{$this->name}: {$this->model}";
    }
}
