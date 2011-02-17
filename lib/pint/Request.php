<?php

namespace pint;

use \pint\Exception,
    \pint\Mixin\ContainerAbstract;

/**
 * 
 * 
 */
class Request extends ContainerAbstract
{
    /**
     *
     * @var array
     */
    protected $container = array(
        'headers' => array(),
        'method'  => null,
        'version' => null,
        'uri' => null
    );
    
    /**
     *
     * @var array
     */
    protected static $filters = array(
        '\pint\Request\Filters::parseHeaders',
        '\pint\Request\Filters::parseRequestLine',
        '\pint\Request\Filters::validateContentType'
    );
    
    /**
     *
     * @var array
     */
    protected $status = array(
        100 => "Continue",
        101 => "Switching Protocols",
        200 => "OK",
        201 => "Created",
        202 => "Accepted",
        203 => "Non-Authoritative Information",
        204 => "No Content",
        205 => "Reset Content",
        206 => "Partial Content",
        300 => "Multiple Choices",
        301 => "Moved Permanently",
        302 => "Found",
        303 => "See Other",
        304 => "Not Modified",
        305 => "Use Proxy",
        307 => "Temporary Redirect",
        400 => "Bad Request",
        401 => "Unauthorized",
        402 => "Payment Required",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
        406 => "Not Acceptable",
        407 => "Proxy Authentication Required",
        408 => "Request Timeout",
        409 => "Conflict",
        410 => "Gone",
        411 => "Length Required",
        412 => "Precondition Failed",
        413 => "Request Entity Too Large",
        414 => "Request URI Too Long",
        415 => "Unsupported Media Type",
        416 => "Requested Range Not Satisfiable",
        417 => "Expectation Failed",
        500 => "Internal Server Error",
        501 => "Method Not Implemented",
        502 => "Bad Gateway",
        503 => "Service Unavailable",
        504 => "Gateway Timeout",
        505 => "HTTP Version Not Supported"
    );
    
    /**
     *
     * @var string
     */
    protected $errormsg = null;
    
    /**
     * 
     * @param string $input
     * @return boolean|\pint\Request
     */
    public static function parse($input, array $filters = null)
    {
        $instance = new self();

        if(empty($input)) {
            throw new Exception('Empty in input received from connection!');
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
        return $this->offsetGet('headers');
    }
    
    /**
     *
     * @return string
     */
    public function method()
    {
        return $this->offsetGet('method');
    }
    
    /**
     *
     * @return string
     */
    public function version()
    {
        return $this->offsetGet('version');
    }
    
    /**
     *
     * @return string
     */
    public function uri()
    {
        return $this->offsetGet('uri');
    }
    
    /**
     *
     * @param int|string $code
     * @return string
     */
    public function statusmsg($code)
    {
        return $this->status[$code];
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
