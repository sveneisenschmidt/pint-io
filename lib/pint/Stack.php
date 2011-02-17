<?php

namespace pint;

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

    function build()
    {
        if (!$this->app()) {
            $this->app(new App());
        }
        
        $stack = $this->buildCallable($this->app());
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
        return $this->build()->call($env);
    }
}
