<?php


    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost:3000/upload");
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_POST, true);
    $post = array(
        "file" => "@" . __DIR__ . '/files/test.jpg',
    );
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
    curl_exec($ch);
    
    
    
