<?php
declare(strict_types=1);
namespace RabbitCMS\Modules;

use Illuminate\Foundation\Application;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;
use RabbitCMS\Modules\Contracts\ModulesManager;

/**
 * Class ModuleController
 *
 * @package RabbitCMS\Modules
 */
abstract class ModuleController extends BaseController
{
    /**
     * @var Application $app
     */
    protected $app;

    /**
     * @var string
     */
    protected $module = '';

    /**
     * @var integer|float
     */
    protected $cache = 0;

    /**
     * ModuleController constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        if (method_exists($this, 'init')) {
            $this->app->call([$this, 'init']);
        }
    }

    /**
     * Get the specified configuration value.
     *
     * @deprecated
     * @param  string $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    public function config(string $key, $default = null)
    {
        return $this->module()->config($key, $default);
    }

    /**
     * Get module.
     *
     * @return Module
     */
    public function module()
    {
        static $module = null;
        if ($module === null) {
            $module = $this->app->make(ModulesManager::class)->get($this->module);
        }

        return $module;
    }

    /**
     * Execute an action on the controller.
     *
     * @param  string $method
     * @param  array  $parameters
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        if (method_exists($this, 'before')) {
            $this->app->call([$this, 'before'], ['method' => $method]);
        }

        $response = parent::callAction($method, $parameters);

        if ($this->cache > 0) {
            $response = \Route::prepareResponse($this->app->make('request'), $response);
            $response->headers->addCacheControlDirective('public');
            $response->headers->addCacheControlDirective('max-age', floor($this->cache * 60));
        }

        return $response;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function asset($path)
    {
        return asset_module($path, $this->module()->getName());
    }

    /**
     * @param string $id
     * @param array  $parameters
     * @param string $domain
     * @param null   $locale
     *
     * @return array|null|string
     */
    public function trans($id, array $parameters = [], $domain = 'messages', $locale = null)
    {
        $id = $this->module()->getName() . '::' . $id;
        return $this->app->make('translator')->trans($id, $parameters, $domain, $locale);
    }

    /**
     * @param string $view
     * @param array  $data
     *
     * @return View
     */
    protected function view($view, array $data = [])
    {
        return $this->app->make('view')->make($this->viewName($view), $data, []);
    }

    /**
     * Get module view name.
     *
     * @param string $view
     *
     * @return string
     */
    protected function viewName($view)
    {
        return $this->module()->getName() . '::' . $view;
    }
}
