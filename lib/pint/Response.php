<?php

namespace pint;

use \pint\Socket,
    \pint\Socket\ChildSocket,
    \pint\Request,
    \pint\Exception,
    \pint\Mixin\ContainerAbstract;

class Response extends ContainerAbstract
{
    /**
     *
     * @var string
     */
    protected $container = 'parts';
    
    /**
     *
     * @var array
     */
    protected $parts = array();
    
    /**
     *
     * @var array
     */
    public static $status = array(
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
     * @param string $body
     * @param int $status
     * @param array $headers
     * @return void
     */
    public function __construct($status = 200, array $headers = array(), $body = '')
    {
        $this->parts[0] = (int)$status;
        $this->parts[1] = $headers;
        $this->parts[2] = (array)$body;
    }
    
    /**
     *
     * @param array $parts
     * @return \pint\Respone
     */
    public static function fromArray(array $parts) 
    {
        $parts = array_values($parts);
        
        if(count($parts) === 3 || count($parts) > 3) {
            return new self($parts[0], $parts[1], $parts[2]);
        }
        if(count($parts) === 2) {
            return new self($parts[0], $parts[1]);
        }        
        if(count($parts) === 1) {
            return new self($parts[0]);
        }
        
        return new self();
    }
    
    /**
     *
     * @return array
     */
    public static function badRequest()
    {
        return self::fromArray(array(
            400,
            array("Content-Type" => "text/html"),
            array("400 " . self::$status[400])
        ));
    }    
    
    /**
     *
     * @return array
     */
    public static function internalServerError()
    {
        return self::fromArray(array(
            500,
            array("Content-Type" => "text/html"),
            array("500 " . self::$status[400])
        ));
    }      
    
    /**
     * @param \pint\Socket $socket
     * @param \pint\Response|array $response
     * @return array
     */
    public static function write(ChildSocket $socket, $response)
    {
        if ($socket->isClosed()) {
            // throw new Exception("Response::write() failed because its socket is already closed.");
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
            'Connection' => 'close' // keep-alive connections can be the knife in our back
        ));

        // response line
        $buffer = \vsprintf("HTTP/1.1 %s %s\r\n", array($response[0], self::$status[$response[0]]));
        
        foreach ($response[1] as $key => $value) {
            $buffer .= \vsprintf("%s: %s\r\n", array($key, $value));
        }
        
        $buffer .= \vsprintf("\r\n%s", array($response[2]));
        $bytes = \strlen($buffer);
        $written = 0;
        
        while ($written < $bytes)
        {
            $x = $socket->write($buffer, $bytes);
            if (!is_int($x)) {
                echo "[{$this->pid()}] write error!\n";
                break;
            } else {
                $written += $x;
            }
        }

        $socket->close();
    } 
}
