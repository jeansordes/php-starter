<?php

use Slim\Http\Request;
use Slim\Http\Response;

require_once __DIR__ . '/vendor/autoload.php';
session_start();

// dotenv
(new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/.env');

require_once 'src/utilities.php';
require_once 'src/slim-config.php';

// routes
foreach (glob("src/routes/*.php") as $filename) require_once $filename;
foreach (glob("src/routes/**/*.php") as $filename) require_once $filename;

// URL ending with / redirects to URL without /
$app->get('{url:.*}/', function (Request $request, Response $response, array $args) {
    return $response->withRedirect($args["url"], 301);
});

// Run app
$app->run();
