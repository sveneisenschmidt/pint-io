<?php

namespace tests\lib\pint;

use \pint\Request;


class Request_BaseTest extends \PHPUnit_Framework_TestCase
{
    
    /**
     * Setup
     * 
     */
    public function setUp()
    {
        $this->request = new \pint\Request(); 
    }
    
    /**
     * Teardown
     * 
     */
    public function tearDown()
    {
        $this->request = null;
    }
    
    /**
     * 
     * @test
     */
    public function DoImplementArrayAccess()
    {
        $this->assertInstanceOf('ArrayAccess', $this->request);
    }
    
    /**
     * 
     * @test
     */
    public function ArrayAccessAndAccessMethodsReturnTheSame()
    {
        $this->assertEquals($this->request['headers'], $this->request->headers());
        $this->assertEquals($this->request['method'],  $this->request->method());
        $this->assertEquals($this->request['uri'],      $this->request->uri());
        $this->assertEquals($this->request['version'], $this->request->version());
    }
    
    /**
     * 
     * @test
     * @depends ArrayAccessAndAccessMethodsReturnTheSame
     * @expectedException \pint\Exception
     */
    public function ArrayAccessMethods()
    {
        $this->assertTrue($this->request->offsetExists('headers'));
        $this->request->offsetUnset('headers');
    }
    
    /**
     * 
     * @test
     */
    public function AllStatusCodesAreDelivered()
    {
        $states = $this->request->states();
        
        $this->assertClassHasAttribute('status', '\pint\Request');
        $this->assertEquals(40, count($states));
        $this->assertEquals(array(        
            100 => "Continue",
            101 => "Switching Protocols",
            200 => "OK",
            201 => "Created",
            202 => "Accepted",
            203 => "Non-Authoritative Information",
            204 => "No Content",
            205 => "Reset Content",
            206 => "Partial Content",
            300 => "Multiple Choices",
            301 => "Moved Permanently",
            302 => "Found",
            303 => "See Other",
            304 => "Not Modified",
            305 => "Use Proxy",
            307 => "Temporary Redirect",
            400 => "Bad Request",
            401 => "Unauthorized",
            402 => "Payment Required",
            403 => "Forbidden",
            404 => "Not Found",
            405 => "Method Not Allowed",
            406 => "Not Acceptable",
            407 => "Proxy Authentication Required",
            408 => "Request Timeout",
            409 => "Conflict",
            410 => "Gone",
            411 => "Length Required",
            412 => "Precondition Failed",
            413 => "Request Entity Too Large",
            414 => "Request URI Too Long",
            415 => "Unsupported Media Type",
            416 => "Requested Range Not Satisfiable",
            417 => "Expectation Failed",
            500 => "Internal Server Error",
            501 => "Method Not Implemented",
            502 => "Bad Gateway",
            503 => "Service Unavailable",
            504 => "Gateway Timeout",
            505 => "HTTP Version Not Supported"
         ), $states);
    }
    
    /**
     * 
     * @test
     */
    public function ErrorMessageHandling()
    {
        $message = 'test error test123 !?+*';
        
        $this->assertEmpty($this->request->errormsg());
        $this->assertFalse($this->request->haserror());
        
        
        $this->request->errormsg($message);
        $this->assertEquals($message, $this->request->errormsg());
        $this->assertTrue($this->request->haserror());
    }
    
    /**
     * 
     * @test
     */
    public function RightStatusMessagegetsReturned()
    {
        $this->assertEquals('Bad Request', $this->request->statusmsg(400));
    }
    
    
}
