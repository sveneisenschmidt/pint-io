<?php

namespace pint;

use \pint\Request,
    \pint\Socket,
    \pint\Exception;

class Connection
{
    /**
     *
     * @var \pint\Socket
     */
    protected $socket = null;
    
    /**
     * 
     * @param resource $socket
     * @return void
     */
    function __construct($socket)
    {
        if(\is_resource($socket)) {
            $socket = Socket::fromSocket($socket);
        }
        
        if(!\is_object($socket) && !($socket instanceof \pint\Socket)) {
            throw new Exception('$socket is no resource or instance of \pint\Socket!');
        }
        
        $this->socket = $socket;
        $this->socket->options(array(
            array(\SOL_SOCKET, \SO_RCVTIMEO, array("sec" => 0, "usec" => 250)),
            array(\SOL_SOCKET, \SO_SNDTIMEO, array("sec" => 0, "usec" => 250))
        ));
        
        $this->read();
        $this->request = Request::parse($this->input());
        
        if($this->request->haserror()) {
            $this->criticizeSyntax();
        }
    }

    /**
     * 
     * @return void
     */
    function criticizeSyntax()
    {
        $this->write(array(
            400,
            array("Content-Type" => "text/html"),
            array("400 " . $this->request->statusmsg(400))
        ));
    }

    /**
     * 
     * @return void
     */
    function read()
    {
        $this->input = "";
        while (substr($this->input, -4) !== "\r\n\r\n")
        {
            $chunk = $this->socket->receive(1024);
            if ($chunk === false) {
                break;
            }
            $this->input .= $chunk;
        }
    }

    /**
     *
     * 
     * @param array $response
     * @param array $start
     * @return void
     */
    function write(array $response, $start = null)
    {
        if ($this->socket->isClosed()) {
            // throw new Exception("Connection->write() failed because its socket is already closed.");
            return;
        }

        // stringify body and set Content-Length
        if (\is_array($response[2])) {
            $response[2] = implode("\n", $response[2]);
        } else {
            $response[2] = (string)$response[2];
        }
        
        $response[1] = array_merge($response[1], array(
            'Content-Length' => \strlen($response[2]),
            'Connection'     => 'close' // keep-alive connections can be the knife in our back
        ));

        // response line
        $buffer = \vsprintf("HTTP/1.1 %s %s\r\n", array($response[0], $this->request->statusmsg($response[0])));
        
        \array_walk($response[1], function($value, $key) use ($buffer) {
            $buffer .= \vsprintf("%s: %s\r\n", array($key, $value));
        });
        
        $buffer .= \vsprintf("\r\n%s", array($response[2]));
        $bytes   = \strlen($buffer);
        $written = 0;
        
        while ($written < $bytes)
        {
            $x = $this->socket->write($buffer, $bytes);
            if (!is_int($x)) {
                echo "[{$this->pid()}] write error: {$this->socket->getLastErrorMessege()}\n";
                break;
            } else {
                $written += $x;
            }
        }

        $this->socket->option(\SOL_SOCKET, \SO_LINGER, array("l_onoff" => 1, "l_linger" => 1));
        $this->socket->close();
    }

    /**
     *
     * @return string
     */
    public function input()
    {
        return trim($this->input);
    }

    /**
     *
     * @return int
     */
    function pid()
    {
        return \posix_getpid();
    }

    /**
     *
     * @return array
     */
    function env()
    {
        $env = array(
            "REQUEST_METHOD" => $this->request->method(),
            "REQUEST_URI" => $this->request->uri(),
            "SERVER_SOFTWARE" => "pint/0.0.0",
            "SERVER_PROTOCOL" => "HTTP/1.1",
            "SERVER_NAME" => "pint.io",
            "SERVER_PORT" => "3000"
        );
        foreach ($this->request->headers() as $key => $value)
        {
            $key = \preg_replace("#[^a-z]+#i", "_", $key);
            $env["HTTP_" . \strtoupper($key)] = $value;
        }
        return \array_merge($env, array(
            "HTTP_VERSION" => "HTTP/" . $this->request->version()
        ));
    }
}
