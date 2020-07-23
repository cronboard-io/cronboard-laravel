<?php

namespace Cronboard\Tests\Core;

use Cronboard\Commands\Command;
use Cronboard\Core\Api\Client;
use Cronboard\Core\Context\TaskContext;
use Cronboard\Core\Cronboard;
use Cronboard\Core\Reflection\Parameters;
use Cronboard\Tasks\Task;
use Cronboard\Tests\TestCase;
use Mockery as m;

class CronboardTasksApiTest extends TestCase
{
    /** @test */
    public function it_resolves_task_instances_when_starting()
    {
        $task = new Task(
            $taskKey = 'taskKey',
            $command = new Command('type', 'handler'),
            $parameters = new Parameters,
            $constraints = []
        );

        $tasks = $this->app->make(Client::class)->mockEndpoint($this->app, 'tasks');

        $cronboard = $this->app->make(Cronboard::class);
        $runtime = TaskContext::enter($task);

        $this->assertNotNull(TaskContext::getTask());
        $this->assertEquals(TaskContext::getTask()->getKey(), 'taskKey');

        $tasks->shouldReceive('start')->with(m::on(function($runtimeTask) {
            return $runtimeTask->getKey() === 'taskKey';
        }), m::any())->once()->andReturn([
            'success' => true,
            'key' => 'taskInstanceKey'
        ]);

        $this->assertTrue($cronboard->start($task));
        $this->assertNotNull(TaskContext::getTask());
        $this->assertEquals(TaskContext::getTask()->getKey(), 'taskInstanceKey');
    }
}