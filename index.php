<?php

use Slim\Http\Request;
use Slim\Http\Response;

require_once __DIR__ . '/vendor/autoload.php';
session_start();

require_once 'src/utilities.php';
loadDotEnv();

require_once 'src/slim-config.php';

// routes
foreach (glob("src/routes/*.php") as $filename) require_once $filename;
foreach (glob("src/routes/**/*.php") as $filename) require_once $filename;

// URL ending with / redirects to URL without /
$app->get('{url:.*}/', function (Request $request, Response $response, array $args) {
    return redirect($response->withStatus(301), $args["url"]);
});

// Run app
$app->run();
