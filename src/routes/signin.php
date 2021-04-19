<?php

use Slim\Http\Request;
use Slim\Http\Response;

require_once __DIR__ . '/../utilities.php';
require_once __DIR__ . '/../sql-utilities.php';

$app->get('/', function (Request $request, Response $response, array $args): Response {
    if (!empty($_GET['action']) and !empty($_GET['token'])) {
        // => there is a token with an associated action
        // vérifier que l'action est valide
        if (!in_array($_GET['action'], ['reset_password', 'init_password', 'mail_login'])) {
            throw new Exception("'?action=" . $_GET['action'] . "' non traitable");
        }

        // vérifier le token (et récupérer les infos utiles au cas où)
        $payload = jwt_decode($_GET['token']);
        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('select_user_from_id_user'));
        $req->execute(['id_user' => $payload['id_user']]);
        $res = $req->fetch();
        $user_infos = [
            'id_user' => $payload['id_user'],
            'email' => $res['email'],
            'user_role' => $res['user_role'],
        ];
        if ($user_infos['id_user'] == null || $user_infos['email'] == null || $user_infos['user_role'] == null) {
            console_log($payload);
            console_log($user_infos);
            throw new Exception("Les infos de l'utilisateur n'ont pas été correctement initialisés");
        }
        if ($res['last_user_update'] != $payload['last_user_update']) {
            throw new Exception("Ce lien n'est plus valide (votre compte a été modifié depuis l'émission de ce lien)");
        }

        // connecter l'utilisateur
        $_SESSION['current_user'] = $user_infos;

        // montrer la bonne page
        if (in_array($_GET['action'], ['reset_password', 'init_password'])) {
            return $response->withRedirect('/password-edit');
        } else if (in_array($_GET['action'], ['mail_login'])) {
            return $response->withRedirect('/');
        }
    } else if (empty($_SESSION['current_user'])) {
        // not logged => /login
        return $response->withRedirect(empty($_GET['redirect']) ? '/login' : $_GET['redirect']);
    } else {
        return $response->withRedirect(empty($_GET['redirect']) ? '/' . $_SESSION['current_user']['user_role'] : $_GET['redirect']);
    }
    // return $response;
});

$app->get('/login', function (Request $request, Response $response, array $args): Response {
    if (empty($_SESSION['current_user'])) {
        return $response->write($this->view->render('signin/login.html.twig', $_GET));
    } else {
        return $response->withRedirect(empty($_GET['redirect']) ? '/' : $_GET['redirect']);
    }
});

$app->post('/login', function (Request $request, Response $response): Response {
    // verifier login + mdp, si oui, mettre dans $_SESSION['current_user'] : user_role + id_user + prenom + nom
    $missing_fields_message = get_form_missing_fields_message(['email', 'password'], $_POST);
    if ($missing_fields_message) {
        alert($missing_fields_message, 3);
        return $response->withRedirect($request->getUri()->getPath() . '?' . array_to_url_encoding($_POST));
    }
    $db = getPDO();
    $req = $db->prepare(getSqlQueryString('select_user_from_email'));
    $req->execute(['email' => $_POST['email']]);
    if ($req->rowCount() == 0) {
        alert("Cet email est inconnu", 3);
        return $response->withRedirect($request->getUri()->getPath() . '?' . array_to_url_encoding($_POST));
    }

    // get password hash (and infos at the same time)
    $res = $req->fetch();
    $user_infos = [
        'id_user' => $res['id_utilisateur'],
        'email' => $res['email'],
        'user_role' => $res['user_role'],
    ];

    // check password
    if (!password_verify($_POST['password'], $res['password_hash'])) {
        alert("Mot de passe incorrect", 3);
        return $response->withRedirect($request->getUri()->getPath() . '?' . array_to_url_encoding($_POST));
    }

    // 👍 all good : create session and redirect
    $_SESSION['current_user'] = $user_infos;
    return $response->withRedirect(empty($_GET['redirect']) ? '/' : $_GET['redirect']);
});

$app->get('/password-reset', function (Request $request, Response $response, array $args): Response {
    return $response->write($this->view->render('signin/password-reset.html.twig'));
});

$app->post('/password-reset', function (Request $request, Response $response): Response {
    if (empty($_POST['email'])) {
        alert(il_manque_les_champs(['email']), 3);
        return $response->withRedirect($request->getUri()->getPath());
    }

    // faire des tests pour vérifier que l'email renseigné est bien un primary_email
    $db = getPDO();
    $req = $db->prepare(getSqlQueryString('select_user_from_email'));
    $req->execute(['email' => $_POST['email']]);
    if ($req->rowCount() == 0) {
        alert("Cet email nous est inconnu : $_POST[email])", 3);
        return $response->withRedirect($request->getUri()->getPath());
    }
    $user = $req->fetch();

    // générer un token pour que l'utilisateur puisse réinitialiser son mot de passe
    $jwt = jwt_encode([
        "last_user_update" => $user['last_user_update'],
        "id_user" => $user['id_user'],
    ], 20);

    sendEmail(
        $_POST['email'],
        "Vous avez oublié votre de mot de passe ?",
        $this->view->render(
            'emails/password-reset.html.twig',
            ['url' => 'http://' . $_SERVER["SERVER_NAME"] . '/token?action=reset_password&token=' . $jwt]
        )
    );

    alert("Un email avec un lien pour réinitialiser votre mot de passe vous a été envoyé", 1);
    return $response->withRedirect('/login');
});

$app->get('/logout', function (Request $request, Response $response, array $args): Response {
    session_destroy();
    return $response->withRedirect(empty($_GET['redirect']) ? '/' : $_GET['redirect']);
});

$app->get('/admin', function (Request $request, Response $response, array $args): Response {
    return $response->write($this->view->render('homepage.html.twig', [
        'title' => 'Welcome :)',
        'body' => "Can't wait to see what you gonna code ᕕ( ՞ ᗜ ՞ )ᕗ",
    ]));
});
