<?php

return array(
    "listen" => "127.0.0.1:3000",
    "socket_file" => "tmp/pint.sock",
    "pid_file" => "tmp/pint.pid",
    "fork" => false,
//    "fork" => function($server) {
//        echo "Shall I fork?\n";
//        return $server->env() != "development";
//    },
    "workers" => 1,
    "max_requests" => 1,
    "boot" => function($server) {
        $loader = new \SplClassLoader("example", "lib");
        $loader->register();

        // both accept a string, object or closure
        $server->middleware("pint\middleware\Logging");
        $server->app("example\App");
    },
    "before_fork" => function($server) {
        echo "[master] Forking workers\n";
    },
    "after_fork" => function($server, $worker) {
        echo "[pid=" . $worker->pid() . "] Forked worker\n";
    }
);
