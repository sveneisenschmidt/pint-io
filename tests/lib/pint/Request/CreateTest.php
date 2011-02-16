<?php

namespace tests\lib\pint;

use \pint\Request,
    \pint\Connection,
    \pint\Exception;


class Request_CreateTest extends \PHPUnit_Framework_TestCase
{
    
    /**
     * Setup
     * 
     */
    public function setUp() 
    { 
        $this->input_headers = array(
            'request'       => 'GET / HTTP/1.1',
            'host'          => 'Host: testhost:3000',
            'agent'         => 'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:2.0b11) Gecko/20100101 Firefox/4.0b11',
            'accept'        => 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,* / *;q=0.8',
            'accept-lang'   => 'Accept-Language: de-de,de;q=0.8,en-us;q=0.5,en;q=0.3',
            'accept-enc'    => 'Accept-Encoding: gzip, deflate',
            'accept-char'   => 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'keep-alive'    => 'Keep-Alive: 115',
            'connection'    => 'Connection: keep-alive',
            'cache'         => 'Cache-Control: max-age=0'
        );
        
        $this->input_body = array(
            "\r\n\r\n"
        );
    }
    
    
    /**
     * Teardown
     * 
     */
    public function tearDown() {}
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     */
    public function NewInstanceOnCreate()
    {
        $input  = \implode("\r\n", $this->input_headers);
        $input .= \implode('', $this->input_body);
        $request = \pint\Request::parse($input, array());
        
        $this->assertInstanceOf('\pint\Request', $request);
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     * @expectedException \pint\Exception
     */
    public function ThrowsExceptionOnEmptyInput()
    {
       \pint\Request::parse('');
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     */
    public function HeadersSuccesfullyParsed()
    {
        $input  = \implode("\r\n", $this->input_headers);
        $input .= \implode('', $this->input_body);
        
        $request = \pint\Request::parse($input);
        $this->assertNotEmpty($request['headers']);
        $this->assertNotEmpty($request['method']);
        $this->assertNotEmpty($request['uri']);
        $this->assertNotEmpty($request['version']);
        
        $headers = $request->headers();
        $keys    = array(
            'Host', 'User-Agent', 'Accept', 'Accept-Language', 'Accept-Encoding', 
            'Accept-Charset', 'Keep-Alive', 'Connection', 'Cache-Control'
        );
        
        foreach($keys as $key) {
            $this->assertArrayHasKey($key, $headers);
        }
        
        return $request;
    }
    
    
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     */
    public function InvalidHeaders_MissingMethodUri()
    {
        $incompleteHeaders = $this->input_headers;
        unset($incompleteHeaders['request']);
        
        $input  = \implode("\r\n", $incompleteHeaders);
        $input .= \implode('', $this->input_body);
        
        $request = \pint\Request::parse($input);
        
        $this->assertTrue($request->haserror());
        $this->assertEquals('\pint\Request::parseHeaders', $request->errormsg());
        $this->assertEquals($request['headers'], array());
        $this->assertEquals($request['method'],  null);
        $this->assertEquals($request['uri'],     null);
        $this->assertEquals($request['version'], null);
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     */
    public function InvalidHeaders_InvalidHeadRequestFromRequestLine()
    {
        $defectHeaders = $this->input_headers;
        $defectHeaders['request'] = 'GET / HTTP/1.1 asdas asd asd';
        
        $input  = \implode("\r\n", $defectHeaders);
        $input .= \implode('', $this->input_body);

        $request = \pint\Request::parse($input);
        
        $this->assertTrue($request->haserror());
        $this->assertEquals('\pint\Request::parseHeaders', $request->errormsg());
        $this->assertEquals($request['headers'], array());
        $this->assertEquals($request['method'],  null);
        $this->assertEquals($request['uri'],     null);
        $this->assertEquals($request['version'], null);
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     */
    public function StaticMethod_ParseHeaders()
    {
        $status = \pint\Request::parseHeaders(new \pint\Request(), '');
        $this->assertFalse($status);
        
        $input  = \implode("\r\n", $this->input_headers);
        $input .= \implode('', $this->input_body);
        
        $status = \pint\Request::parseHeaders(new \pint\Request(), $input);
        $this->assertTrue($status);
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     */
    public function StaticMethod_ParseRequestLineSuccess()
    {
        $defectHeaders = $this->input_headers;
        $defectHeaders['request'] = 'GET / HTTP/1.1';
        
        $input  = \implode("\r\n", $defectHeaders);
        $input .= \implode('', $this->input_body);
        
        $status = \pint\Request::parseRequestLine(new \pint\Request(), $input);
        $this->assertTrue($status);
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     */
    public function StaticMethod_ParseRequestLineFailure()
    {
        $defectHeaders = $this->input_headers;
        $defectHeaders['request'] = 'GREET / PHHT/99.x12';
        
        $input  = \implode("\r\n", $defectHeaders);
        $input .= \implode('', $this->input_body);
        
        $status = \pint\Request::parseRequestLine(new \pint\Request(), $input);
        $this->assertFalse($status);
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     * @depends HeadersSuccesfullyParsed
     */
    public function StaticMethod_ValidateContentTypeSuccess(\pint\Request $request)
    {   
        $status = \pint\Request::validateContentType($request);
        $this->assertTrue($status);
        
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     * @depends HeadersSuccesfullyParsed
     */
    public function StaticMethod_ValidateContentTypeFailureInvalidMethod(\pint\Request $request)
    {   
        $headers = $request->headers();
        $request['method'] = 'DELETE';
        $headers['Content-Type']  = 'application/json';
        $request['headers'] = $headers;
        
        $status = \pint\Request::validateContentType($request);
        $this->assertFalse($status);
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     * @depends HeadersSuccesfullyParsed
     */
    public function StaticMethod_ValidateContentTypeFailureInvalidContentType(\pint\Request $request)
    {   
        $headers = $request->headers();
        $request['method'] = 'POST';
        unset($headers['Content-Type']);
        $request['headers'] = $headers;
        
        $status = \pint\Request::validateContentType($request);
        $this->assertFalse($status);
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     * @depends HeadersSuccesfullyParsed
     */
    public function HeaderGetsFiltered(\pint\Request $request)
    {  
        $headers = $request->headers();
        
        $this->assertFalse(\array_key_exists('Request Method', $headers));
        $this->assertFalse(\array_key_exists('Request Url', $headers));
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     */
    public function FilterSupport()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
}
