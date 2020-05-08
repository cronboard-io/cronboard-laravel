<?php

namespace Cronboard\Tests\Tasks;

use Cronboard\Commands\Command;
use Cronboard\Core\Api\Endpoints\Tasks;
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

        $this->app->instance(Tasks::class, $tasks = m::mock(Tasks::class));

        $cronboard = $this->app->make(Cronboard::class);
        $cronboard->setTaskContext($task);

        $this->assertNotNull($taskContext = $cronboard->getContext());
        $this->assertEquals($taskContext->getTask(), 'taskKey');

        $tasks->shouldReceive('start')->with(m::on(function($runtimeTask) {
            return $runtimeTask->getKey() === 'taskKey';
        }), m::any())->once()->andReturn([
            'success' => true,
            'key' => 'taskInstanceKey'
        ]);

        $this->assertTrue($cronboard->start($task));
        $this->assertNotNull($taskContext2 = $cronboard->getContext());
        $this->assertEquals($taskContext2->getTask(), 'taskInstanceKey');
    }
}