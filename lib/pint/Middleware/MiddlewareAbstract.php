<?php

namespace pint\Middleware;

use \pint\Mixin\OptionsAbstract;

abstract class MiddlewareAbstract extends OptionsAbstract
{
    /**
     *
     * @var type 
     */
    protected $next;

    /**
     *
     * @param array $env
     * @return type 
     */
    abstract function call($env = array(), array $response = null);

    /**
     *
     * @param array $env
     * @return type 
     */
    final function next($env = array(), array $response = null)
    {
        if(empty($env) && is_null($response)) {
            return $this->next;
        }
        
        if($this->next == null) {
            return $response;
        }
        
        return $this->next->call($env, $response);
    }

    /**
     *
     * @param array $env
     * @return type 
     */
    final function set($next)
    {
        $this->next = $next;
    }
}
