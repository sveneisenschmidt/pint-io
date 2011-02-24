<?php

namespace pint\Middleware;

use pint\Middleware\MiddlewareAbstract;

class FileInterceptor extends MiddlewareAbstract
{
    /**
     *
     * @param array $env
     * @return mixed 
     */
    function call($env = array(), array $response = null)
    {
        if($dir = $this->option('dir')) {
            
            $path = ltrim($env['PATH_INFO'], '/');
            $dir  = rtrim($dir, '/');
            $file = realpath($dir . '/' . $path);
            
            if(file_exists($file) && is_file($file)) {
                $type = mime_content_type($file);
                $data = file_get_contents($file);
                return array(
                    200,
                    array(),
                    $data
                );
            }
        }
        return $this->next($env);
    }
}
