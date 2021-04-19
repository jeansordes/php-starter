<?php

// Create and configure Slim app
$app = new \Slim\App(['settings' => [
    'addContentLengthHeader' => false,
    'displayErrorDetails' => true,
]]);

// Get container
$container = $app->getContainer();

// Register component on container
$container['view'] = function ($container) {
    $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
    $twig = new \Twig\Environment($loader, [
        'cache' => false,
        // 'cache' => __DIR__ . '/templates/cache',
    ]);

    // In the case the base path is not http://www.exemple.com/ but something like http://www.exemple.com/my-app/
    // Code taken from https://github.com/selective-php/basepath/blob/master/src/BasePathDetector.php
    $basePath = '';
    if (PHP_SAPI === 'apache2handler') {
        // For apache
        if (isset($_SERVER['REQUEST_URI'])) {
            $scriptName = $_SERVER['SCRIPT_NAME'];
            $basePath = (string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $scriptName = str_replace('\\', '/', dirname(dirname($scriptName)));
            if ($scriptName === '/') {
                $basePath = '';
            } else {
                $length = strlen($scriptName);
                if ($length > 0 && $scriptName !== '/') {
                    $basePath = substr($basePath, 0, $length);
                }
                $basePath = strlen($basePath) > 1 ? $basePath : '';
            }
        }
    } else if (PHP_SAPI === 'cli-server') {
        // For built-in server
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $basePath = str_replace('\\', '/', dirname($scriptName));
        $basePath = strlen($basePath) > 1 ? $basePath : '';
    }
    $twig->addGlobal('base_path', $basePath);
    $twig->addGlobal('current_path', parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH)); // https://stackoverflow.com/a/25944383/5736301
    $twig->addGlobal('is_localhost', in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']));

    // CURRENT USER
    $twig->addGlobal('current_user', (empty($_SESSION['current_user']) ? null : $_SESSION['current_user']));

    // Displays alerts created with the function `alert` in /src/utilities.php
    $twig->addGlobal('session_alert', (empty($_SESSION['session_alert']) ? null : $_SESSION['session_alert']));
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
    $twig->addFilter($filter);

    return $twig;
};

//Override the default Not Found Handler
$container['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        return $c['response']
            ->withStatus(404)
            ->write($c->view->render('error.html.twig', ['message' => '404 - Page introuvable']));
    };
};
$container['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        console_log($exception);
        return $c['response']
            ->withStatus(500)
            ->write($c->view->render('error.html.twig', [
                'message' => $exception->getMessage(),
                "details" => $c['settings']['displayErrorDetails'] ? $exception->getFile() . ":" . $exception->getLine() : '',
            ]));
    };
};
