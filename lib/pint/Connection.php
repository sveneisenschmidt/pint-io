<?php

namespace pint;

use \pint\Socket,
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
     * @var array
     */
    protected $headers  = array();
    
    /**
     *
     * @var string
     */
    protected $method = null;
    
    /**
     *
     * @var string
     */
    protected $uri = null;
    
    /**
     *
     * @var string
     */
    protected $version = null;
    
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
     * @param resource $socket
     * @return void
     */
    function __construct($socket)
    {
        if(\is_resource($socket)) {
            $this->socket = Socket::fromSocket($socket);
        } else
        if(!(\is_object($socket) && $socket instanceof \rubidium\Socket)) {
            throw new Exception('$socket is no resource or instance of \rubidium\Socket!');
        }
        
        $this->socket->options(array(
            array(\SOL_SOCKET, \SO_RCVTIMEO, array("sec" => 0, "usec" => 250)),
            array(\SOL_SOCKET, \SO_SNDTIMEO, array("sec" => 0, "usec" => 250))
        ));
        
        $this->read();
        $this->parse();
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
            array("400 " . $this->status[400])
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
            $chunk = $this->socket->read(1024);
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
            throw new Exception("Connection->write() failed because its socket is already closed.");
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
        $buffer = \vsprintf("HTTP/1.1 %s %s \r\n", array($response[0], $this->status[$response[0]]));
        
        \array_walk($response[1], function($value, $key) use ($buffer) {
            $buffer .= \vsprintf("%s: %s \r\n", array($key, $value));
        });
        
        $buffer .= \vsprintf("\r\n%s", array($response[2]));
        $bytes   = \strlen($buffer);
        $written = 0;
        
        while ($written < $bytes)
        {
            $x = $this->socket->write($buffer, $bytes);
            if (!is_int($x)) {
                echo "[{$this->pid()}] write error: {$this->socket->getLastErrorMessege()} \n";
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
    function method()
    {
        return $this->method;
    }

    /**
     *
     * @return string
     */
    function uri()
    {
        return $this->uri;
    }

    /**
     *
     * @return string
     */
    function version()
    {
        return $this->version;
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
    function headers()
    {
        return $this->headers;
    }

    /**
     *
     * @return array
     */
    function env()
    {
        $env = array(
            "REQUEST_METHOD" => $this->method(),
            "REQUEST_URI" => $this->uri(),
            "SERVER_SOFTWARE" => "pint/0.0.0",
            "SERVER_PROTOCOL" => "HTTP/1.1",
            "SERVER_NAME" => "pint.io",
            "SERVER_PORT" => "3000"
        );
        foreach ($this->headers() as $key => $value)
        {
            $key = \preg_replace("#[^a-z]+#i", "_", $key);
            $env["HTTP_" . \strtoupper($key)] = $value;
        }
        return \array_merge($env, array(
            "HTTP_VERSION" => "HTTP/" . $this->version()
        ));
    }
    
    /*
    Array
    (
        [Request Method] => GET
        [Request Url] => /
        [Host] => localhost:3000
        [User-Agent] => Mozilla/5.0 (X11; Linux i686; rv:2.0b11) Gecko/20100101 Firefox/4.0b11
        [Accept] => text/html,application/xhtml+xml,application/xml;q=0.9,* / *;q=0.8
        [Accept-Language] => de-de,de;q=0.8,en-us;q=0.5,en;q=0.3
        [Accept-Encoding] => gzip, deflate
        [Accept-Charset] => ISO-8859-1,utf-8;q=0.7,*;q=0.7
        [Keep-Alive] => 115
        [Connection] => keep-alive
        [Cache-Control] => max-age=0
    )
     */
    
    /**
     * 
     * @return void
     */
    function parse()
    {
        $raw   = \http_parse_headers($this->input);
        $lines = \explode("\r\n", \trim($this->input));
        
        if(!\array_key_exists('Request Method', $raw) ||
           !\array_key_exists('Request Url', $raw)    
        ) {
            return $this->criticizeSyntax();
        }
        
        $this->headers = $raw;
        $this->method  = trim($raw['Request Method']);
        $this->uri     = trim($raw['Request Url']);        
        
        \preg_match("#^(?P<method>GET|HEAD|POST|PUT|OPTIONS|DELETE)\s+(?P<uri>[^\s]+)\s+HTTP/(?P<version>1\.\d)$#U", trim($lines[0]), $matches);
        if(!\array_key_exists('version', $matches)) {
            return $this->criticizeSyntax();
        } else {
            $this->version = $matches['version'];
            unset($matches);
        }

        
        // content type is not allowed when performing get requests
        if(array_key_exists('Content-Type', $raw) &&
           !in_array($this->method, array('POST', 'PUT'))
        ) {
            return $this->criticizeSyntax();
        } 
        
        
        // application/x-www-url-encoded; charset=UTF-8
//        \preg_match('/^(?P<ctype>.*?);[\s]charset=(?P<charset>.+)/i', trim($raw['Content-Type']), $matches);
//        $ctype  = $matches['ctype'];
//        $chrset = $matches['charset'];
//        
//        if(!\in_array($ctype, 'text/plain', 'application/x-www-url-encoded', 'formdata/multipart')) {
//            return $this->criticizeSyntax();
//        }
        
        
        
        
        unset($raw, $lines, $matches);
        
    }
}
