<?php

$post = 'article';
$response = 'comment';

// Repair
if(strpos($config->url_path, '/id:') !== false) {
    Weapon::add('tab_button_before', function($page, $segment) use($config, $speak) {
        include __DIR__ . DS . 'unit' . DS . 'tab' . DS . 'button' . DS . 'new.php';
    }, .9);
    Weapon::add('tab_content_1_before', function($page, $segment) use($config, $speak) {
        include __DIR__ . DS . 'unit' . DS . 'form' . DS . 'date.php';
    }, .9);
}

Weapon::add('tab_content_1_before', function($page, $segment) use($config, $speak) {
    include __DIR__ . DS . 'unit' . DS . 'form' . DS . 'post' . DS . 'kind[].php';
}, 6.1);

require __DIR__ . DS . 'route.post.php';