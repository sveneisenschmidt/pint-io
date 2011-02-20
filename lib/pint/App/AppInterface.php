<?php

namespace pint\App;

interface AppInterface {
    
    public function __construct();
    
    public function call($env);
    
    public function process($env);
}
