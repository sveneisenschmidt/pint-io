<?php

namespace pint;

class Request implements \ArrayAccess
{
    protected $env;

    function __construct(array $env)
    {
        $this->env = $env;
    }

    function env()
    {
        return $this->env;
    }
}
