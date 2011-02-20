<?php

namespace pint\Socket;

use \pint\Exception,
    \pint\Socket;

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