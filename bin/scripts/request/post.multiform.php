<?php

    ini_set('display_errors', '1');

    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost:3000/upload");
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_POST, true);
    $post = array(
        "file_upload_1" => "@" . __DIR__ . '/files/test.jpg',
        "form[group1][a]"    => time(),
        "form[group2][b]"    => time(),
    );
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
    
    if( ! $result = curl_exec($ch))
    {
        trigger_error(curl_error($ch));
    } 
    
    curl_close($ch);
    
