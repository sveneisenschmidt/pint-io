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
        if ($pid === false)
        {
            throw new Exception("rubidium was unable to fork.");
        }

        $this->forked = $pid == 0;
        if ($this->forked)
        {
            $this->pid = posix_getpid();
            $this->loop();
            die();
        } else {
            $this->pid = $pid;
        }
    }

    function pingFile()
    {
        $config = $this->server->config();
        return $config["socket_file"] . "." . $this->pid();
    }

    // @todo Use posix_mkfifo()
    function ping()
    {
        touch($this->pingFile());
    }

    function alive()
    {
        echo "$this->pid - " . @print_r(pcntl_getpriority($this->pid()), true) . "\n";
        return @pcntl_getpriority($this->pid()) !== false;
    }

    function responsive()
    {
        if (!file_exists($this->pingFile())) {
            return false;
        }

        $config = $this->server->config();
        
        clearstatcache();
        $mtime = filemtime($this->pingFile());
        return $mtime > (time() - $config["timeout"]);
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
        $this->ping();
        echo "Worker->serve() - serving (pid=$this->pid)\n";
        usleep(1000000);
        return;

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
        posix_kill($this->pid(), SIGKILL);
        unlink($this->pingFile());
    }
}
