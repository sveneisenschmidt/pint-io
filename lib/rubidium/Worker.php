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

            echo "[" . $this->pid() . "] forked (server->shuttingDown=" . (int)$this->server->shuttingDown() . ")\n";

            $t = $this;
            $callback = function($signo) use ($t) {
                echo "[" . $t->pid() . "] received SIGTERM\n";
                $t->shutdown();
            };
            pcntl_signal(SIGTERM, $callback);
            pcntl_signal(SIGINT, $callback);

            $this->ping();
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
        return @pcntl_getpriority($this->pid()) !== false;
    }

    function responsive()
    {
        if (!file_exists($this->pingFile()))
        {
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
            $this->ping();
            $this->serve();
            $this->ping();

            $config = $this->server->config();
            if ($this->requests == $config["max_requests"])
            {
                $this->shutdown();
            }
            else
            {
                pcntl_signal_dispatch();
            }
        }

        echo "[" . $this->pid() . "] shutting down\n";
        unlink($this->pingFile());
    }

    function serve()
    {
        $socket = @socket_accept($this->socket);
        if (!$socket)
        {
//            echo "No Connection...\n";
            usleep(1000);
            return;
        }

//        echo "Connection!\n";

        $conn = new Connection($socket);
        var_dump($conn->env());
        $conn->write(array(
            200,
            array("Content-Type" => "text/plain", "Content-Length" => 12),
            "Hello World!"
        ));
        $conn->close();

        $this->requests++;
    }

    function shuttingDown()
    {
        return $this->shuttingDown;
    }

    function shutdown()
    {
        if ($this->forked)
        {
            echo "[" . $this->pid() . "] starting to shutdown\n";
            $this->shuttingDown = true;
        }
        else
        {
            echo "[master-" . posix_getpid() . "] sending SIGTERM to " . $this->pid() . "\n";
            posix_kill($this->pid(), SIGTERM);
        }
    }

    function kill()
    {
        unlink($this->pingFile());
        posix_kill($this->pid(), SIGKILL);
    }
}
