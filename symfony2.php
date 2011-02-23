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
        
        
        $app = new \example\Symfony2App;
        $app->bootstrap(__DIR__ . '/../pint-symfony2/app/bootstrap.php');
        $app->kernel('AppKernel', __DIR__ . '/../pint-symfony2/app/AppKernel.php');
        $app->stage('dev', true);
        $app->prepare();
        

        // both accept a string, object or closure
        $server->stack()->middleware("pint\Middleware\Logging");
        $server->stack()->middleware("pint\Middleware\FileInterceptor", array(
            'dir' => __DIR__ . '/../pint-symfony2/web' 
        ));
        $server->stack()->app($app);
    },
    "before_fork" => function($server) {
        echo "[master] Forking workers\n";
    },
    "after_fork" => function($server, $worker) {
        echo "[pid=" . $worker->pid() . "] Forked worker\n";
    }
);
