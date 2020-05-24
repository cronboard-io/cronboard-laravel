<?php

namespace Cronboard\Tasks\Context;

use Illuminate\Console\Scheduling\Event;

class OutputModule extends Module
{
    protected $output;
    protected $outputSource;


    public function load(array $data)
    {
        $this->output = $data['output'] ?? null;
        $this->outputSource = $data['outputSource'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'output' => $this->output,
            'outputSource' => $this->outputSource,
        ];
    }

    public function getHooks(): array
    {
        return [
            'readsOutputFromEvent',
            'getOutput'
        ];
    }

    public function shouldStoreAfter(string $hookName): bool
    {
        return in_array($hookName, [
            'readsOutputFromEvent',
            'setOutput'
        ]);
    }

    public function onContextFinalise()
    {
        if (empty($this->outputSource) && file_exists($this->outputSource)) {
            $this->setOutput(trim(file_get_contents($this->outputSource)));
        }
    }

    public function readsOutputFromEvent(Event $event)
    {
        $this->outputSource = $this->getEventOutput($event);
    }

    public function getOutput()
    {
        return $this->output;
    }

    private function getEventOutput(Event $event)
    {
        if (!$event->output ||
            $event->output === $event->getDefaultOutput() ||
            $event->shouldAppendOutput ||
            !file_exists($event->output)) {
            return null;
        }
        return $event->output;
    }

    private function setOutput($output)
    {
        $this->output = $output;
    }
}
