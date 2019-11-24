<?php

namespace Cronboard\Core\Api\Endpoints;

class Account extends Endpoint
{
    public function info()
    {
        return $this->get('account');
    }
}
