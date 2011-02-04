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
        "timeout" => 30,
        "boot" => null,
        "before_fork" => null,
        "after_fork" => null
    );

    protected $env, $socket, $workers = array(), $shuttingDown = false;

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

    function start($env)
    {
        $this->env = (string)$env;

//        $this->writePidFile();

        list($host, $port) = explode(":", $this->config["listen"]);
        $this->socket = socket_create(AF_INET, SOCK_STREAM, getprotobyname("tcp"));
        socket_bind($this->socket, $host, $port);

        $this->forkWorkers();
        $this->loop();
    }

    function loop()
    {
        while (!$this->shuttingDown())
        {
            if ($this->config["fork"])
            {
                $this->maintainWorkers();
                usleep(500000);
            }
            else
            {
                $this->workers[0]->serve();
            }
        }
    }

    function forkWorkers()
    {
        $count = $this->config["fork"] ? $this->config["workers"] : 1;
        for ($i = count($this->workers); $i < $count; $i++)
        {
            $this->workers []= new Worker($this, $this->socket);
        }

        foreach ($this->workers as $worker)
        {
            if ($this->config["fork"] && !$worker->forked())
            {
                $worker->fork();
            }
        }
    }

    function maintainWorkers()
    {
        foreach ($this->workers as $i => $worker)
        {
            if (!$worker->responsive())
                    {
                $worker->kill();
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

    static function fromAppFile($file)
    {
        $config = require $file;
        if (!is_array($config))
        {
            echo "Could not find a config in $file\n";
        }
        return new self($config);
    }
}
