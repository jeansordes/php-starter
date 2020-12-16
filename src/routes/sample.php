<?php

$app->get('/[hello/{name:[A-Za-z]*}]', function ($request, $response, $args) {
    $title = empty($args['name']) ? 'you' : $args['name'];
    return $this->view->render('homepage.html.twig', [
        'title' => "Hello $title !",
        'body' => "And welcome ! Here is a random string : " . MyApp\Utility\Math::getARandomString(),
    ]);
});

$app->get('/zipcode/{code:[0-9]{5}}', function ($request, $response, $args) {
    return $this->view->render('homepage.html.twig', [
        'title' => 'Hello you !',
        'body' => "You entered the following zipcode : " . $args["code"],
    ]);
});
