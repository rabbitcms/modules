<?php

declare(strict_types=1);

namespace RabbitCMS\Modules\Attributes;

use Illuminate\Http\Request;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Put extends Method
{
    public function __construct(string $path = '', array $options = [])
    {
        parent::__construct(Request::METHOD_PUT, $path, $options);
    }
}
