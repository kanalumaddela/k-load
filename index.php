<?php

// if installed with composer
require __DIR__ . '/vendor/autoload.php';
// or if installed manually by zip file
// require 'flight/Flight.php';

Flight::route('/', static function () {
    echo 'hello world!';
});

Flight::route('/json', static function () {
    Flight::json([
        'hello' => 'world'
    ]);
});

Flight::start();