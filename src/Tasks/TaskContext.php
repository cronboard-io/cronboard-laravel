<?php

namespace Cronboard\Tasks;

use BadMethodCallException;
use Cronboard\Core\Execution\Context\ParseContext;
use Cronboard\Support\Storage\Storage;
use Cronboard\Tasks\Context\CollectorModule;
use Cronboard\Tasks\Context\EnvironmentModule;
use Cronboard\Tasks\Context\ExceptionModule;
use Cronboard\Tasks\Context\OutputModule;
use Cronboard\Tasks\Context\OverridesModule;
use Cronboard\Tasks\Context\ReportModule;
use Cronboard\Tasks\Context\StateModule;
use Cronboard\Tasks\Task;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ReflectionClass;

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
            OutputModule::class,
            CollectorModule::class,
            ExceptionModule::class,
            EnvironmentModule::class,
        ];

        $this->container = $container;
        $this->task = $taskKey;
        $this->entered = false;

        $this->modules = Collection::wrap($modules)->combine($modules)->map(function($module){
            return $this->container->make($module);
        });
        $this->hooks = $this->modules->map->getHooks();

        $this->storage = new Storage($container);

        $this->load();
    }

    public static function fromTask(Container $container, Task $task)
    {
        return new static($container, $task->getKey());
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

    public function exit(): TaskContext
    {
        $this->entered = false;
        $this->notify('onContextExit');

        $this->store();

        $this->destroy();

        return $this;
    }

    public function toArray()
    {
        $array = $this->modules->keyBy(function($module){
            return $this->getModuleKey($module);
        })->map->toArray();

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

    private function load()
    {
        $data = $this->storage->get($this->getKey());

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
        return 'cronboard.tasks.' . $this->task;
    }
}
