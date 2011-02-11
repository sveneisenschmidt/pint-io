<?php

namespace rubidium;

class Connection
{
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

    function __construct($socket)
    {
        $this->socket = $socket;
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 0, "usec" => 250));
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array("sec" => 0, "usec" => 250));
        
        $this->read();
//        $this->parse();

        return;
        var_dump($data);

        preg_match("#^(GET|HEAD|POST|PUT|OPTIONS|DELETE)\s+([^\s]+)\s+HTTP/1\.(0|1)$#U", $data[0], $matches);
        if (empty($matches))
        {
            $this->criticizeSyntax();
        }
        list($nothing, $this->method, $this->uri, $this->version) = $matches;

        $this->headers = array();
        foreach (explode("\r\n", $data[1]) as $header)
        {
            list($key, $value) = preg_split("#:\s*#", trim($header), 2);
            $this->headers[trim($key)] = trim($value);
        }
    }

    function criticizeSyntax()
    {
        $this->write(array(
            400,
            array("Content-Type" => "text/html"),
            array("400 " . $this->status[400])
        ));
    }

    function read()
    {
        $this->input = "";
        while ($chunk = socket_read($this->socket, 1024, PHP_BINARY_READ))
        {
            $this->input .= $chunk;
        }
        if ($chunk === false)
        {
//            echo "[" . posix_getpid() . "] read error: " . socket_strerror(socket_last_error($this->socket)) . "\n";
        }
    }

    function write(array $response, $start = null)
    {
        // response line
        $str = "HTTP/1.1 " . $response[0] . " " . $this->status[$response[0]] . "\r\n";

//        if (!is_null($start))
//        {
//            $response[2] = "server time: " . (microtime() - $start) . "\n" . $response[2];
//        }

        // headers
        foreach ($response[1] as $key => $value)
        {
            $str .= $key . ": " . $value . "\r\n";
        }
        $str .= "\r\n";

        // body
        if (is_string($response[2]))
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

        // null byte
//        $str .= chr(0);
        $str .= "\r\n";

        $bytes = strlen($str);
        $written = 0;
        while ($written < $bytes)
        {
            $x = socket_write($this->socket, $str, $bytes);
            if (!is_int($x))
            {
                echo "[" . posix_getpid() . "] write error: " . socket_strerror(socket_last_error($this->socket)) . "\n";
            }
            else
            {
                $written += $x;
            }
        }

        socket_set_option($this->socket, SOL_SOCKET, SO_LINGER, array("l_onoff" => 1, "l_linger" => 1));
        socket_close($this->socket);
    }

    function method()
    {
        return $this->method;
    }

    function uri()
    {
        return $this->uri;
    }

    function version()
    {
        return $this->version;
    }

    function headers()
    {
        return $this->headers;
    }

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
            $key = preg_replace("#[^a-z]+#i", "_", $key);
            $env["HTTP_" . strtoupper($key)] = $value;
        }
        return array_merge($env, array(
            "HTTP_VERSION" => "HTTP/1." . $this->version
        ));
    }

//    function readRequestLine()
//    {
//        $line = trim(socket_read($this->socket, 4096, PHP_NORMAL_READ));
//        preg_match("#^(GET|HEAD|POST|PUT|OPTIONS|DELETE)\s+([^\s]+)\s+HTTP/1\.(0|1)$#U", $line, $matches);
//        if (!count($matches))
//        {
//            return false;
//        }
//
//        return array(
//            $matches[1],
//            $matches[2],
//            "HTTP/1." . $matches[3]
//        );
//    }

//    function readHeaders()
//    {
//        $headers = array();
//        while ($line = socket_read($this->socket, 1024, PHP_NORMAL_READ))
//        {
//            var_dump($line);
//            if ($line == "\n" || $line == "\r")
//            {
//                continue;
//            }
//            elseif ($line == "\r\n")
//            {
//                break;
//            }
//            list($key, $value) = preg_split("#:\s*#", $line, 1);
//            $headers[$key] = $value;
//        }
//        return $headers;
//    }
//
//    function readLine()
//    {
//        return socket_read($this->socket, 1024, PHP_NORMAL_READ);
//    }
}
