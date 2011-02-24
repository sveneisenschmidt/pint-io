<?php

namespace pint\Adapters;

use \pint\App\AppAbstract,
    \pint\Request,
    \pint\Adapters\Symfony2Adapter\Exception;

abstract class Symfony2Adapter extends AppAbstract
{
    /**
     *
     * @var string
     */
    protected $kernelClass = null;
    
    /**
     *
     * @var string
     */
    protected $kernelFile = null;
    
    
    /**
     *
     * @var  \Symfony\Component\HttpKernel\Kernel
     */
    protected $kernel = null;
    
    /**
     *
     * @var string
     */
    protected $stage = 'prod';
    
    /**
     *
     * @var boolean
     */
    protected $debug = false;
    
    /**
     *
     * @var boolean
     */
    private $prepared = false;
    
    
    final public function call(\pint\Request $env) 
    {
        if(!$this->prepared) {
            $this->prepare();
        }
        
        $kernel  = clone $this->_kernel();
        $request = $this->_request($env);
        
        try {
            $response = $kernel->handle($request);
        } catch(\Exception $e) {
            return array(500, array(), 'Symfony2Adapter: ' . $e->__toString());    
        }
        
        $headers = array_merge(array(
            "Content-Type"    => 'text/html' 
        ), $response->headers->all());
        
        return $this->next($env, array(
            $response->getStatusCode(),
            $headers,
            $response->getContent()                
        ));
    }
    
    /**
     * The path to your Symfony2 bootstrap/autoload files
     *
     * @var string
     */
    public function bootstrap($file) 
    {
        if(!file_exists($file)) {
            throw new Exception('Could not find/access bootstrap file: ' . realpath($file));
        }
        
        require_once($file);    
    }
    
    /**
     * The path to your Symfony2 bootstrap/autoload files
     *
     * @var string
     */
    public function kernel($class, $file = null) 
    {
        if(!is_null($file)) {
            if(!file_exists($file)) {
                throw new Exception('Could not find/access kernel file in ' . realpath($file));
            }
            $this->kernelFile  = $file;
        }
        
        $this->kernelClass = $class;
    }
    
    /**
     * The path to your Symfony2 bootstrap/autoload files
     *
     * @var string
     */
    public function stage($stage, $debug = null) 
    {
        $this->stage = $stage;
        
        if(!is_null($debug)) {
            $this->debug = (bool)$debug;
        }
    }
    
    /**
     *
     * @return void
     */
    public function prepare() 
    {
        $this->kernel = $this->_kernel();
        $this->prepared = true;
    }
    
    /**
     * The path to your Symfony2 bootstrap/autoload files
     *
     * @var string
     */
    protected function _kernel() 
    {
        if(!is_null($this->kernelFile) && !class_exists($this->kernelClass)) {
            require($this->kernelFile);
        }
        
        return new $this->kernelClass($this->stage, $this->debug);        
    }
    
    /**
     *
     * @var 
     */
    protected function _request(\pint\Request $env) 
    {
        $query      = $env->paramsGet();
        $request    = $env->paramsPost();
        $attributes = array();
        $cookies    = array();
        $files      = $env->files();
        $server     = $env->server();
        $content    = $env->body();
        
        return new \Symfony\Component\HttpFoundation\Request(
            $query, $request, $attributes, $cookies, $files, $server, $content);
    }
    
}
