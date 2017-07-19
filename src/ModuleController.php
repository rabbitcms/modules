<?php
declare(strict_types=1);
namespace RabbitCMS\Modules;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Route;
use RabbitCMS\Modules\Support\ModuleConcerns;

/**
 * Class ModuleController.
 * @package RabbitCMS\Modules
 * @deprecated
 */
abstract class ModuleController extends BaseController
{
    use ModuleConcerns;
    /**
     * @var Application $app
     */
    protected $app;

    /**
     * @var ConfigRepository
     */
    protected $config;

    /**
     * Page cache in seconds.
     *
     * @var int
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
            $response->headers->addCacheControlDirective('max-age', (int)$this->cache);
        }

        return $response;
    }


}
