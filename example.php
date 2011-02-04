<?php

return array(
    "listen" => "127.0.0.1:3000",
    "socket_file" => "tmp/rubidium.sock",
    "pid_file" => "tmp/rubidium.pid",
    "fork" => true,
//    "fork" => function($server) {
//        return $server->env() != "development";
//    },
    "workers" => 4,
    "boot" => function($worker) {
        $loader = new SplClassLoader("example", "lib");
        $loader->register();

        $server->use("rubidium\Logging");
        $server->run("example\App");
    },
    "fork" => true,
    "before_fork" => function($worker) {
        echo "[master] Forking worker (pid=" . $worker->pid() . ")";
    },
    "after_fork" => function($worker) {
        echo "[pid=" . $worker->pid . "] Forked worker";
    }
);
