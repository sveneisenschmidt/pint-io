<?php

namespace rubidium;

class Server
{
    protected $config, $configDefaults = array(
        "pid_file" => "tmp/rubidium.pid",
        "listen" => "tcp://127.0.0.1:3000",
        "socket_file" => "tmp/rubidium.sock",
        "fork" => false,
        "workers" => 4,
        "max_requests" => -1,
        "timeout" => 30,
        "boot" => null,
        "before_fork" => null,
        "after_fork" => null
    );

    protected $env, $pid, $socket, $workers = array(), $shuttingDown = false;

    function __construct(array $config = array())
    {
        $this->config($config, true);
    }

    function config(array $config = array(), $reset = false)
    {
        if (!empty($config))
        {
            $this->config = array_merge(
                    $reset ? $this->configDefaults : $this->config, $config);
        }

        return $this->config;
    }

    function env()
    {
        return $this->env;
    }

    function pid()
    {
        return $this->pid;
    }

    function start($env)
    {
        $this->env = (string)$env;
        $this->pid = posix_getpid();

        echo "[master-" . $this->pid() . "] starting\n";

        if (file_exists($this->config["pid_file"])) {
            throw new Exception($this->config["pid_file"] . " already exists.");
        }
        file_put_contents($this->config["pid_file"], $this->pid());

        list($host, $port) = explode(":", $this->config["listen"]);
        $this->socket = socket_create(AF_INET, SOCK_STREAM, getprotobyname("tcp"));
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->socket, $host, $port);
        socket_set_nonblock($this->socket);
        socket_listen($this->socket);

        $this->forkWorkers();

        $t = $this;
        $callback = function($signo) use ($t) {
            echo "[master-" . $t->pid() . "] received SIGTERM\n";
            $t->shutdown();
        };
        pcntl_signal(SIGTERM, $callback);
        pcntl_signal(SIGINT, $callback);

        $this->loop();
        die();
    }

    function loop()
    {
        while (true)
        {
            if ($this->config["fork"])
            {
                usleep(1000);
                $this->maintainWorkers();
            }
            else
            {
                $this->workers[0]->serve();
            }

            pcntl_signal_dispatch();
            if ($this->shuttingDown())
            {
                break;
            }
        }

        echo "[master-" . $this->pid() . "] shutting down\n";

        if ($this->config["fork"])
        {
            foreach ($this->workers as $worker)
            {
                $worker->shutdown();
            }

//            while (count($this->workers) > 0)
//            {
//                foreach ($this->workers as $i => $worker)
//                {
//                    if (!$worker->alive())
//                    {
//                        echo "worker dead...\n";
//                        unset($this->workers[$i]);
//                    }
//                    else
//                    {
//                        echo "worker still alive...\n";
//                        pcntl_waitpid($worker->pid(), $status, WNOHANG);
//                    }
//                }
//            }
        }

        socket_close($this->socket);
        unlink($this->config["pid_file"]);

        echo "[master-" . $this->pid() . "] done, bye!\n";

        usleep(500000);
    }

    function forkWorkers()
    {
        $count = $this->config["fork"] ? $this->config["workers"] : 1;
        for ($i = count($this->workers); $i < $count; $i++)
        {
            $worker = new Worker($this, $this->socket);
            $this->workers []= $worker;
            if ($this->config["fork"]) {
                $worker->fork();
            }
        }
    }

    function maintainWorkers()
    {
//        if ($this->shuttingDown())
//        {
//            return;
//        }
        echo "[master-" . posix_getpid() . "] maintainWorkers() (shuttingDown=" . (int)$this->shuttingDown() . "\n";
        
        foreach ($this->workers as $worker)
        {
            if (!$worker->responsive())
            {
                $worker->kill();
            }
        }

        pcntl_wait($status, WNOHANG);
        foreach ($this->workers as $i => $worker)
        {
            if (!$worker->alive())
            {
                unset($this->workers[$i]);
            }
        }
        $this->workers = array_values($this->workers);

        $this->forkWorkers();
    }

    function shuttingDown()
    {
        return $this->shuttingDown;
    }

    function shutdown()
    {
        echo "[master-" . posix_getpid() . "] setting shuttingDown to true (shuttingDown=" . (int)$this->shuttingDown() . "\n";
        $this->shuttingDown = true;
        echo "[master-" . posix_getpid() . "] set shuttingDown to true (shuttingDown=" . (int)$this->shuttingDown() . "\n";
    }

    static function fromAppFile($file)
    {
        $config = require $file;
        if (!is_array($config))
        {
            throw new Exception("Could not find a config in $file");
        }
        return new self($config);
    }
}
