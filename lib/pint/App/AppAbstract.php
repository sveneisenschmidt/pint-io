<?php

namespace pint\App;

use \pint\App\AppInterface;

abstract class AppAbstract implements AppInterface {
    
    /**
     *
     * @var \pint\Middleware\MiddlewareAbstract
     */
    protected $next = null;
    
    public function __construct() {}   
    
    function process(\pint\Request $env, \pint\Socket\ChildSocket $socket) {}

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
