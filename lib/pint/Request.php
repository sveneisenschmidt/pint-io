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
    public static $chunkSize = 1024;
    
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
        '\pint\Request\Filters::createServerEnv',
        '\pint\Request\Filters::createPathInfoEnv',
        '\pint\Request\Filters::validateContentType',
        '\pint\Request\Filters::processPostPutIf',
        
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
    public static function parse(Socket $socket, array $config = array(), array $filters = null)
    {
        $instance = new self();

        if (!$input = self::read($socket)) {
            return false;
        }

        if(is_null($filters)) {
            $filters = self::$filters;  
        }
        
        foreach($filters as $func) {
            $func = \explode('::', $func);
            try {
                static::callFilter($func, array($instance, $input, $config));
            } catch(Exception $e) {
                $instance->errormsg($e->getMessage());
        
                die($e->getMessage() . "\r\n");
                return false;
            }
        }
        
        return $instance;
    }
    
    /**
     * 
     * @param \pint\Socket $socket
     * @return array|false
     */
    public static function read(Socket $socket)
    {
        $headers = $body = "";
        $headersComplete = $bodyComplete = false;
        $toRead = $bodyLength = 0;
        
        while (!$headersComplete) {
            $chunk = $socket->receive(self::$chunkSize);
            if ($chunk === false || is_null($chunk)) {
                break;
            } 
            
            $parts = \explode("\r\n\r\n", $chunk);
            $headers .= $parts[0];
            
            if(isset($parts[1])) {
                $headersComplete = true;
                $body .= $parts[1];
                
                break;
            }
        }
        
        if (\preg_match("#\r\nContent-Length: *([^\s]*)\r\n#", $headers, $match)) {
            if(\preg_match("#^\d+$#", $match[1])) {
                $bodyLength = $match[1];
            }

            $bodyComplete = (strlen($body) == $bodyLength);
        } 
        
        if(!$bodyComplete) {
            $toRead   = ($bodyLength - strlen($body));
            $beenRead = 0;
            
            while($toRead > 0) {
                $body     .= $socket->receive(self::$chunkSize); 
                $beenRead += self::$chunkSize;
                $toRead   -= self::$chunkSize;
                
                if($toRead < 0) {
                    break;
                }
            }
        }        
        
        if(empty($headers)) {
            return false;
        }
        
        return array($headers, $body);
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
