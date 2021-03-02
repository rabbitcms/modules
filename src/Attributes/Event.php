<?php

declare(strict_types=1);

namespace RabbitCMS\Modules\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Event
{
    public function __construct(public string $name)
    {
    }
}
