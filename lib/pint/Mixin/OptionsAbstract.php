<?php

namespace pint\Mixin;

class OptionsAbstract
{
    /**
     *
     * @var array
     */
    protected $options = array();

    /**
     *
     * @param array $options 
     * @return void
     */
    function __construct(array $options = array())
    {
        $this->options = $options;
    }
    
    /**
     *
     * @param type $options 
     * @return void
     */
    function options(array $options = null)
    {
        if(is_null($options)) {
            return $this->options;
        }
        
        $this->options = $options;
    }
    
    /**
     *
     * @param string $va$keylue 
     * @param mixed $value 
     * @return void
     */
    function option($key, $value = null)
    {
        if(is_null($value) && \array_key_exists($key, $this->options)) {
            return $this->options[$key];
        }
        
        $this->options[$key] = $value;
    }
}
