<?php

declare(strict_types=1);

namespace RabbitCMS\Modules\Attributes;

use Illuminate\Routing\Router;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Any extends Method
{
    public function __construct(string $path = '', array $options = [])
    {
        parent::__construct(Router::$verbs, $path, $options);
    }
}
