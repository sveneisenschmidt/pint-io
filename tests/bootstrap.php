<?php

$path = dirname(dirname(__FILE__));
require $path . "/lib/SplClassLoader.php";

$loader = new SplClassLoader("rubidium", $path . "/lib");
$loader->register();
$appLoader = new SplClassLoader("example", $path . "/lib");
$appLoader->register();
