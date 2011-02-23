<?php

namespace pint\App;

interface AppInterface {
    
    public function __construct();
    
    public function call(\pint\Request $env);
    
    public function process(\pint\Request $env, \pint\Socket\ChildSocket $socket);
}
