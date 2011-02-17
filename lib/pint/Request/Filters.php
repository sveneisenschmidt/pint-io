<?php

namespace pint\Request;

use \pint\Exception;


class Filters
{
    /**
     *
     * @param \pint\Request $request
     * @param string $input
     * @param array $config
     * @return void
     */
    static function parseHeaders(\pint\Request $request, $input, array $config = array())
    {
        $raw   = @\http_parse_headers($input[0]);
        
        if($raw === false ||
           !\array_key_exists('Request Method', $raw) ||
           !\array_key_exists('Request Url', $raw)    
        ) {
            throw new \pint\Exception('Could not parse headers!');
        }
        
        $request->offsetSet('REQUEST_METHOD', $raw['Request Method']);
        $request->offsetSet('REQUEST_URI',    $raw['Request Url']);
        
        unset($raw['Request Method'], $raw['Request Url']);
        
        foreach ($raw as $key => $value)
        {
            $key = \preg_replace("#[^a-z]+#i", "_", $key);
            $request->offsetSet('HTTP_' . \strtoupper($key), $value);
        }
    }
    
    /**
     *
     * @param \pint\Request $request
     * @param string $input
     * @param array $config
     * @return void
     */
    static function parseRequestLine(\pint\Request $request, $input, array $config = array())
    {
        $lines = \explode("\r\n", $input[0]);
        \preg_match("#^(?P<method>GET|HEAD|POST|PUT|OPTIONS|DELETE)\s+(?P<uri>[^\s]+)\s+HTTP/(?P<version>1\.\d)$#U", trim($lines[0]), $matches);
        if(!\array_key_exists('version', $matches)) {
            throw new \pint\Exception('Could not parse rrequest line!');
        }
        
        $request->offsetSet('HTTP_VERSION', 'HTTP/' . trim($matches['version']));
        unset($matches);
    }
    
    /**
     *
     * @param \pint\Request $request
     * @param string $input
     * @param array $config
     * @return void
     */
    static function validateContentType(\pint\Request $request, $input, array $config = array())
    {
        if(\array_key_exists('Content-Type', $request) && !\in_array($request['REQUEST_METHOD'], array('POST', 'PUT'))) {
            throw new \pint\Exception('Conten-Type header is set but wrong HTTP method, epxected POST or PUT');
        }
        
        if(\in_array($request['REQUEST_METHOD'], array('POST', 'PUT')) && !\array_key_exists('Content-Type', $request)) {
            throw new \pint\Exception('Conten-Type header is not set but is needed by POST or PUT requests.');
        }
    }
    
    /**
     *
     * @param \pint\Request $request
     * @param string $input
     * @param array $config
     * @return void
     */
    static function createServerEnv(\pint\Request $request, $input, array $config = array())
    {
        $listen = explode(':', $request['HTTP_HOST']);
        $host   = str_replace('http://', '', $listen[0]);
        if(!isset($listen[1]) && !is_numeric($listen[1])) {
            $port = '-';
        } else {
            $port = $listen[1];
        }
        
        $request->offsetSet('SERVER_SOFTWARE',  'pint/0.0.0');
        $request->offsetSet('SERVER_PROTOCOL',  'HTTP/1.1');
        $request->offsetSet('SERVER_NAME',      'pint.io');
        $request->offsetSet('SERVER_PORT',      $port);
    }
    
    /**
     *
     * @param \pint\Request $request
     * @param string $input
     * @param array $config
     * @return void
     */
    static function createPathInfoEnv(\pint\Request $request, $input, array $config = array())
    {
        $parts = explode('?', $request['REQUEST_URI']);
        
        $request['PATH_INFO'] = isset($parts[0]) ? $parts[0]    : $request['REQUEST_URI'];
        $request['QUERY_STRING'] = isset($parts[1]) ? $parts[1] : '';
    }
    
    
    
    
    
    
}
