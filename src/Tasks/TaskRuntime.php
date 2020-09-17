<?php

namespace Cronboard\Tasks;

use BadMethodCallException;
use Cronboard\Core\Execution\Context\ParseContext;
use Cronboard\Support\Storage\Storable;
use Cronboard\Tasks\Context\CollectorModule;
use Cronboard\Tasks\Context\EnvironmentModule;
use Cronboard\Tasks\Context\ExceptionModule;
use Cronboard\Tasks\Context\OverridesModule;
use Cronboard\Tasks\Context\ReportModule;
use Cronboard\Tasks\Context\StateModule;
use Cronboard\Tasks\Task;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ReflectionClass;

/**
* @method void setOnce($once = true)
* @method void setActive($active = true)
* @method void setTracking($tracking = true)
* @method bool isTracked()
* @method void stopTracking()
* @method void startTracking()
* @method bool isActive()
* @method bool shouldExecuteImmediately()
*
* @method void setOverrides(array $overrides = [])
* @method \Illuminate\Support\Collection getExecutionContext()
*
* @method void report($key, $value)
* @method array getReport()
*
* @method \Cronboard\Core\Execution\Collectors\Collector getCollector()
*
* @method void setException(Throwable $exception)
* @method array getException()
*
* @method \Cronboard\Support\Environment getEnvironment()
*/
class TaskRuntime
{
    use Storable;

    const KEY_PREFIX = 'cronboard.tasks.';

    protected $task;
    protected $entered;

    protected $modules;
    protected $hooks;

    public function __construct(string $key, Container $container = null)
    {
        $modules = [
            StateModule::class,
            OverridesModule::class,
            ReportModule::class,
            CollectorModule::class,
            ExceptionModule::class,
            EnvironmentModule::class,
        ];

        $container = $container ?: Container::getInstance();

        $this->task = $key;
        $this->entered = false;

        $this->modules = Collection::wrap($modules)->combine($modules)->map(function($module) use ($container) {
            return $container->make($module);
        });
        $this->hooks = $this->modules->map->getHooks();

        $this->load();
    }

    public function toArray(): array
    {
        $array = $this->modules->keyBy(function($module) {
            return $this->getModuleKey($module);
        })->map->toArray()->all();

        $array['task'] = $this->task;

        return $array;
    }

    public function loadFromArray(array $array)
    {
        $this->modules->each(function($module) use ($array) {
            $module->load(Arr::get($array, $this->getModuleKey($module)) ?: []);
        });
    }

    public function getStorableKey(): string
    {
        return $this->buildKey($this->task);
    }

    public static function inheritTaskRuntime(string $childKey, string $parentKey): TaskRuntime
    {
        $context = new static($childKey);
        $context->load($context->buildKey($parentKey));
        $context->store();
        return $context;
    }

    public static function fromTaskKey(string $key): TaskRuntime
    {
        return new static($key);
    }

    public static function fromTask(Task $task): TaskRuntime
    {
        return static::fromTaskKey($task->getKey());
    }

    public function getTask(): string
    {
        return $this->task;
    }

    public function enter(): TaskRuntime
    {
        if ($this->entered) {
            return $this;
        }

        $this->entered = true;
        $this->notify('onContextEnter');

        $this->store();

        return $this;
    }

    public function finalise(): TaskRuntime
    {
        $this->notify('onContextFinalise');

        $this->store();

        return $this;
    }

    public function exit(bool $keep = false): TaskRuntime
    {
        $this->entered = false;
        $this->notify('onContextExit');

        $this->store();

        if (! $keep) {
            $this->destroy();
        }

        return $this;
    }

    public function exitAndKeep(): TaskRuntime
    {
        return $this->exit(true);
    }

    private function notify(string $callback, array $arguments = [])
    {
        $this->modules->each(function($module) use ($callback, $arguments) {
            call_user_func_array([$module, $callback], $arguments);
        });
    }

    private function getModuleKey($module): string
    {
        return (new ReflectionClass($module))->getShortName();
    }

    private function buildKey(string $key): string
    {
        return static::KEY_PREFIX . $key;
    }

    public function __call(string $method, array $args)
    {
        $supportedByModule = $this->hooks->map(function(array $hooks, string $moduleKey) use ($method) {
            return in_array($method, $hooks) ? $moduleKey : null;
        })->filter()->first();

        if (! empty($supportedByModule)) {

            $module = $this->modules->get($supportedByModule);
            $result = call_user_func_array([$module, $method], $args);

            if ($module->shouldStoreAfter($method)) {
                $this->store();
            }

            return $result;
        }
        throw new BadMethodCallException("Method does not exist: " . $method, 1);
    }
}
