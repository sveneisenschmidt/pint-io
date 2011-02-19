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
        $headers = $body = $expectBuffer = "";
        $toRead = $bodyLength = 0;
        
        $headersComplete = false;
        $bodyComplete    = true;
        
        $continued = false;
        
        // goto mark
        headers:
        
        if($continued) {
            $expectBuffer .= "\r\n";}
            
        while (!$headersComplete) {
            $chunk = $socket->receive(self::$chunkSize);
            if ($chunk === false || is_null($chunk)) {
                break;
            } 
            $parts = \explode("\r\n\r\n", $chunk);
            $headers .= $parts[0];
            
            // when sending a "HTTP/1.1 100 Continue" you get some new headers back which are part 
            // of the Content Length!
            if($continued) {
                $expectBuffer .= $parts[0];
            }
            
            if(isset($parts[1])) {
                $headersComplete = true;
                $body .= $parts[1];
                break;
            }
        }
        if($continued) {
            $expectBuffer .= "\r\n";}
        
        // test if an expect, 100-continue header is set, welcome to the hell of HTTP!
        if(\preg_match("#\r\nExpect: *100-continue\r\n#", $headers) && $continued == false){
        
            // okay, we got an expect header, normally we head to check if the headers are correct
            // atm we are too lazy and just returning 'HTTP/1.1 100 Continue'
            $buffer  = "HTTP/1.1 100 Continue\r\n";
            $success = $socket->write($buffer, \strlen($buffer));
            
            if(!is_int($success)) {
                return false;
            }
            
            // now the client sents some additional headers, this makes everything the most complicated
            // so we need to jump back to the top and parse the additional headers
            $headersComplete = false;
            $continued = true;
            goto headers;
        }
        
            
        if (\preg_match("#\r\nContent-Length: *([^\s]*)#", $headers, $match)) {
            if(\preg_match("#^\d+$#", $match[1])) {
                $bodyLength  = (int)$match[1] - strlen($body) - strlen($expectBuffer);
            }

            $bodyComplete = (strlen($body) == $bodyLength);
        } 
        
        if(!$bodyComplete) {
            $toRead  = $bodyLength;
            while(true) {
                $chunk = $socket->receive(self::$chunkSize);
                if ($chunk === false || is_null($chunk) || strlen($chunk) == 0) {
                    break;
                } 
                
            
                $body   .= $chunk;
                $toRead -= \strlen($chunk);
                
                if($toRead <= 0) {
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
