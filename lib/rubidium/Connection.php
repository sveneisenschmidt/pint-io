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

        if (!$req = $this->readRequestLine())
        {
            $this->write(array(
                400,
                array("Content-Type" => "text/html"),
                array("400 " . $this->status[400])
            ));
            $this->close();
        }

        list($this->method, $this->uri, $this->version) = $this->readRequestLine();
        $headers = $this->readHeaders();
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
            "HTTP_VERSION" => $this->version
        ));
    }

    function readRequestLine()
    {
        $line = trim(socket_read($this->socket, 4096, PHP_NORMAL_READ));
        preg_match("#^(GET|HEAD|POST|PUT|OPTIONS|DELETE)\s+([^\s]+)\s+HTTP/1\.(0|1)$#U", $line, $matches);
        if (!count($matches))
        {
            return false;
        }

        return $matches[0];
    }

    function readHeaders()
    {
    }

    function readLine()
    {
        return socket_read($this->socket, 1024, PHP_NORMAL_READ);
    }

    function write(array $response)
    {
        // response line
        $str = "HTTP/1.1 " . $response[0] . " " . $this->status[$response[0]] . "\r\n";

        // headers
        foreach ($response[1] as $key => $value)
        {
            $str .= $key . ": " . $value;
        }
        $str .= "\r\n";

        // body
        if (is_string($response[2]))
        {
            $str .= $response[2];
        }
        else
        {
            foreach ($response[2] as $line)
            {
                $str .= $line . "\r\n";
            }
        }

        $bytes = strlen($str);
        $written = 0;
        while ($written < $bytes)
        {
            $written += socket_write($this->socket, $str, $bytes);
        }

        socket_close($this->socket);
    }
}
