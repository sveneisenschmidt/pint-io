<?php

namespace pint\Middleware;

use \pint\Mixin\OptionsAbstract;

abstract class MiddlewareAbstract extends OptionsAbstract
{
    
    /**
     *
     * @var type 
     */
    protected $app;

    /**
     *
     * @param type $app
     * @return type 
     */
    function app($app = null)
    {
        if (!is_null($app))
        {
            $this->app = $app;
        }

        return $this->app;
    }

    /**
     *
     * @param array $env
     * @return type 
     */
    abstract function call($env = array());
}
