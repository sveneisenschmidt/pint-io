<?php

namespace pint;

use \pint\Exception,
    \pint\Socket,
    \pint\Mixin\ContainerAbstract;

/**
 * 
 * 
 */
class Request extends ContainerAbstract
{
    /**
     *
     * @var int
     */
    public static $bytes = 1024;
    
    /**
     *
     * @var string
     */
    public $container = 'env';
    
    /**
     *
     * @var array
     */
    protected $env = array();
    
    /**
     *
     * @var array
     */
    protected static $filters = array(
        '\pint\Request\Filters::parseHeaders',
        '\pint\Request\Filters::parseRequestLine',
        '\pint\Request\Filters::validateContentType',
        '\pint\Request\Filters::createServerEnv'
    );
    
    /**
     *
     * @var string
     */
    protected $errormsg = null;
    
    /**
     * 
     * @param \pint\Socket $socket
     * @return \pint\Request
     */
    public static function parse(Socket $socket, array $filters = null)
    {
        $instance = new self();
        $input    = self::read($socket);

        if(empty($input)) {
            throw new Exception('Empty in input received from socket!');
        }
        
        if(is_null($filters)) {
            $filters = self::$filters;  
        }
        
        foreach($filters as $func) {
            if(is_string($func) && \strpos($func, '::') !== false) {
                $func = \explode('::', $func);
            }
            try {
                static::callFilter($func, array($instance, $input));
            } catch(Exception $e) {
                $instance->errormsg($e->getMessage());
                break;
            }
        }
        
        return $instance;
    }
    
    /**
     * 
     * @param \pint\Socket $socket
     * @return string
     */
    public static function read(Socket $socket)
    {
        $buffer = "";
        while (substr($buffer, -4) !== "\r\n\r\n")
        {
            $chunk = $socket->receive(self::$bytes);
            if ($chunk === false) break; 
            
            $buffer .= $chunk;
        }
    
        return $buffer;
    }
    
    /**
     * 
     * @return void
     */
    public static function callFilter($name, $args)
    {
        \call_user_func_array($name, $args);
    }
    
    /**
     *
     * @return array
     */
    public function headers()
    {
        $headers = array();
        
        foreach($this->env as $key => $value) {
            if(substr($key, 0, 5) == 'HTTP_') {
                $headers[$key] = $value;
            }
        }        
        
        return $headers;
    }
    
    /**
     *
     * @return string
     */
    public function method()
    {
        return $this->env['REQUEST_METHOD'];
    }
    
    /**
     *
     * @return string
     */
    public function version()
    {
        return $this->env['HTTP_VERSION'];
    }
    
    /**
     *
     * @return string
     */
    public function uri()
    {
        return $this->env['REQUEST_URI'];
    }
    
    /**
     *
     * @return string
     */
    public function errormsg($msg = null)
    {
        if(!is_null($msg)) {
            $this->errormsg = $msg;
            return;
        }
        return $this->errormsg;
    }
    
    /**
     *
     * @return string
     */
    public function haserror()
    {
        return !(trim($this->errormsg) == "");
    }
    
    /**
     * 
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset) 
    {
        throw new \pint\Exception('No allowed to unset any values!');
    }
    
}
