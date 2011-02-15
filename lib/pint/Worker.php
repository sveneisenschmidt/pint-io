<?php

namespace pint;

use \pint\Connection,
    \pint\Server,
    \pint\Socket;

/**
 * Request Processor (Worker)
 *
 * Tries to get requests from the shared socket, parses them, passes them to the
 * app and sends the response.
 */
class Worker
{
    /**
     * Master
     *
     * @var \pint\Server
     */
    protected $server;

    /**
     * Shared socket
     *
     * @var resource
     */
    protected $socket;

    /**
     * Indicates if we're inside the worker (true) or inside the master (false)
     *
     * @var bool
     */
    protected $forked;

    /**
     * Worker's PID
     *
     * @var int
     */
    protected $pid;

    /**
     * Shutdown flag
     *
     * @var bool
     */
    protected $shuttingDown = false;

    /**
     * Number of requests this worker has served
     *
     * Only gets incremented if we're inside the worker.
     *
     * @var int
     */
    protected $requests = 0;

    /**
     * Constructor
     *
     * @param \pint\Server $server
     * @param \pint\Socket $socket
     * @return void
     */
    function __construct(\pint\Server $server, \pint\Socket $socket)
    {
        $this->server = $server;
        $this->socket = $socket;
    }

    /**
     * Returns the master object
     *
     * @return \pint\Server
     */
    function server()
    {
        return $this->server;
    }

    /**
     * Indicates if we're inside the worker
     *
     * @return bool
     */
    function forked()
    {
        return $this->forked;
    }

    /**
     * Returns the worker's PID
     *
     * @return int
     */
    function pid()
    {
        return $this->pid;
    }

    /**
     * Forks the worker
     *
     * Afterwards <tt>forked()</tt> will indicate if we're inside the worker.
     *
     * @return void
     * @throws \pint\Exception
     */
    function fork()
    {
        $pid = pcntl_fork();
        if ($pid === false)
        {
            // this can happen on windows, e.g.
            throw new Exception("pint was unable to fork.");
        }

        $this->forked = $pid == 0;
        if ($this->forked)
        {
            // we're inside the worker
            $this->pid = posix_getpid();

            // register CTRL+C and SIGTERM
            $t = $this;
            $callback = function($signo) use ($t) {
                $t->shutdown();
            };
            pcntl_signal(SIGTERM, $callback);
            pcntl_signal(SIGINT, $callback);

            // create the ping file
            $this->ping();

            // start the main loop
            $this->loop();

            // the main loop ended, die
            unlink($this->pingFile());
            die();
        } else {
            // we're inside the master
            $this->pid = $pid;
        }
    }

    /**
     * Returns the ping file's path
     *
     * @return string
     */
    function pingFile()
    {
        $config = $this->server->config();
        return $config["socket_file"] . "." . $this->pid();
    }

    /**
     * Refreshes the ping file
     *
     * @return void
     *
     * @todo use posix_mkfifo() to get rid of those ugly ping files
     */
    function ping()
    {
        touch($this->pingFile());
    }

    /**
     * Indicates whether the worker process is running
     *
     * @return bool
     */
    function alive()
    {
        return @pcntl_getpriority($this->pid()) !== false;
    }

    /**
     * Indicates whether the worker is responsive
     * 
     * Returns false if the worker has timed out, true otherweise. Returns
     * nothing if the worker is not yet ready to serve requests or if it
     * has been killed.
     *
     * @return void|bool
     */
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

    /**
     * Main loop
     *
     * @return void
     */
    function loop()
    {
        while (!$this->shuttingDown())
        {
            $this->serve();
            $this->ping();

            pcntl_signal_dispatch();
        }
    }

    /**
     * Tries to serve one request
     *
     * @return void
     */
    function serve()
    {
        $msg = "[pid=%s] socket_select error: [%s] %s \n";
        
        // see if a connection comes in
        if (!$c = @socket_select($r = array($this->socket->resource()), $w = null, $x = null, 1)) {
            if ($c === false) {
                $error = \socket_last_error();
                if (!in_array($error, array(0, 4))) {
                    \vprintf($msg, array($this->pid(), $error, \socket_strerror($error)));
                }
            }
            return;
        }

        // try to get it! go go go!
        $socket = @socket_accept($this->socket->resource());
        if (!$socket) {
            if (is_null($socket)) {
                $error = \socket_last_error();
                \vprintf($msg, array($this->pid(), $error, \socket_strerror($error)));
            }
            return;
        }
        
        $this->requests++;
        
        $con = new Connection($socket);
        $response = $this->server()->stack()->call($con->env());
        $con->write($response);

        // die if we reached the request limit
        $config = $this->server->config();
        if ($this->requests == $config["max_requests"])
        {
            $this->shutdown();
        }
    }

    /**
     * Indicates whether the worker shall shut down
     *
     * @return bool
     */
    function shuttingDown()
    {
        return $this->shuttingDown;
    }

    /**
     * Tells the worker to shutdown
     *
     * @return void
     */
    function shutdown()
    {
        $this->shuttingDown = true;
        if (!$this->forked)
        {
            posix_kill($this->pid(), SIGTERM);
        }
    }

    /**
     * Kills the worker
     *
     * @return void
     */
    function kill()
    {
        unlink($this->pingFile());
        posix_kill($this->pid(), SIGKILL);
    }
}
