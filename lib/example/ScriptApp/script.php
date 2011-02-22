<?php

$path = trim($_SERVER['PATH_INFO'], '/');

switch($path) { 

    case 'server':
        print 'Hey, there is your Server env!';
        print '<pre>';
        print_r($_SERVER);
        print '</pre>';
    break;

    case 'query-string':
        print 'Hey, there is your Query String!';
        print '<pre>';
        print_r($_GET);
        print '</pre>';
    break;

    case 'request':
        print 'Hey, there is everything!<br/><br/>';
        print '$_GET';
        print '<pre>';
        print_r($_GET);
        print '</pre>';
        print '$_POST';
        print '<pre>';
        print_r($_POST);
        print '</pre>';
        print '$_FILES';
        print '<pre>';
        print_r($_FILES);
        print '</pre>';
        print '$_SERVER';
        print '<pre>';
        print_r($_SERVER);
        print '</pre>';
    break;

    case 'output-json':
        header('Content-Type: application/json');
        print json_encode(array(
            "ResultSet" => array(
                "Message" => "You requested some JSON? pint.IO is serving you soem JSON :)"
            )
        ));
        exit();
    break;

    case 'exception':
        throw new Exception('Some Exception!');
    break;

    case '':
        print 'Welcome!';
    break;
    
    default:
        header('HTTP/1.1 404 Not Found');
        print 'Not Found!';

}

$host = 'http://' . $_SERVER['HTTP_HOST'] . '/';

?>
<hr/>
<a href="<?php print $host; ?>">Start</a> |
<a href="<?php print $host; ?>server">Server Env</a> |
<a href="<?php print $host; ?>query-string?hello=my friend">Query String</a> |
<a href="<?php print $host; ?>request">Complete Request</a> |
<a href="<?php print $host; ?>exception">Exception</a> |
<a href="<?php print $host; ?>output-json">Some JSON?</a> |
<a href="<?php print $host . time() ; ?>">404</a>

