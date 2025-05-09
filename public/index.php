<?php

// This is the front controller
// All requests go through here

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Load Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

// Load utilities and environment
require_once __DIR__ . '/../app/utilities.php';
loadDotEnv();

// Load Slim configuration
require_once __DIR__ . '/../app/slim-config.php';

// Load routes
foreach (glob(__DIR__ . "/../app/routes/*.php") as $filename) require_once $filename;
foreach (glob(__DIR__ . "/../app/routes/**/*.php") as $filename) require_once $filename;

// URL ending with / redirects to URL without /
$app->get('{url:.*}/', function (Request $request, Response $response, array $args) {
    return redirect($response, $args["url"]);
});

$app->run(); 