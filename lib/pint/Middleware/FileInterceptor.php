<?php

namespace pint\Middleware;

use pint\Middleware\MiddlewareAbstract;

class FileInterceptor extends MiddlewareAbstract
{
    
    protected $options = array(
        'skip' => 0    
    );
    
    /**
     *
     * @param array $env
     * @return mixed 
     */
    function call($env = array(), array $response = null)
    {
        if($dir = $this->option('dir')) {
            
            $skip = (int) $this->option('skip');
            $path = ltrim($env['PATH_INFO'], '/');
            $dir  = rtrim($dir, '/');
    
            $file = new \SplFileInfo(realpath($dir . '/' . $path));
            
            if($file->isFile($file)) {
                
                $response = array(
                    200, 
                    array(), 
                    file_get_contents($file)
                );
                
                if($skip > 0) {
                    $next = $this;
                    for($i = 0; $i < $skip; $i++) {
                        $next = $next->next();                
                    }
                    
                    return $next->next($env, $response);
                }  
                
                return $response;
            }
        }
        return $this->next($env);
    }
}
