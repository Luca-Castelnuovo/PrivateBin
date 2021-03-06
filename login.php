<?php

require_once 'vendor/autoload.php';
session_start();

$github_client_id = '738bde32ca3360c16554';
$github_client_secret = ''; // Will be added on server
$allowed_users = json_decode(file_get_contents("https://cms.lucacastelnuovo.nl/api/singletons/get/Transfer"))->allowed_users;


$provider = new League\OAuth2\Client\Provider\Github([
    'clientId'          => $github_client_id,
    'clientSecret'      => $github_client_secret,
    'redirectUri'       => 'https://transfer.lucacastelnuovo.nl/login',
]);

function redirect($to)
{
    header('location: ' . $to);
    exit;
}


if (isset($_GET['authenticate'])) {
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['state'] = $provider->getState();
    redirect($authUrl);
}

if (isset($_GET['code'])) {
    if(empty($_GET['state']) || ($_GET['state'] !== $_SESSION['state']))  {
        redirect('/login');
    }

    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    try {
        $username = $provider->getResourceOwner($token)->getNickname();

        if (!in_array($username, $allowed_users)) {
            redirect('/login');
        }

        $_SESSION['logged_in'] = true;
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];

        redirect('/');
    } catch (Exception $error) {
        redirect('/login');
    }
}


session_destroy();
redirect("/");
