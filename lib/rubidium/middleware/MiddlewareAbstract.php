<?php

namespace rubidium\middleware;

class MiddlewareAbstract
{
    protected $options, $app;

    function __construct($options = array())
    {
        $this->options = $options;
    }

    function options()
    {
        return $this->options;
    }

    function app($app = null)
    {
        if (!is_null($app))
        {
            $this->app = $app;
        }

        return $this->app;
    }

    function call(array $env)
    {
        $env["rubidium"]["errors"] []= get_class() . " is missing a call() method.";

        return array(
            500,
            array("Content-Type" => "text/html"),
            "Hi, I'm " . get_class($this) . " and I'm bugging you because you " .
                "didn't implement a proper call() method for me :-X"
        );
    }
}
