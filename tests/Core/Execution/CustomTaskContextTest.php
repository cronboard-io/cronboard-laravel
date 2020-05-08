<?php

namespace Cronboard\Tests\Core\Execution;

use Cronboard\Core\Execution\Context\ConfigurationSettingOverride;
use Cronboard\Core\Execution\Context\EnvironmentVariableOverride;
use Cronboard\Tasks\Task;
use Cronboard\Tasks\TaskContext;
use Cronboard\Tests\Stubs\ContextRecordInvokable;
use Cronboard\Tests\TestCase;
use Illuminate\Support\Collection;
use Mockery as m;

class CustomTaskContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function callable_task_can_override_environment_variables()
    {
        $_ENV['TEST_ENV_VAR'] = 'testEnvDefault';

        $configOverrides = [];
        $envOverrides = [
            new EnvironmentVariableOverride('TEST_ENV_VAR', 'test1')
        ];
        $overrides = Collection::wrap($configOverrides)->merge($envOverrides)->toArray();
        $taskContext = new TaskContext($this->app, 'taskKey');
        $taskContext->setOverrides($overrides);

        $taskContext->enter();
        $invokable = new ContextRecordInvokable(['TEST_ENV_VAR']);
        $invokable();
        $taskContext->exit();

        $env = ContextRecordInvokable::getEnvironmentVariables();

        // config was set
        $this->assertArrayHasKey('TEST_ENV_VAR', $env);
        $this->assertEquals($env['TEST_ENV_VAR'], 'test1');

        // config was reset
        $this->assertEquals(env('TEST_ENV_VAR'), 'testEnvDefault');
    }

    /** @test */
    public function callable_task_can_override_config_settings()
    {
        $this->app['config']->set('test.value', 'testDefault');

        $configOverrides = [
             new ConfigurationSettingOverride('test.value', 'test1')
        ];
        $envOverrides = [];
        $overrides = Collection::wrap($configOverrides)->merge($envOverrides)->toArray();
        $taskContext = new TaskContext($this->app, 'taskKey');
        $taskContext->setOverrides($overrides);

        $taskContext->enter();
        $invokable = new ContextRecordInvokable([], ['test.value']);
        $invokable();
        $taskContext->exit();

        $runtimeConfig = ContextRecordInvokable::getConfigurationSettings();

        // config was set
        $this->assertArrayHasKey('test.value', $runtimeConfig);
        $this->assertEquals($runtimeConfig['test.value'], 'test1');

        // config was reset
        $this->assertEquals($this->app['config']->get('test.value'), 'testDefault');
    }

    protected function tearDown(): void
    {
        m::close();
    }
}