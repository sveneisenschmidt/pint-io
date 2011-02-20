<?php

namespace pint;

use \pint\Exception,
    \pint\Socket,
    \pint\Socket\ChildSocket,
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
        '\pint\Request\Filters::createRemoteEnv',
        '\pint\Request\Filters::createPathInfoEnv',
        '\pint\Request\Filters::validateContentType',
        '\pint\Request\Filters\PostPutFilter::parse',
        
    );
    
    /**
     *
     * @var string
     */
    protected $errormsg = null;
    
    /**
     * 
     * @param \pint\Socket\ChildSocket $socket
     * @return \pint\Request
     */
    public static function parse(ChildSocket $socket, array $config = array(), array $filters = null)
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
                static::callFilter($func, array($instance, $input, $socket, $config));
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
        $toRead  = $bodyLength = 0;
        
        $headersComplete = false;
        $bodyComplete    = true;
        $continued       = false;
        
        while (!$headersComplete) {
            $chunk = $socket->receive(self::$chunkSize);
            if ($chunk === false || is_null($chunk)) {
                break;
            } 
            $parts = \explode("\r\n\r\n", $chunk);
            $headers .= $parts[0];
            
            if(count($parts) > 1) {
                $headersComplete = true;
                unset($parts[0]);
                foreach($parts as $part) {
                    $body .= "\r\n\r\n" . $part;
                }
                break;
            }
        }
        
        // test if an expect, 100-continue header is set, welcome to the hell of HTTP!
        if(\preg_match("#\r\nExpect: *100-continue\r\n#", $headers) && $continued == false){
        
            // okay, we got an expect header, normally we head to check if the headers are correct
            // atm we are too lazy and just returning 'HTTP/1.1 100 Continue'
            $buffer  = "HTTP/1.1 100 Continue\r\n";
            $success = $socket->write($buffer, \strlen($buffer));
            
            if(!is_int($success)) {
                return false;
            }
            
            // now the client sents some additional headers
            $continued = true;
        }  
        
        if (\preg_match("#\r\nContent-Length: *([^\s]*)#", $headers, $match) ||
            \preg_match("#\r\nContent-Length: *([^\s]*)#", $body, $match)
        ) {
            if(\preg_match("#^\d+$#", $match[1])) {
                $bodyLength  = (int)$match[1];
            }
            
            $bodyLength  -= ($continued == true) ? strlen($body) : strlen($headers);
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
    
    /**
     * 
     * @return array
     */
    public function server($filter = true) 
    {
        return $this->toArray($filter);        
    }
    
    /**
     * 
     * @return array
     */
    public function toArray($filter = false) 
    {
        if($filter === false) {
            return $this->env;
        }
        
        $server = array();
        foreach($this->env as $key => $value) {
            if(substr($key, 0, 5) != 'PINT_') {
                $server[$key] = $value;
            }
        }        
        return $server;      
    }
    
    /**
     * 
     * @return array
     */
    public function paramsGet() 
    {
        if(!isset($this->env['QUERY_STRING']) || 
           (isset($this->env['QUERY_STRING']) && empty($this->env['QUERY_STRING']))
        ) {
            return array();
        }     
        
        @\parse_str($this->env['QUERY_STRING'] , $params);
        return $params;
    }
    
    /**
     * 
     * @return array
     */
    public function paramsPost() 
    {
        if(!isset($this->env['PINT_FIELDS']) || 
           (isset($this->env['PINT_FIELDS']) && !\is_array($this->env['PINT_FIELDS']))
        ) {
            return array();
        } 
        
        // Note:
        // we have to transform fields with nested names 
        
        return $this->env['PINT_FIELDS'];   
    }
    
    /**
     * 
     * @return array
     */
    public function files() 
    {
        if(!isset($this->env['PINT_FILES']) || 
           (isset($this->env['PINT_FILES']) && !\is_array($this->env['PINT_FILES']))
        ) {
            return array();
        } 
        
        // Note:
        // we have to transform files with nested names 
        
        return $this->env['PINT_FILES'];   
    }
    
}
