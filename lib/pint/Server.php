<?php

namespace pint;

use \pint\Socket,
    \pint\Stack,
    \pint\Worker;

/**
 * Central Server Manager (Master)
 *
 * Boots application, forks workers, kills stale workers, etc.
 */
class Server
{
    /**
     * Configuration defaults
     * 
     * @var array
     */
    protected $configDefaults = array(
        "pid_file" => "tmp/pint.pid",
        "listen" => "tcp://127.0.0.1:3000",
        "socket_file" => "tmp/pint.sock",
        "fork" => false,
        "workers" => 4,
        "max_requests" => -1,
        "timeout" => 30,
        "boot" => null,
        "before_fork" => null,
        "after_fork" => null
    );

    /**
     * Runtime configuration
     *
     * @var array
     */
    protected $config;

    /**
     * Runtime environment (e.g. development, test, or production)
     * 
     * @var string
     */
    protected $env;

    /**
     * Master process' PID
     *
     * @var int
     */
    protected $pid;

    /**
     * Shared listening socket
     *
     * @var \pint\Socket
     */
    protected $socket;

    /**
     * Worker objects
     *
     * @var \pint\Worker
     */
    protected $workers = array();

    /**
     * Shutdown flag
     *
     * @var bool
     */
    protected $shuttingDown = false;

    /**
     * Middleware and App stack
     *
     * @var \pint\Stack
     */
    protected $stack;

    /**
     * Constructor
     *
     * @param array $config
     */
    function __construct(array $config = array())
    {
        $this->configDefaults["boot"] = function($server) {};
        $this->configDefaults["before_fork"] = function($server) {};
        $this->configDefaults["after_fork"] = function($server, $worker) {};
        $this->config($config, true);

        $this->stack = new Stack();
    }

    /**
     * Getter/setter for runtime configuration
     *
     * @param array $config
     * @param bool $reset
     * @return array
     */
    function config(array $config = array(), $reset = false)
    {
        if (!empty($config))
        {
            $this->config = \array_merge(
                    $reset ? $this->configDefaults : $this->config, $config);
        }

        if (\is_callable($this->config["fork"])) {
            $this->config["fork"] = $this->config["fork"]($this);
        }

        return $this->config;
    }

    /**
     * Returns the runtime environment
     *
     * @return string
     */
    function env()
    {
        return $this->env;
    }

    /**
     * Returns the master process' PID
     *
     * @return int
     */
    function pid()
    {
        return $this->pid;
    }

    /**
     * Boots the server and starts listening
     *
     * @param string $env
     * @return null
     */
    function start($env)
    {
        $this->env = (string)$env;
        $this->pid = \posix_getpid();

        // flush output instantly after echo/print was called
        \ob_implicit_flush();

        // write PID file
        if (\file_exists($this->config["pid_file"])) {
            throw new Exception($this->config["pid_file"] . " already exists.");
        }
        \file_put_contents($this->config["pid_file"], $this->pid());
        
        \register_shutdown_function(array('\pint\Server', 'cleanup'), $this, array(
            'tmp'
        ));

        $this->config["boot"]($this);  // boot application
        list($host, $port) = \explode(":", $this->config["listen"]);

        
        $this->socket = Socket::create($this->config["listen"]);
        $this->socket->nonblock();
        
        echo "Listening on http://" . $this->config["listen"] . "\n";



        $this->config["before_fork"]($this);
        $this->forkWorkers();

        // register CTRL+C and SIGTERM
        $t = $this;
        $callback = function($signo) use ($t) {
            $t->shutdown();
        };
        \pcntl_signal(SIGTERM, $callback);
        \pcntl_signal(SIGINT, $callback);

        $this->loop();
        die();
    }

    /**
     * Main loop
     *
     * Maintains workers or serves requests, depending on fork config
     *
     * @return void
     */
    function loop()
    {
        while (!$this->shuttingDown())
        {
            if ($this->config["fork"])
            {
                usleep(100000);
                $this->maintainWorkers();
            }
            else
            {
                $this->workers[0]->serve();
            }

            // look for STRG+C
            pcntl_signal_dispatch();
        }

        // main loop finished, start shutting down


        if ($this->config["fork"])
        {
            echo "Shutting down workers\n";

            foreach ($this->workers as $worker)
            {
                // tell the workers to die
                $worker->shutdown();
            }

            $this->maintainWorkers(false);
        }

        // clean up
        $this->socket->close();
        @unlink($this->config["pid_file"]);

        echo "Good day to you.\n";
    }

    /**
     * Builds workers
     *
     * Initializes workers, amount depends on the value of the <tt>workers</tt>
     * setting. If <tt>fork</tt> is disabled builds one worker and doesn't tell
     * it to fork.
     *
     * @return void
     */
    function forkWorkers()
    {
        $count = $this->config["fork"] ? $this->config["workers"] : 1;
        for ($i = count($this->workers); $i < $count; $i++)
        {
            $worker = new Worker($this, $this->socket);
            $this->workers []= $worker;
            if ($this->config["fork"]) {
                $worker->fork();
                $this->config["after_fork"]($this, $worker);
            }
        }
    }

    /**
     * Replaces stale workers
     *
     * @return void
     *
     * @todo the pcntl_wait() stuff is complete bollocks
     */
    function maintainWorkers($forkNew = true)
    {
        foreach ($this->workers as $worker)
        {
            if ($worker->responsive() === false)
            {
                $worker->kill();
            }
        }

        // can SIGKILL fail?
        // shouldn't we wait() for each worker to confirm being KILLed?
        // how long does it take wait() to timeout?
        // retry after timeout? exit the master?
        pcntl_wait($status, WNOHANG);
        foreach ($this->workers as $i => $worker)
        {
            if (!$worker->alive())
            {
                unset($this->workers[$i]);
            }
        }

        // reindex workers
        $this->workers = array_values($this->workers);

        if ($forkNew) {
            // fork new workers
            $this->forkWorkers();
        }
    }

    /**
     * Indicates if the main loop should shut down
     *
     * @return bool
     */
    function shuttingDown()
    {
        return $this->shuttingDown;
    }

    /**
     * Tells the main loop to exit after its current cycle
     *
     * @return void
     */
    function shutdown()
    {
        $this->shuttingDown = true;
    }

    /**
     * Returns the middleware/app stack
     *
     * @return \pint\Stack
     */
    function stack()
    {
        return $this->stack;
    }

    /**
     * Builds a new instance from a config file
     *
     * @param string $file
     * @return \pint\Server
     * @throws \pint\Exception
     */
    public static function fromAppFile($file)
    {
        $config = require $file;
        if (!is_array($config))
        {
            throw new Exception("Could not find a config in $file");
        }
        return new self($config);
    }

    /**
     * Cleans everything up, even if the server dies
     *
     * @param \pint\Server $server
     * @param array $dirs
     * @return void
     */
    public static function cleanup(\pint\Server $server, array $dirs = array())
    {
        foreach($dirs as $path) {
            $pattern = $path . \DIRECTORY_SEPARATOR .'*';
            foreach(\glob($pattern) as $file) {
                @unlink($file);
            }
        }
    }
}
