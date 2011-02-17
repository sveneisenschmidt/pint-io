<?php

namespace pint\Middleware;

use \pint\Mixin\OptionsAbstract;

class MiddlewareAbstract extends OptionsAbstract
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
    function call(array $env)
    {
        $env["pint"]["errors"] []= get_class() . " is missing a call() method.";

        return array(
            500,
            array("Content-Type" => "text/html"),
            "Hi, I'm " . get_class($this) . " and I'm bugging you because you " .
                "didn't implement a proper call() method for me :-X"
        );
    }
}
