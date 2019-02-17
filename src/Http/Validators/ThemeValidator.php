<?php

namespace RabbitCMS\Modules\Http\Validators;

use Illuminate\Http\Request;
use Illuminate\Routing\Matching\ValidatorInterface;
use Illuminate\Routing\Route;
use RabbitCMS\Modules\Facades\Modules;

/**
 * Class ThemeValidator
 * @package RabbitCMS\Modules
 */
class ThemeValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a route and request.
     *
     * @param  \Illuminate\Routing\Route $route
     * @param  \Illuminate\Http\Request $request
     * @return bool
     */
    public function matches(Route $route, Request $request)
    {
        $theme = $route->getAction('theme');

        if (empty($theme)) {
            return true;
        }

        $currentTheme = Modules::getCurrentTheme();

        return is_array($theme) ? end($theme) === $currentTheme : $theme === $currentTheme;
    }
}
