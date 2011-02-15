<?php

namespace pint;

class Response
{
    protected $status,
              $headers = array("Content-Type" => "text/html"),
              $body;

    function __construct($body = array(), $status = 200, array $headers = array())
    {
        $this->status($status);
        $this->headers($headers);
        $this->body($body);
    }

    function status($status = null)
    {
        if (!is_null($status)) {
            $this->status = $status;
        }

        return $this->status;
    }

    function headers(array $headers = array(), $reset = false)
    {
        if (!empty($headers)) {
            $this->headers = $reset ? $headers : array_merge($this->headers, $headers);
        }

        return $this->headers;
    }

    function body(array $body = null)
    {
        if (!is_null($body)) {
            $this->body = is_array($body) ? $body : array((string)$body);
        }

        return $this->body;
    }

    function write($line)
    {
        $this->body []= $line;
    }

    function finish()
    {
        return array($this->status, $this->headers, $this->body());
    }
}
