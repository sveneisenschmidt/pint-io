<?php

namespace rubidium;

class Request
{
    protected $env;

    function __construct(array $env)
    {
        $this->env =& $env;
    }

    function env()
    {
        return $this->env;
    }
}
