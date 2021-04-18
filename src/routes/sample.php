<?php

use Slim\Http\Request;
use Slim\Http\Response;

require_once __DIR__ . '/../utilities.php';

$app->get('/[hello/{name:[A-Za-z]*}]', function (Request $request, Response $response, array $args): Response {
    $title = empty($args['name']) ? 'you' : $args['name'];
    return $response->write($this->view->render('homepage.html.twig', [
        'title' => "Hello $title !",
        'body' => "And welcome ! Here is a random string : " . getARandomString(),
    ]));
});

$app->post('/[hello/{name:[A-Za-z]*}]', function (Request $request, Response $response, array $args): Response {
    return $response->withRedirect($request->getUri()->getPath());
});
