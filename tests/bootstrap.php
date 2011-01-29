<?php

$root = dirname(dirname(__FILE__));
set_include_path($root . "/lib" .
        PATH_SEPARATOR . get_include_path());

function __autoload($class) {
    $class = str_replace("\\", "/", $class);
    foreach (explode(":", get_include_path()) as $path) {
        $file = $path . "/" . $class . ".php";
        if (file_exists($file)) {
            require_once $file;
            break;
        }
    }
}
spl_autoload_register("__autoload");
