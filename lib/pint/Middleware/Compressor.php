<?php

namespace pint\Middleware;

use pint\Middleware\MiddlewareAbstract,
    pint\Exception;

class Compressor extends MiddlewareAbstract
{
    /**
     *
     * @var array
     */
    protected $options = array(
        'threshold' => 512, //bytes  
        'level'     => 6,
        'support'   => array()
    );
        
    /**
     *
     * @var array
     */
    protected $supported = array(
        'gzip'      => 'gzencode',
        'deflate'   => 'gzdeflate'
    );
    
    /**
     *
     * @param array $env
     * @param array $response
     * @return array 
     */
    function call($env = array(), array $response = null)
    {
        $support = $this->option('support');
        if(empty($support) || is_null($support)) {
            $this->option('support', array_keys($this->supported));
        }    
        if(is_string($support)) {
            $this->option('support', array($support));
        }    
            
        $level     = $this->option('level');
        $supported = array_keys($this->supported);     
        $methods   = \array_filter($this->option('support'), function($compression) use ($supported) {
            return \in_array($compression, $supported);            
        });   
        
        foreach($methods as $method) {
            if(isset($env['HTTP_ACCEPT_ENCODING']) && strpos($env['HTTP_ACCEPT_ENCODING'], $method) !== false) {
                if(isset($response[2]) && !empty($response[2]) && strlen($response[2]) > $this->option('threshold')) {
                    $function = $this->supported[$method];
                    $response[1] = array_merge($response[1], array(
                        'Content-Encoding' => $method   
                    ));
                    $response[2] = \call_user_func_array($function, array($response[2], $level));
                    break;
                }        
            }
        }
        
        return $this->next($env, $response);
    }
}