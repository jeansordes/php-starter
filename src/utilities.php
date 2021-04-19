<?php

use MyApp\EditableException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

function sendEmail($to, $subject, $body)
{
    $mail = new PHPMailer();

    //Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->isSMTP();
    $mail->Host       = $_ENV['email_smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['email_username'];
    $mail->Password   = $_ENV['email_password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $_ENV['email_smtp_port'];

    // NO OUTPUT
    $mail->SMTPDebug = false;
    $mail->do_debug = 0;

    //Recipients
    $mail->setFrom($_ENV['email_username']);
    $mail->addAddress($to);

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->CharSet = 'UTF-8';

    if (!$mail->send()) {
        throw new Exception("<p>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</p>"
            . json_encode(['TO' => $to, 'SUBJECT' => $subject, 'BODY' => $body]));
    }
}

function getARandomString($length = 18, $keyspace = '')
{
    $base62 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $str = '';
    $keyspace = empty($keyspace) ? $base62 : $keyspace;
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
}

function console_log($payload)
{
    echo '<script>console.log(' . json_encode($payload) . ')</script>';
}

function jwt_encode($payload, $expire_minutes)
{
    if (array_key_exists('iat', $payload) or array_key_exists('exp', $payload)) {
        throw new \Exception("Attention, il ne faut pas mettre 'iat' et 'exp' dans le payload, c'est géré automatiquement");
    }
    $iat = time();
    $exp = $iat + 60 * $expire_minutes;
    return Firebase\JWT\JWT::encode(array_merge([
        "iat" => $iat,
        "exp" => $exp,
    ], $payload), $_ENV['jwt_key']);
}

function jwt_decode($token)
{
    return (array) Firebase\JWT\JWT::decode($token, $_ENV['jwt_key'], array('HS256'));
}

/**
 * @param string $message The message to be displayed
 * @param int $meaning_code 0 = info, 1 = success, 2 = warning, 3 = danger
 */
function alert($message, $meaning_code)
{
    $meaning_switch = ['alert-info', 'alert-success', 'alert-warning', 'alert-danger'];

    $_SESSION['session_alert'] = [
        'message' => $message,
        'meaning' => $meaning_switch[$meaning_code]
    ];
}

function loggedInSlimMiddleware(array $allowed_roles)
{
    global $_allowed_roles;
    $_allowed_roles = $allowed_roles;

    return function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {
        global $_allowed_roles;
        global $_internal_exception;
        if (!empty($_SESSION["current_user"]) && in_array($_SESSION["current_user"]["user_role"], $_allowed_roles)) {
            return $next($request, $response);
        } else {
            $origin = debug_backtrace(1)[0];
            console_log($origin);
            $e = new EditableException("Vous devez être <b>" . join(' ou ', $_allowed_roles) . "</b> pour pouvoir visualiser cette page", 0, $_internal_exception);
            $e->setFile($origin['file']);
            $e->setLine($origin['line']);
            throw $e;
        }
    };
}

function array_special_join(string $glue, string $last_item_glue, array $array)
{
    if (count($array) == 1) return $array[0];
    $last_item = array_pop($array);
    return join($glue, $array) . $last_item_glue . $last_item;
}

function get_form_missing_fields_message(array $keys, array $arr)
{
    $diff_keys = [];
    foreach ($keys as $key) {
        if (empty($arr[$key])) {
            $diff_keys[] = $key;
        }
    }
    return il_manque_les_champs($diff_keys);
}

function il_manque_les_champs($fields)
{
    if (count($fields) == 0) return null;
    if (count($fields) == 1) return 'Il manque le champs <b>' . $fields[0] . '</b>';
    if (count($fields) > 1) return 'Il manque les champs <b>' . array_special_join('</b>, <b>', '</b> et <b>', $fields) . '</b>';
}

function array_to_url_encoding($array)
{
    return join('&', array_map((fn ($k, $v) => $k . '=' . $v), array_keys($array), $array));
}
