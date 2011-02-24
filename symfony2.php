<?php

return array(
    "listen" => "127.0.0.1:3000",
    "socket_file" => "tmp/pint.sock",
    "pid_file" => "tmp/pint.pid",
    "fork" => true,
//    "fork" => function($server) {
//        echo "Shall I fork?\n";
//        return $server->env() != "development";
//    },
    "workers" => 1,
    "max_requests" => 0,
    "boot" => function($server) {
        $loader = new \SplClassLoader("example", "lib");
        $loader->register();
        
        // both accept a string, object or closure
        $server->stack()->push("pint\Middleware\Logging");
        $server->stack()->push("pint\Middleware\FileInterceptor", array(
            'dir'  => __DIR__ . '/../pint-symfony2/web',
            'skip' => 1
        ));
        $server->stack()->push('example\Symfony2App');
            
        $server->stack()->push("pint\Middleware\Compressor", array(
            'threshold' => 512, // compress only if the content is bigger than 2048 bytes    
            'level'     => 6,   // compression level -1 - 9,
            // 'support'   => array('gzip', 'deflate')
        ));
    },
    "before_fork" => function($server) {
        echo "[master] Forking workers\n";
    },
    "after_fork" => function($server, $worker) {
        echo "[pid=" . $worker->pid() . "] Forked worker\n";
    }
);
