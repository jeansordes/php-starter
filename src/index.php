<?php
require_once '../vendor/autoload.php';

session_start();

// Create and configure Slim app
$app = new \Slim\App(['settings' => [ 'addContentLengthHeader' => false]]);

// Get container
$container = $app->getContainer();

// Register component on container
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig(__DIR__ . '/templates', [
        'cache' => __DIR__ . '/templates/cache'
    ]);
    
    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));

    return $view;
};

//Override the default Not Found Handler
$container['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        $c->view->render($response, 'error.html', [ 'message' => '404 - page not found' ]);
        return $c['response']
            ->withStatus(404)
            ->withHeader('Content-Type', 'text/html');            
    };
};


// Define app routes
$app->get('/[hello/{name}]', function ($request, $response, $args) {
    $title = empty($args['name']) ? 'you' : $args['name'] ;
    return $this->view->render($response, 'homepage.html', [
        'title' => 'Hello ' . $title,
        'body' => "And welcome! Here is a random string : " . MyApp\Utility\Math::getARandomString()
    ]);
})->setName('homepage');

// Run app
$app->run();