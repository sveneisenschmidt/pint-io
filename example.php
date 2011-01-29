<?php

return function($r) {
    $r->use(new \rubidium\Lint);
    $r->use(new \rubidium\StaticFiles);
    $r->use(new \rubidium\Logger);
    $r->run(new \example\App);
};
