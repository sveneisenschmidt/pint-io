<?php

namespace pint;

use \pint\Exception,
    \pint\Socket\ChildSocket;

class Socket
{
    /**
     *
     * @var resource#id
     */
    protected $resource = null;
    
    /**
     *
     * @param int $domain 
     * @param int $type 
     * @param int $protocol 
     * @return void
     */
    public static function create($listen)
    {
        $resource = \stream_socket_server($listen, $a, $b);
        return new static($resource);
    }
    
    /**
     * 
     * Creates a new pint\Socket instance with an already intialized socket
     *
     * @param resource $socket 
     * @return void
     */
    public static function fromResource($resource)
    {
        return new static($resource);
        
    }
    
    /**
     *
     * @param resource $resource 
     * @return void
     */
    public function __construct($resource)
    {
        if(!\is_resource($resource)) {
            throw new Exception('$resource is no valid socket!');
        }
        
        $this->resource = $resource;        
    }
    
    /**
     *
     * @return void
     */
    public function accept()
    {
        $child = @\stream_socket_accept($this->resource, -1);
        
        if(is_resource($child)) {
            $child = ChildSocket::fromResource($child);
            
            return $child;
        }
        
        return false;
    }

    /**
     *
     * @return boolean
     */
    public function available()
    {
        return @\stream_select($r = array($this->resource), $w = null, $x = null, 1);
    }
    
    /**
     *
     * @return boolean
     */
    public function nonblock($block = 0)
    {
        return \stream_set_blocking($this->resource, $block);
    }
    
    /**
     *
     * @return boolean
     */
    public function close()
    {
        return \fclose($this->resource);
    }
    
    /**
     *
     * @return boolean
     */
    public function isClosed()
    {
        return !\is_resource($this->resource);
    }
    
    /**
     *
     * @return resource
     */
    public function resource()
    {
        return $this->resource;
    }
}