<?php

namespace Cronboard\Core\Api\Endpoints;

class Projects extends Endpoint
{
    public function projects()
    {
        return $this->get('projects');
    }
}
