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
        $headers = "";
        $headersComplete = false;
        $body = "";
        $continued = false;
        while (true)
        {
            $chunk = $socket->receive(self::$chunkSize);
            if ($chunk === false || is_null($chunk)) {
                break;
            } if (!$headersComplete) {
                $parts = \explode("\r\n\r\n", $chunk);
                $headers .= $parts[0];
                
                if (!is_array($headers) && \preg_match("#\r\nExpect: *100-continue\r\n#", $headers) && $continued == false) {
                    $buffer = "HTTP/1.1 100 Continue\r\n";
                    
                    \socket_write($socket->resource(), $buffer, strlen($buffer));
                    sleep(1);
                    $continued = true;
                    continue;
                }        
                
                if (isset($parts[1])) {
                    $headersComplete = true;
                    if (!\preg_match("#\r\nContent-Length: *([^\s]*)\r\n#", $headers, $match)) {
                        if (!empty($parts[1])) {
                            return false;
                        }
                        break;
                    } else {
                        if (!\preg_match("#^\d+$#", $match[1])) {
                            return false;
                        }
                        $remaining = (int)$match[1];
                        $remaining -= \strlen($parts[1]);
                        $body .= $parts[1];
                    }
                }
            } else {
                $chunkLength = \strlen($chunk);
                if ($chunkLength > $remaining) {
                    $body .= substr($chunk, 0, $remaining);
                    $remaining = 0;
                } else {
                    $body .= $chunk;
                    $remaining -= $chunkLength;
                }

                if (!$remaining) {
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
