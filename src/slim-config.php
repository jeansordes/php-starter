<?php

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require_once __DIR__ . '/utilities.php';

// Create Container using PHP-DI
$container = new Container();

// Set view in container
$container->set('view', function() {
    $twig = Twig::create(__DIR__ . '/templates', [
        'cache' => $_ENV['app_mode'] == 'dev' ? false : __DIR__ . '/templates/cache',
    ]);

    // In the case the base path is not http://www.exemple.com/ but something like http://www.exemple.com/my-app/
    $twig->getEnvironment()->addGlobal('base_url', getBaseUrl());
    $twig->getEnvironment()->addGlobal('current_path', parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));
    $twig->getEnvironment()->addGlobal('is_localhost', in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']));

    // CURRENT USER
    $twig->getEnvironment()->addGlobal('current_user', (empty($_SESSION['current_user']) ? null : $_SESSION['current_user']));

    // Displays alerts created with the function `alert` in /src/utilities.php
    $twig->getEnvironment()->addGlobal('session_alert', (empty($_SESSION['session_alert']) ? null : $_SESSION['session_alert']));
    $_SESSION['session_alert'] = null;

    // French equivalent to "2 minutes ago"
    $filter = new \Twig\TwigFilter('timeago', function ($datetime) {
        $time = time() - strtotime($datetime);

        $units = array(
            31536000 => 'an',
            2592000 => 'mois',
            604800 => 'semaine',
            86400 => 'jour',
            3600 => 'heure',
            60 => 'minute',
            1 => 'seconde'
        );

        foreach ($units as $unit => $val) {
            if ($time < $unit) continue;
            $numberOfUnits = floor($time / $unit);
            return ($unit <= 1) ? "à l'instant" :
                'il y a ' . $numberOfUnits . ' ' . $val . (($numberOfUnits > 1 and $val != 'mois') ? 's' : '');
        }
    });
    $twig->getEnvironment()->addFilter($filter);

    return $twig;
});

// Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

// Set base path if needed
if (isset($_SERVER['BASE_PATH'])) {
    $app->setBasePath($_SERVER['BASE_PATH']);
}

// Add Twig-View Middleware
$app->add(TwigMiddleware::createFromContainer($app));

// Add Routing Middleware - THIS MUST BE ADDED BEFORE ERROR MIDDLEWARE
$app->addRoutingMiddleware();

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware(
    $_ENV['app_mode'] == 'dev', // displayErrorDetails
    true, // logErrors
    true // logErrorDetails
);

// Custom Error Handler
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->registerErrorRenderer('text/html', function ($exception, $displayErrorDetails) use ($container) {
    $view = $container->get('view');
    
    console_log($exception);
    
    // Create a temporary response object to render the template into
    $tempResponse = new \Slim\Psr7\Response();
    
    // Render the template into the temporary response body
    $renderedResponse = $view->render($tempResponse, 'error.html.twig', [
        'message' => $exception->getMessage(),
        'details' => $_ENV['app_mode'] == 'dev' ? $exception->getFile() . ":" . $exception->getLine() : '',
        'title' => 'Error'
    ]);
    
    // Get the rendered content as a string from the temporary response body
    $renderedContent = (string)$renderedResponse->getBody();
    
    // Return the rendered string content
    return $renderedContent;
});

// Custom Not Found Handler
$customNotFoundHandler = function (Request $request, $exception) use ($container) {
    $view = $container->get('view');
    
    $response = new \Slim\Psr7\Response();
    $response = $response->withStatus(404);
    
    return $view->render($response, 'error.html.twig', [
        'message' => '404 - Page introuvable',
        'title' => 'Page introuvable'
    ]);
};

$errorMiddleware->setErrorHandler(
    \Slim\Exception\HttpNotFoundException::class,
    $customNotFoundHandler
);