<?php

namespace rubidium;

class Worker
{
    protected $server, $socket, $forked = false, $pid, $shuttingDown = false;
    protected $requests = 0;

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

            $t = $this;
            $callback = function($signo) use ($t) {
                $t->shutdown();
            };
            pcntl_signal(SIGTERM, $callback);
            pcntl_signal(SIGINT, $callback);

            $this->ping();
            $this->loop();
            
            unlink($this->pingFile());
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
        return @pcntl_getpriority($this->pid()) !== false;
    }

    function responsive()
    {
        if (!file_exists($this->pingFile()))
        {
            return null;
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
            $this->ping();
            $this->serve();
            $this->ping();

            pcntl_signal_dispatch();
        }
    }

    function serve()
    {
        $socket = @socket_accept($this->socket);
        if (!$socket)
        {
//            echo socket_strerror(socket_last_error($this->socket)) . "\n";
//            echo "No Connection...\n";
            usleep(250);
            return;
        }
        
        $this->requests++;

//        echo "Connection!\n";

        $conn = new Connection($socket);
        $conn->write(array(
            200,
            array("Content-Type" => "text/plain", "Content-Length" => 12),
            "Hello World!"
        ));

        $config = $this->server->config();
        if ($this->requests == $config["max_requests"])
        {
            $this->shutdown();
        }
    }

    function shuttingDown()
    {
        return $this->shuttingDown;
    }

    function shutdown()
    {
        $this->shuttingDown = true;
        if (!$this->forked)
        {
            posix_kill($this->pid(), SIGTERM);
        }
    }

    function kill($force = false)
    {
        if ($this->shuttingDown() && !$force)
        {
            return; 
        }
        unlink($this->pingFile());
        posix_kill($this->pid(), SIGKILL);
    }
}
