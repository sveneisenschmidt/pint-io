<?php

header("HTTP/1.0 404 Not Found");

// header("Content-type: application/json");
?>

<h1>Not Found!</h1>
<p>Just kidding, playing around with some headers.</p>

$_SERVER:
<pre>
<?php print_r($_SERVER); ?>
</pre>

$_GET:
<pre>
<?php print_r($_GET); ?>
</pre>
