<?php

namespace tests\lib\pint\Mixin;

use \pint\ContainerAbstract;


class ContainerAbstractTest extends \PHPUnit_Framework_TestCase
{
    
    /**
     * Setup
     * 
     */
    public function setUp()
    {
        $this->class = "";
    }
    
    /**
     * Teardown
     * 
     */
    public function tearDown()
    {
        $this->class = null;
    }
}
