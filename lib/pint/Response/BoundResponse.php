<?php

namespace pint\Response;

use \pint\Socket\ChildSocket,
    \pint\Response;

class BoundResponse extends Response
{
    /**
     *
     * @var \pint\Socket\ChildSocket $socket
     */
    protected $socket = null;
    
    /**
     *
     * @param \pint\Socket\ChildSocket $socket
     */
    public function __construct(ChildSocket $socket)
    {
        $this->socket = $socket;
    }
    
    /**
     *
     * @param \pint\Socket\ChildSocket $socket
     * @return \pint\Response\BoundResponse
     */
    public function bind(ChildSocket $socket)
    {
        return new self($socket);        
    }
    
    /**
     * @param \pint\Response|array $response
     * @return void
     */
    public function flush($response, $close = true)
    {
        parent::write($this->socket, $response, $close);
    } 
}
