<?php

namespace rubidium;

use \rubidium\Socket;

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
     * @var \rubidium\Socket
     */
    protected $socket;

    /**
     * Worker objects
     *
     * @var \rubidium\Worker
     */
    protected $workers = array();

    /**
     * Shutdown flag
     *
     * @var bool
     */
    protected $shuttingDown = false;

    /**
     * Middleware stack
     *
     * @var array
     */
    protected $middleware = array();

    /**
     *
     * @var mixed
     */
    protected $app;

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
        
        \register_shutdown_function(array('\rubidium\Server', 'cleanup'), $this, array(
            'tmp'
        ));

        $this->config["boot"]($this);  // boot application
        list($host, $port) = \explode(":", $this->config["listen"]);

        
        $this->socket = Socket::create(AF_INET, SOCK_STREAM, getprotobyname("tcp"));
        $this->socket->options(array(
            array(SOL_SOCKET, SO_REUSEADDR, 1),
            array(SOL_SOCKET, SO_RCVTIMEO, array("sec" => 0, "usec" => 250)), // cancel reads and writes after 250 microseconds
            array(SOL_SOCKET, SO_SNDTIMEO, array("sec" => 0, "usec" => 250))
        ));
        $this->socket->bind($host, $port);
        $this->socket->nonblock(); // enable non-blocking mode. socket_accept() returns immediately
        
        echo "Listening on http://" . $this->config["listen"] . "\n";
        $this->socket->listen();


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

            while (count($this->workers) > 0)
            {
                foreach ($this->workers as $i => $worker)
                {
                    if (!$worker->alive())
                    {
                        // worker died, delete its ping file
                        unset($this->workers[$i]);
                    }
                    else
                    {
                        // the worker will be KILLed by the kernel if we immediately exit.
                        // we are graceful and let it finish its request
                        pcntl_waitpid($worker->pid(), $status, WNOHANG);
                    }
                }
            }
        }

        // clean up
        $this->socket->close();
        unlink($this->config["pid_file"]);

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
            $worker = new Worker($this, $this->socket->resource());
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
    function maintainWorkers()
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

        // fork new workers
        $this->forkWorkers();
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

    function middleware($mw = null, array $options = array())
    {
        if (!is_null($mw))
        {
            $this->middleware []= array($mw, $options);
        }

        return $this->middleware;
    }

    function app($app = null)
    {
        if (!is_null($app))
        {
            $this->app = $app;
        }

        return $this->app;
    }

    function stack()
    {
        if (!$this->stack)
        {
            $app = $this->buildCallable($this->app);
            foreach ($this->middleware() as $mw)
            {
                $outer = $this->buildCallable($mw[0], $mw[1]);
                $outer->app($app);
                $app = $outer;
            }

            $this->stack = $app;
        }

        return $this->stack;
    }

    function buildCallable($src, $options = null)
    {
        $obj = is_object($src) ? $src : new $src($options);
        if (!method_exists($obj, "call"))
        {
            throw new Exception(get_class($obj) . " does not have a call() method.");
        }

        return $obj;
    }

    /**
     * Builds a new instance from a config file
     *
     * @param string $file
     * @return \rubidium\Server
     * @throws \rubidium\Exception
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
     * @param string $file
     * @return \rubidium\Server
     * @throws \rubidium\Exception
     */
    public static function cleanup(\rubidium\Server $server, array $dirs = array())
    {
        foreach($dirs as $path) {
            $pattern = $path . \DIRECTORY_SEPARATOR .'*';
            foreach(\glob($pattern) as $file) {
                unlink($file);
            }
        }
    }
}
