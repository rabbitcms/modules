<?php
declare(strict_types = 1);
namespace RabbitCMS\Modules\Managers;

use Illuminate\Contracts\Foundation\Application;
use RabbitCMS\Modules\Contracts\PackageContract;
use RabbitCMS\Modules\Repository;
use RuntimeException;
use SplFileInfo;

/**
 * Class ManagerImpl
 * @package RabbitCMS\Modules\Managers
 */
trait ManagerImpl
{
    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var Application
     */
    protected $app;

    /**
     * Manager constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->restore();
    }

    /**
     * @inheritdoc
     */
    public function restore(): bool
    {
        if (empty($cacheFile = $this->cacheFile()) || !is_file($file = base_path($cacheFile))) {
            return false;
        }
        $repository = new Repository();

        $data = (array)json_decode(file_get_contents($file), true);

        foreach ($data as $module) {
            $module = $this->restoreItem($module);
            if (!is_dir($module->getPath())) {
                //disable modules if not exist
                $module->setEnabled(false);
            }
            $repository->add($module);
        }

        $this->repository = $repository;

        return true;
    }

    /**
     * Store modules for fast load.
     */
    public function store()
    {
        if (empty($cacheFile = $this->cacheFile())) {
            return;
        }
        file_put_contents(
            base_path($cacheFile),
            json_encode($this->all()->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Get all modules.
     *
     * @return Repository
     */
    public function all(): Repository
    {
        if ($this->repository === null && !$this->restore()) {
            $this->repository = new Repository();
            $this->scan();
        }

        return $this->repository;
    }

    /**
     * @inheritdoc
     */
    public function disable($name)
    {
        $module = $this->all()->get($name);
        if ($module->isSystem()) {
            throw new RuntimeException('Don\'t disable system module.');
        }
        $module->setEnabled(false);
        $this->store();
    }

    /**
     * @inheritdoc
     */
    public function enable($name)
    {
        $this->all()->get($name)->setEnabled(true);
        $this->store();
    }

    /**
     * @inheritdoc
     */
    public function enabled(): Repository
    {
        return $this->all()->filter(
            function (PackageContract $module) {
                return $module->isEnabled();
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function has($name): bool
    {
        return $this->all()->has($name);
    }

    /**
     * @inheritdoc
     */
    public function get($name): PackageContract
    {
        return $this->all()->get($name);
    }

    /**
     * Get the specified configuration value.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    abstract public function config(string $key, $default = null);

    /**
     * @param string $link
     * @param string $path
     */
    protected function updateLink(string $link, string $path)
    {
        if (is_link($path)) {
            unlink($path);
        }
        if (is_dir($link) && $link !== public_path()) {
            if (!defined('PHP_WINDOWS_VERSION_MAJOR')) {
                $link = $this->getRelativePath($path, $link);
            }
            symlink($link, $path);
        }
    }

    /**
     * @param string $from
     * @param string $path
     * @return string
     */
    private function getRelativePath(string $from, string $path): string
    {
        // some compatibility fixes for Windows paths
        $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
        $path = is_dir($path) ? rtrim($path, '\/') . '/' : $path;
        $from = str_replace('\\', '/', $from);
        $path = str_replace('\\', '/', $path);

        $from = explode('/', $from);
        $path = explode('/', $path);
        $relPath = $path;

        foreach ($from as $depth => $dir) {
            // find first non-matching dir
            if ($dir === $path[$depth]) {
                // ignore this directory
                array_shift($relPath);
            } else {
                // get number of remaining dirs to $from
                $remaining = count($from) - $depth;
                if ($remaining > 1) {
                    // add traversals up to first matching dir
                    $padLength = (count($relPath) + $remaining - 1) * -1;
                    $relPath = array_pad($relPath, $padLength, '..');
                    break;
                } else {
                    $relPath[0] = './' . $relPath[0];
                }
            }
        }

        return implode('/', $relPath);
    }

    /**
     * @inheritdoc
     */
    public function scan($store = true)
    {
        $repository = new Repository();
        foreach ((array)$this->config('paths', []) as $path) {
            foreach (glob($path, GLOB_NOSORT | GLOB_BRACE | GLOB_ONLYDIR) as $dir) {
                /* @var SplFileInfo $file */
                if (!is_dir($dir)) {
                    continue;
                }
                if (!is_file($composerFile = $dir . '/composer.json')) {
                    continue;
                }
                $composer = json_decode(file_get_contents($composerFile), true);

                $package = $this->checkPackage($dir, $composer);
                if ($package !== null) {
                    if ($this->repository && $this->repository->has($package->getName())) {
                        $package->setEnabled($this->repository->get($package->getName())->isEnabled());
                    }
                    $repository->add($package);
                }
            }
        }

        $this->repository = $repository;
        if ($store) {
            $this->store();
        }
    }

    /**
     * @param array $item
     * @return PackageContract
     */
    abstract protected function restoreItem(array $item): PackageContract;

    /**
     * @param string $dir
     * @param array $composer
     * @return PackageContract|null
     */
    abstract protected function checkPackage(string $dir, array $composer);

    /**
     * Get cache file path.
     * @return string
     */
    abstract protected function cacheFile():string;
}
