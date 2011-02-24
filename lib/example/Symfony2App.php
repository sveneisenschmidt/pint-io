<?php

namespace example;

class Symfony2App extends \pint\Adapters\Symfony2Adapter 
{
    /**
     *
     * You can configure your app via the constructor or directly in your config file 
     */
    public function __construct()
    {
        
        $this->bootstrap(__PINT_DIR__ . '/../pint-symfony2/app/bootstrap.php');
        $this->kernel('AppKernel', __PINT_DIR__ . '/../pint-symfony2/app/AppKernel.php');
        $this->stage('prod', false);
        $this->prepare();
    }
}
