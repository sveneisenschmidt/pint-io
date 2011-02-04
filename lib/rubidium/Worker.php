<?php

namespace rubidium;

class Worker
{
    protected $server, $socket, $forked = false, $pid, $shuttingDown = false;

    function __construct($server, $socket)
    {
        $this->server = $server;
        $this->socket = $socket;
    }

    function server()
    {
        return $this->server;
    }

    function forked()
    {
        return $this->forked;
    }

    function pid()
    {
        return $this->pid;
    }

    function fork()
    {
        $pid = pcntl_fork();
        $this->forked = $pid == 0;
        if ($pid === false)
        {
            throw new Exception("rubidium was unable to fork.");
        }

        $this->pid = $pid;

        if ($this->forked)
        {
            $this->ping();
            $this->loop();
            die();
        }
    }

    function ping()
    {
        $config = $this->server->config();
        touch($config["socket_file"] . "." . $this->pid());
    }

    function responsive()
    {
        $config = $this->server->config();
        
        clearstatcache();
        $mtime = filemtime($config["socket_file"] . "." . $this->pid());
        return $mtime > (time() > $config["timeout"]);
    }

    function loop()
    {
        while (!$this->shuttingDown())
        {
            $this->serve();
        }
    }

    function serve()
    {
        if (socket_select($this->socket, 10) && $fd = socket_accept($this->socket))
        {
            $this->ping();

            $c = new Connection($fd);
            $response = $this->server()->app()->call($c->env());
            $c->write($response);

            $this->ping();
        }
    }

    function shuttingDown()
    {
        return $this->shuttingDown;
    }

    function kill()
    {
        
    }
}
