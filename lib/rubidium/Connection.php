<?php

namespace rubidium;

class Connection
{
    
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
        $this->socket = $socket;
        \socket_set_option($this->socket, \SOL_SOCKET, \SO_RCVTIMEO, array("sec" => 0, "usec" => 250));
        \socket_set_option($this->socket, \SOL_SOCKET, \SO_SNDTIMEO, array("sec" => 0, "usec" => 250));
        
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
        while ($chunk = \socket_read($this->socket, 1024,\ PHP_BINARY_READ))
        {
            $this->input .= $chunk;
        }
        if ($chunk === false)
        {
//            echo "[" . posix_getpid() . "] read error: " . socket_strerror(socket_last_error($this->socket)) . "\n";
        }
    }

    /**
     *
     * 
     * @param array $response
     * @param array $start
     * @return string
     */
    function write(array $response, $start = null)
    {
        // response line
        $str = "HTTP/{$this->version} {$response[0]} {$this->status[$response[0]]} \r\n";
        
        // headers
        foreach ($response[1] as $key => $value)
        {
            $str .= $key . ": " . $value . "\r\n";
        }
        $str .= "\r\n";

        // body
        if (\is_string($response[2]))
        {
            $str .= $response[2] . "\r\n";
        }
        else
        {
            foreach ($response[2] as $line)
            {
                $str .= $line . "\r\n";
            }
        }

        $str .= "\r\n";

        $bytes = \strlen($str);
        $written = 0;
        while ($written < $bytes)
        {
            $x = \socket_write($this->socket, $str, $bytes);
            if (!is_int($x))
            {
                echo "[" . \posix_getpid() . "] write error: " . \socket_strerror(\socket_last_error($this->socket)) . "\n";
                // we should seriously consider placing a "break;" here
            }
            else
            {
                $written += $x;
            }
        }

        \socket_set_option($this->socket, \SOL_SOCKET, \SO_LINGER, array("l_onoff" => 1, "l_linger" => 1));
        \socket_close($this->socket);
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
            "SERVER_SOFTWARE" => "rubidium/0.0.0",
            "SERVER_PROTOCOL" => "HTTP/1.1",
            "SERVER_NAME" => "rubidium.org",
            "SERVER_PORT" => "3000"
        );
        foreach ($this->headers() as $key => $value)
        {
            $key = \preg_replace("#[^a-z]+#i", "_", $key);
            $env["HTTP_" . \strtoupper($key)] = $value;
        }
        return \array_merge($env, array(
            "HTTP_VERSION" => "HTTP/" . $this->version
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
        if(\array_key_exists('Content-Type', $raw) &&
           \in_array($this->method, array('POST', 'GET'))
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
