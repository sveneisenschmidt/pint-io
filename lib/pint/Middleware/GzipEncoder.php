<?php

namespace pint\Middleware;

use pint\Middleware\MiddlewareAbstract,
    pint\Exception;

class GzipEncoder extends MiddlewareAbstract
{
    /**
     *
     * @var array
     */
    protected $options = array(
        'threshold' => 512, //bytes  
        'level'     => 6,
        'exclude'   => array(
            'image/jpeg',
            'image/jpg',
            'image/png'
        )
    );
    
    /**
     *
     * @param array $env
     * @param array $response
     * @return array 
     */
    function call($env = array(), array $response = null)
    {
        if(isset($env['HTTP_ACCEPT_ENCODING']) && strpos($env['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
            
            if(isset($response[2]) && !empty($response[2]) && strlen($response[2]) > $this->option('threshold')) {
                $response[1] = array_merge($response[1], array(
                    'Content-Encoding' => 'gzip'    
                ));
                $response[2] = gzencode($response[2]);
            }
        }
        
        return $this->next($env, $response);
    }
}
