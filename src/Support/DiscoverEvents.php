<?php

namespace RabbitCMS\Modules\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Reflector;
use Illuminate\Support\Str;
use RabbitCMS\Modules\Attributes\Event;
use RabbitCMS\Modules\Module;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class DiscoverEvents
{
    /**
     * Get all of the events and listeners by searching the given listener directory.
     */
    public static function within(Module $module): array
    {
        return collect(
            static::getListenerEvents(
                (new Finder)->files()->in($listenerPath = $module->getPath('src/Listeners')),
                realpath($listenerPath),
                $module
            ))
            ->reduce(function (array $list, string|array $events, string $listener) {
                return array_reduce((array) $events, function (array $list, $event) use ($listener) {
                    $list[$event] = array_merge($list[$event] ?? [], [$listener]);

                    return $list;
                }, $list);
            }, []);
    }

    /**
     * Get all of the listeners and their corresponding events.
     */
    protected static function getListenerEvents(iterable $listeners, string $basePath, Module $module): array
    {
        $listenerEvents = [];

        foreach ($listeners as $listener) {
            try {
                $listener = new ReflectionClass(
                    static::classFromFile($listener, $basePath, $module->getNamespace('Listeners'))
                );
            } catch (ReflectionException $e) {
                continue;
            }

            if (! $listener->isInstantiable()) {
                continue;
            }

            foreach ($listener->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (class_exists(ReflectionAttribute::class, false)) {
                    $attributes = $method->getAttributes(Event::class, ReflectionAttribute::IS_INSTANCEOF);
                    if (count($attributes) > 0) {
                        $listenerEvents["{$listener->name}@{$method->name}"] =
                            array_map(function (ReflectionAttribute $attribute) use (
                                $listener,
                                $method,
                                $module
                            ) {
                                return $attribute->newInstance()->getEvent($listener, $method, $module);
                            }, $attributes);
                        continue;
                    }
                }

                if (empty($event) && (! Str::is('handle*', $method->name) || ! isset($method->getParameters()[0]))) {
                    continue;
                }

                $listenerEvents["{$listener->name}@{$method->name}"] =
                    [Reflector::getParameterClassName($method->getParameters()[0])];
            }
        }

        return array_filter($listenerEvents);
    }

    /**
     * Extract the class name from the given file path.
     *
     * @param  \SplFileInfo  $file
     * @param  string  $basePath
     * @param  string  $namespace
     * @return string
     */
    protected static function classFromFile(SplFileInfo $file, $basePath, $namespace)
    {
        return str_replace(
            [$basePath, DIRECTORY_SEPARATOR],
            [$namespace, '\\'],
            ucfirst(Str::replaceLast('.php', '', $file->getRealPath()))
        );
    }
}
