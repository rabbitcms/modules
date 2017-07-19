<?php
declare(strict_types=1);

namespace RabbitCMS\Modules\Support;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;
use RabbitCMS\Modules\Support\Facade\Modules;

/**
 * Trait ModuleController
 *
 * @package RabbitCMS\Modules\Support
 */
trait ModuleConcerns
{
    use ModuleDetect;

    /**
     * @param string $path
     *
     * @return string
     */
    public function asset($path)
    {
        return Modules::asset($this->module()->getName(), $path);
    }

    /**
     * @param string $key
     * @param array  $parameters
     * @param null   $locale
     *
     * @return array|null|string
     */
    public function trans($key, array $parameters = [], $locale = null)
    {
        return trans($this->module()->getName() . '::' . $key, $parameters, $locale);
    }

    /**
     * @param string $view
     * @param array  $data
     *
     * @return View
     */
    protected function view($view, array $data = []):View
    {
        return ViewFacade::make($this->viewName($view), $data, []);
    }

    /**
     * Get module view name.
     *
     * @param string $view
     *
     * @return string
     */
    protected function viewName($view):string
    {
        return $this->module()->getName() . '::' . $view;
    }
}
