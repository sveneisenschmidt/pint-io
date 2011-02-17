<?php

namespace pint\Mixin;

class ContainerAbstract implements \ArrayAccess
{
    /**
     *
     * @var string
     */
    protected $container = 'data';
    /**
     *
     * @var array
     */
    protected $data = array();
    
    /**
     * 
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset) 
    {
        return isset($this->{$this->container}[$offset]);
    }
    
    /**
     * 
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        return $this->{$this->container}[$offset];
    }
    
    /**
     * 
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value) 
    {
        $this->{$this->container}[$offset] = is_string($value) ? trim($value) : $value;
    }
    
    /**
     * 
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset) 
    {
        unset($this->{$this->container}[$offset]);
    }
    
    /**
     * 
     * @return void
     */
    public function states() 
    {
        return $this->{$this->container};
    }
}
    