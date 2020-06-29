<?php

namespace Cronboard\Tasks;

use BadMethodCallException;
use Cronboard\Core\Execution\Context\ParseContext;
use Cronboard\Support\Storage\Storage;
use Cronboard\Tasks\Context\CollectorModule;
use Cronboard\Tasks\Context\EnvironmentModule;
use Cronboard\Tasks\Context\ExceptionModule;
use Cronboard\Tasks\Context\OverridesModule;
use Cronboard\Tasks\Context\ReportModule;
use Cronboard\Tasks\Context\StateModule;
use Cronboard\Tasks\Task;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ReflectionClass;

/**
* @method void setOnce($once = true)
* @method void setActive($active = true)
* @method void setTracking($tracking = true)
* @method bool isTracked()
* @method bool isActive()
* @method bool shouldExecuteImmediately()
*
* @method void setOverrides(array $overrides = [])
* @method Collection getExecutionContext()
*
* @method void report($key, $value)
* @method array getReport()
*
* @method Collector getCollector()
*
* @method void setException(Exception $exception)
* @method ?Exception getException()
*
* @method Environment getEnvironment()
*/
class TaskContext implements Arrayable
{
    protected $container;
    protected $task;
    protected $entered;

    protected $modules;
    protected $hooks;

    protected $storage;

    public function __construct(Container $container, string $taskKey)
    {
        $modules = [
            StateModule::class,
            OverridesModule::class,
            ReportModule::class,
            CollectorModule::class,
            ExceptionModule::class,
            EnvironmentModule::class,
        ];

        $this->container = $container;
        $this->task = $taskKey;
        $this->entered = false;

        $this->modules = Collection::wrap($modules)->combine($modules)->map(function($module) {
            return $this->container->make($module);
        });
        $this->hooks = $this->modules->map->getHooks();

        $this->storage = static::getStorage($container);

        $this->load();
    }

    public static function getStorage(Container $container)
    {
        return $container->make(Storage::class);
    }

    public static function inheritTaskContext(Container $container, string $childKey, string $parentKey): TaskContext
    {
        $context = static::fromTaskKey($container, $childKey);
        $context->load($parentKey);
        $context->store();
        return $context;
    }

    public static function fromTaskKey(Container $container, string $key)
    {
        return new static($container, $key);
    }

    public static function fromTask(Container $container, Task $task)
    {
        return static::fromTaskKey($container, $task->getKey());
    }

    public function getTask(): string
    {
        return $this->task;
    }

    public function __call($method, $args)
    {
        $supportedByModule = $this->hooks->map(function($hooks, $moduleKey) use ($method) {
            return in_array($method, $hooks) ? $moduleKey : null;
        })->filter()->first();

        if (!empty($supportedByModule)) {

            $module = $this->modules->get($supportedByModule);
            $result = call_user_func_array([$module, $method], $args);

            if ($module->shouldStoreAfter($method)) {
                $this->store();
            }

            return $result;
        }
        throw new BadMethodCallException("Method does not exist: " . $method, 1);
    }

    public function enter(): TaskContext
    {
        if ($this->entered) {
            return $this;
        }

        $this->entered = true;
        $this->notify('onContextEnter');

        $this->store();

        return $this;
    }

    public function finalise(): TaskContext
    {
        $this->notify('onContextFinalise');

        $this->store();

        return $this;
    }

    public function exit(bool $keep = false): TaskContext
    {
        $this->entered = false;
        $this->notify('onContextExit');

        $this->store();

        if (! $keep) {
            $this->destroy();
        }

        return $this;
    }

    public function exitAndKeep(): TaskContext
    {
        return $this->exit(true);
    }

    public function toArray()
    {
        $array = $this->modules->keyBy(function($module) {
            return $this->getModuleKey($module);
        })->map->toArray();

        $array['task'] = $this->task;

        return $array;
    }

    protected function notify(string $callback, array $arguments = [])
    {
        $this->modules->each(function($module) use ($callback, $arguments) {
            call_user_func_array([$module, $callback], $arguments);
        });
    }

    private function destroy()
    {
        $this->storage->remove($this->getKey());
    }

    private function getModuleKey($module): string
    {
        return (new ReflectionClass($module))->getShortName();
    }

    private function load(string $taskKey = null)
    {
        $data = $this->storage->get($taskKey ? $this->buildKey($taskKey) : $this->getKey());

        $this->modules->each(function($module) use ($data) {
            $module->load(Arr::get($data, $this->getModuleKey($module)) ?: []);
        });
    }

    private function store()
    {
        $this->storage->store($this->getKey(), $this->toArray());
    }

    private function getKey(): string
    {
        return $this->buildKey($this->task);
    }

    private function buildKey(string $key): string
    {
        return 'cronboard.tasks.' . $key;
    }
}
