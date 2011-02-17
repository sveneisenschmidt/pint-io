<?php

namespace pint\Mixin;

class ContainerAbstract implements \ArrayAccess
{
    /**
     *
     * @var array
     */
    protected $container = array();
    
    /**
     * 
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset) 
    {
        return isset($this->container[$offset]);
    }
    
    /**
     * 
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        return $this->container[$offset];
    }
    
    /**
     * 
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value) 
    {
        $this->container[$offset] = is_string($value) ? trim($value) : $value;
    }
    
    /**
     * 
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset) 
    {
        unset($this->container[$offset]);
    }
    
    /**
     * 
     * @return void
     */
    public function states() 
    {
        return $this->status;
    }
}
    