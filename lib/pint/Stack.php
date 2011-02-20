<?php

namespace pint;

use \pint\Exception,
    \pint\App\AppInterface;

class Stack
{
    protected $middleware = array();

    protected $app = null;

    function middleware($mw = null, $options = array())
    {
        if ($mw) {
            $this->middleware []= array($mw, $options);
        } else {
            return $this->middleware;
        }
    }

    function app($app = null)
    {
        if ($app) {
            $this->app = $app;
        } else {
            return $this->app;
        }
    }

    function build($env)
    {
        if (!$this->app()) {
            throw new Exception('No app specified!');
        }
        
        $stack = $this->buildCallable($this->app());
        if(method_exists($stack, 'process')) {
            call_user_func(array($stack, 'process'), $env);
        }
        
        foreach ($this->middleware() as $mw)
        {
            $outer = $this->buildCallable($mw[0], $mw[1]);
            $outer->app($stack);
            $stack = $outer;
        }

        return $stack;
    }

    /**
     * @api
     */
    function buildCallable($src, $options = null)
    {
        $obj = is_object($src) ? $src : new $src($options);
        
        if (!method_exists($obj, "call"))
        {
            throw new Exception(get_class($obj) . " does not have a call() method.");
        }

        return $obj;
    }

    function call($env)
    {
        return $this->build($env)->call($env);
    }
}
