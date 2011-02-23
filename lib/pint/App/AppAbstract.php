<?php

namespace pint\App;

use \pint\App\AppInterface;

abstract class AppAbstract implements AppInterface {
    
    public function __construct() {}
    
    function process(\pint\Request $env, \pint\Socket\ChildSocket $socket) {}
    
}
