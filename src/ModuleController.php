<?php
declare(strict_types=1);
namespace RabbitCMS\Modules;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use RabbitCMS\Modules\Managers\Modules;

/**
 * Class ModuleController.
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
     * @var ConfigRepository
     */
    protected $config;

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
        $this->config = $app->make('config');
        if (method_exists($this, 'init')) {
            $this->app->call([$this, 'init']);
        }
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
            $module = $this->app->make(Modules::class)->get($this->module);
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
            $response = Route::prepareResponse($this->app->make('request'), $response);
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
        return module_asset($this->module()->getName(), $path);
    }

    /**
     * @param string $key
     * @param array  $parameters
     * @param string $domain
     * @param null   $locale
     *
     * @return array|null|string
     */
    public function trans($key, array $parameters = [], $domain = 'messages', $locale = null)
    {
        return $this->app->make('translator')
            ->trans($this->module()->getName() . '::' . $key, $parameters, $domain, $locale);
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
