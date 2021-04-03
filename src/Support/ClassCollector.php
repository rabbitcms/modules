<?php

namespace RabbitCMS\Modules\Support;

use Illuminate\Support\{Collection, Str};
use ReflectionClass;
use ReflectionException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class ClassCollector
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $namespace;

    public function __construct(string $path, string $namespace)
    {
        $this->path = realpath($path).DIRECTORY_SEPARATOR;
        $this->namespace = rtrim($namespace, '\\').'\\';
    }

    public function find(): Collection
    {
        return collect(
            (new Finder)
                ->files()
                ->in($this->path)
                ->name('*.php')
        )
            ->mapWithKeys(function (SplFileInfo $fileInfo) {
                try {
                    return [$fileInfo->getRealPath() => new ReflectionClass($this->classFromFile($fileInfo))];
                } catch (ReflectionException $e) {
                    return [];
                }
            })
            ->filter(function (ReflectionClass $class) {
                return $class->isInstantiable();
            });
    }

    /**
     * Extract the class name from the given file path.
     *
     * @param  \SplFileInfo  $file
     * @param  string  $basePath
     * @param  string  $namespace
     * @return string
     */
    protected function classFromFile(SplFileInfo $file)
    {
        return str_replace(
            [$this->path, DIRECTORY_SEPARATOR],
            [$this->namespace, '\\'],
            ucfirst(Str::replaceLast('.php', '', $file->getRealPath()))
        );
    }

    public static function make(string $path, string $namespace): self
    {
        return new static($path, $namespace);
    }
}
