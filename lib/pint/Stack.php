<?php

namespace pint;

use \pint\Exception,
    \pint\App\AppInterface;

class Stack
{
    protected $middleware = array();

    protected $app = null;

    
    protected $list = array();
    
    
    function push($item, $options = array())
    {        
        if(in_array('pint\App\AppInterface', class_implements($item))) {
            $this->app = $item;
        }
            
        $this->list []= array($item, $options);
    }

    function build($env, $socket)
    {
        if (!$this->app) {
            throw new Exception('No app specified!');
        }
        
        $list  = array_reverse($this->list);
        $stack = null;
        
        foreach($list as $item) {
            $obj = $this->buildCallable($item[0], $item[1]);
            $obj->set($stack);
            $stack = $obj;
        }
        
        return $stack;
    }

    /**
     * @api
     */
    function buildCallable($src, $options = null)
    {
        $obj = is_object($src) ? $src : new $src($options);
        
        if (!method_exists($obj, "call")) {
            throw new Exception(get_class($obj) . " does not have a call() method.");
        }

        return $obj;
    }

    function call($env, $socket)
    {
        return $this->build($env, $socket)->call($env);
    }
}
