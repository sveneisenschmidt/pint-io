<?php

namespace pint;

use \pint\Exception;

class Socket
{
    /**
     *
     * @var resource#id
     */
    protected $resource = null;
    
    /**
     *
     * @var int
     */
    protected $timeout = -1;
    
    /**
     *
     * @param int $domain 
     * @param int $type 
     * @param int $protocol 
     * @return void
     */
    public static function create($listen)
    {
        $socket = \stream_socket_server($listen, $a, $b);
        return new static($socket);
    }
    
    /**
     * 
     * Creates a new pint\Socket instance with an already intialized socket
     *
     * @param resource $socket 
     * @return void
     */
    public static function fromSocket($socket)
    {
        return new static($socket);
        
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
            $child = ChildSocket::fromSocket($child);
            
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
     * @return int
     */
    public function getLastErrorCode()
    {
        return $this->errorno;
    }

    /**
     *
     * @return string
     */
    public function getLastErrorMessege()
    {
        return $this->errorstr;
    }

    /**
     *
     * @return void
     */
    public function clearError()
    {
        $this->errorstr = null;
        $this->errorno  = null;
    }
    
    /**
     *
     * @return resource
     */
    public function resource()
    {
        return $this->resource;
    }
    
    /**
     *
     * @return resource
     */
    public function meta()
    {
        return \stream_get_meta_data($this->resource);
    }
}


class ChildSocket extends Socket
{
    
    /**
     *
     * @return string
     */
    public function receive($bytes)
    {
        return \stream_socket_recvfrom($this->resource, $bytes);
    }
    
    /**
     *
     * @return string
     */
    public function fread($bytes)
    {
        stream_set_timeout($this->resource, 1);
        return \fread($this->resource, $bytes);
    }
    
    /**
     *
     * @return boolean
     */
    public function write($buffer, $length)
    {
        return \fwrite($this->resource, $buffer, $length);
    }
}






































class Socket_OLD
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
    public static function create($domain, $type, $protocol)
    {
        $socket = \socket_create($domain, $type, $protocol);
        return new self($socket);
    }
    
    /**
     * 
     * Creates a new pint\Socket instance with an already intialized socket
     *
     * @param resource $socket 
     * @return void
     */
    public static function fromSocket($socket, array $options = array())
    {
        return new self($socket, $options);
        
    }
    
    /**
     *
     * @param resource $resource 
     * @return void
     */
    public function __construct($resource, array $options = array())
    {
        if(!\is_resource($resource)) {
            throw new Exception('$resource is no valid socket!');
        }
        
        $this->resource = $resource;
        $this->options($options);
    }
    
    /**
     *
     * @return resource
     */
    public function resource()
    {
        return $this->resource;
    }
    
    /**
     *
     * @param int $level
     * @param int $optname
     * @param mixed $optval
     * @return boolean
     */
    public function option($level , $optname , $optval)
    {
        return socket_set_option($this->resource, $level, $optname, $optval);
    }
    
    /**
     *
     * @param array $options
     * @return array An array which options performed successfull and which not
     */
    public function options(array $options)
    {
        $states = array();
        foreach($options as $key => $option) {
            if(!is_array($option)) {
                throw new Exception("$key. option is now array!");
            }
            
            if(count($option) != 3) {
                throw new Exception("$key. option array is have less or more than 3 values");
            } 
            
            $states[$key] = \call_user_func_array(array($this, 'option'), $option);
        }
        
        return $states;
    }
    
    /**
     *
     * @param int|string $length
     * @param int $type 
     * @return false|string
     */
    public function read($length, $type = PHP_BINARY_READ)
    {
        if(!\is_numeric($length)) {
            throw new Exception('$length is no integer or numeric string!');
        }
        
        if(!\in_array($type, array(PHP_BINARY_READ /*, PHP_NORMAL_READ */))) {
            throw new Exception('$type is not valid, either PHP_BINARY_READ or PHP_NORMAL_READ is allowed!');
        }
        
        return \socket_read($this->resource, $length, $type);
    }
    
    /**
     *
     * @param int|string $length
     * @return false|string
     */
    public function receive($length, $flags = MSG_WAITALL) 
    {
        if(!\is_numeric($length)) {
            throw new Exception('$length is no integer or numeric string!');
        }
        
        @\socket_recv($this->resource, $buffer, $length, $flags);
        return $buffer;
    }

    /**
     *
     * @param string $buffer
     * @param string $bytes 
     * @return int
     */
    public function write($buffer, $length = null)
    {
        if(!\is_numeric($length)) {
            throw new Exception('$bytes is no integer or numeric string!');
        }
        
        return \socket_write($this->resource, $buffer, $length);
    }
    
    
    /**
     * Binds the socket to a specific host and port
     * 
     * @param string $host
     * @param string|int $port 
     * @return boolean
     */
    public function bind($host = null, $port = null)
    {
        return \socket_bind($this->resource, $host, $port);
    }

    /**
     *
     * @return void
     */
    public function close()
    {
        \socket_close($this->resource);
    }

    /**
     *
     * @return boolean
     */
    public function nonblock()
    {
        return \socket_set_nonblock($this->resource);
    }

    /**
     *
     * @return boolean
     */
    public function listen()
    {
        return \socket_listen($this->resource);
    }

    /**
     *
     * @return boolean
     */
    public function available()
    {
        return @socket_select($r = array($this->resource), $w = null, $x = null, 1);        
    }

    /**
     *
     * @return resource|integer
     */
    public function accept()
    {
        $resource = @socket_accept($this->resource);
        if(!is_resource($resource)) {
            return false;
        }
        
        return self::fromSocket($resource ); 
    }
    
}
