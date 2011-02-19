<?php
    
    ini_set('display_errors', '1');

    $headers = array(
        "boundary=--someBoundaryValue--\r\n"
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost:3000/upload");
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_POST, true);
    $post = array(
        "file" => "@" . __DIR__ . '/files/test.jpg',
    );
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
    
    if( ! $result = curl_exec($ch))
    {
        trigger_error(curl_error($ch));
    } 
    
    curl_close($ch);
    
