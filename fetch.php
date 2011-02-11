<?php

define("NUM_REQUESTS", 1);
define("OUTPUT_DIR", realpath($_SERVER["argv"][1]));

$requests = array();
for ($i = 0; $i < NUM_REQUESTS; $i++)
{
    if (pcntl_fork() !== 0)
    {
        usleep(5000);
        break;
    }

    $start = microtime(true);
    $response = file_get_contents("http://localhost:3000/");
    $end = microtime(true);

    $out = "client time: " . ($end - $start) . "\n";
    $out .= $response;
    file_put_contents(OUTPUT_DIR . "/request." . posix_getpid(), $out);
}
