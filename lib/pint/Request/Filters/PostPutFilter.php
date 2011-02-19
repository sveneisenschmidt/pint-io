<?php

namespace pint\Request\Filters;

use \pint\Exception;


class PostPutFilter
{   
    /**
     *
     * @param \pint\Request $request
     * @param array $input
     * @param array $config
     * @return void
     */
    public static function parse(\pint\Request $request, $input, array $config = array())
    {
        list($headers, $body) = array($input[0], $input[1]);
        
        if($request['REQUEST_METHOD'] != 'POST' && $request['REQUEST_METHOD'] != 'PUT') {
            return;
        }       
        
        if($request['REQUEST_METHOD'] == 'PUT' && 
            (empty($body) || (int)$request['HTTP_CONTENT_LENGTH'] < 1)
        ) {
            throw new \pint\Exception('PUT request but empty body received!');
        }   
        
        self::process($request, $input, $config);
    }
    
    /**
     *
     * @param \pint\Request $request
     * @param array $input
     * @param array $config
     * @return void
     */
    protected static function process (\pint\Request $request, $input, array $config = array())
    {
        list($contenttype)    = \preg_split('#\s*;\s*#', $request['HTTP_CONTENT_TYPE']);
        
        switch($contenttype) {
        
            case 'application/octet-stream':
                print 'application/octet-stream'; return;
                throw new \pint\Exception('Not yet implemented!');
            break;
            
            //see @ http://www.w3.org/Protocols/rfc1341/7_2_Multipart.html
            case 'multipart/form-data':
                self::processMultiPartFormData($request, $input, $config);
            break;
            
            default:
                $request['PINT_BODY'] = $body;
        }
    }
    
    /**
     *
     * @param \pint\Request $request
     * @param array $input
     * @param array $config
     * @return void
     */
    protected static function processMultiPartFormData (\pint\Request $request, $input, array $config = array())
    {
        list($headers, $body) = array($input[0], $input[1]);
        list($contenttype)    = \preg_split('#\s*;\s*#', $request['HTTP_CONTENT_TYPE']);
        
        if(!preg_match('#boundary=(.*)(?:;)?#', $request['HTTP_CONTENT_TYPE'], $matches)) {
            throw new \pint\Exception('Could not detect any boundary!');
        }
        
        $boundary = $matches[1];
        $parts    = explode('--' . $boundary, $body);
        
        if(count($parts) < 1) {
            throw new \pint\Exception('Could not detect any body parts!');
        }
        
        $multiparts = array();
        foreach($parts as $index => $part) {
            if(trim($part) == '' || trim($part) == '--') {
                continue;
            }
            
            $headers = \http_parse_headers($part);
            if(!isset($headers['Content-Type']) && !isset($headers['Content-Disposition'])) {
                throw new \pint\Exception('Could not determinate not-set Content-Type 
                                           because Content-Disposition is missing!');
            } else 
            if(!isset($headers['Content-Type'])) {
                list($ctype) = \preg_split('#\s*;\s*#', $headers['Content-Disposition']);
                if($ctype == false || trim($ctype) == '') {
                    throw new \pint\Exception('Could not detect Content-type from Content-Disposition!');
                }
                $headers['Content-Type'] = 'multipart/' . $ctype;
            }
            
            if($headers['Content-Type'] == 'multipart/form-data' && true == false) {
                if(!preg_match('#form-data;\s*name=".*"(?:filename=".*")?(?:\r\n\r\n)?#', $headers['Content-Disposition'], $matches)) {
                    throw new \pint\Exception("Could not parse Content-Disposition: {$headers['Content-Disposition']}");
                }
                
                if(!$position = strpos($part, $matches[0])) {
                    throw new \pint\Exception("Could not extract body!");
                }
                
                $body = substr($part, $position + strlen($matches[0]));
                $headers['Content-Disposition'] = $matches[0];
                
            }
            
            $subparts = explode("\r\n\r\n", trim($part, "\r\n"));
            
            if(count($subparts) < 2) {
                
                
                var_dump(explode("\r", $part));
                die();
                $subpart = $part;
                foreach($headers as $name => $value) {
                    $subpart = str_replace($name . ': ' . $value, '', $subpart);
                }
                
                
            } else {
                $body = $subparts[1];    
            }
            
            // ;   
            $multiparts[] = array(
                'headers' => $headers,
                'body'    => $body
            );               
        }
        
        // sort every multipart into his according namespace
        foreach($multiparts as $multipart) {
            $body        = $multipart['body'];
            $ctype       = $multipart['headers']['Content-Type'];
            $disposition = $multipart['headers']['Content-Disposition'];
            
            switch($ctype) {
                
                // string data
                case 'multipart/form-data': 
                    
                    // example disposition: form-data; name="a"
                    if(!preg_match('#form-data;\s*name="(.*)"#', $disposition, $matches)) {
                        throw new \pint\Exception("Could not parse Content-Disposition: {$disposition}");
                    }
                    
                    if(!isset($request['PINT_FIELDS'])) {
                        $request['PINT_FIELDS'] = array();
                    } else {
                        $fields = $request['PINT_FIELDS'];
                    }
                    
                    // Cause of: "Indirect modification of overloaded element of pint\Request has no effect"
                    $fields[$matches[1]] = $body;
                    $request['PINT_FIELDS'] = $fields;
                    
                break;
                
                // binary data
                default:
                
                    if(!preg_match('#.*;\s*name="(.*)";\s*filename="(.*)"#', $disposition, $matches)) {
                        throw new \pint\Exception("Could not parse Content-Disposition: {$disposition}");
                    }
                    
                    list($key, $path) = array($matches[1], $matches[2]);
                    
                    
                    $tmpfile = self::saveTempFile($body);
                    
                    if(!isset($request['PINT_FILES'])) {
                        $request['PINT_FILES'] = array();
                    } else {
                        $files = $request['PINT_FILES'];
                    }
                    
                    $files[] = array(
                        'name'     => basename($path),
                        'type'     => mime_content_type($tmpfile),
                        'tmp_name' => $tmpfile,
                        'size'     => filesize($tmpfile),
                        'error'    => null
                    );
                    
                    $request['PINT_FILES'] = $files;
                break;
            }
        }
        
        print_r($request);
        die();
    }
    
    /**
     *
     * @param string $body
     * @return string
     */
    protected function saveTempFile($body)
    {
        $tmpfile = sys_get_temp_dir() . '/pint_tmp_' . \md5(\uniqid() . time());
        $handle = fopen($tmpfile, 'w');
        if(!$handle || !@fwrite($handle, $body)) {
            throw new \pint\Exception("Could not write tmp file for PUT/POST: {$tmpfile}. Error: ");
        }
        fclose($handle);
        return $tmpfile;
    }
    
}