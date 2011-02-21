<?php

namespace pint\App;

use \pint\App\AppInterface;

abstract class AppAbstract implements AppInterface {
    
    final function __construct() {}
    
    function process($env, \pint\Socket\ChildSocket $socket) {}
    
}
