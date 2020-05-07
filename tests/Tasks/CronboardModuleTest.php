<?php

namespace Cronboard\Tests\Tasks;

use Cronboard\Tasks\Context\ReportModule;
use Cronboard\Tests\TestCase;

class CronboardModuleTest extends TestCase
{
    /** @test */
    public function it_reports_only_for_scalar_values()
    {
        $module = new ReportModule;
        
        $module->report('string', 'keyAsString');
        $module->report(123, 'keyAsNumber');
        $module->report(123, 'keyAsNumber');
        $module->report($object = new \stdClass, 'keyAsObject');
        $module->report('valueAsObject', new \stdClass);
        $module->report('valueAsArray', []);

        $report = $module->getReport();

        $this->assertArrayHasKey('string', $report); 
        $this->assertArrayHasKey(123, $report);
        $this->assertCount(2, $report);
    }
}