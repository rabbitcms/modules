<?php
declare(strict_types=1);
namespace RabbitCMS\Modules;

use Illuminate\Foundation\Application;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\App;
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
     * Page cache in seconds.
     *
     * @var int
     */
    protected $cache = 0;

    /**
     * @var Application
     */
    protected $app;

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
            $response = Route::prepareResponse(App::make('request'), $response);
            $response->headers->addCacheControlDirective('public');
            $response->headers->addCacheControlDirective('max-age', (int)$this->cache);
        }

        return $response;
    }


}
