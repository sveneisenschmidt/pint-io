<?php

require_once __DIR__.'/silex.phar';

$app = new Silex\Application();

$app->get('/', function () {
	return 'hello SILEX! (having a pint of guinness)';
});

$app->get('/bar', function () {
	return 'one more guinness please';
});

return $app;
