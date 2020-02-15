<?php

require_once 'vendor/autoload.php';
session_start();

$github_client_id = '738bde32ca3360c16554';
$github_client_secret = '0f3808aa2f6bc0647c9ccbd394dcbf84f9f5d0c6';
$allowed_users = json_decode(file_get_contents("https://test.lucacastelnuovo.nl/users/Luca-Castelnuovo/configuration/transfer.json"))->allowed_users;


$provider = new League\OAuth2\Client\Provider\Github([
    'clientId'          => $github_client_id,
    'clientSecret'      => $github_client_secret,
    // 'redirectUri'       => 'https://transfer.lucacastelnuovo.nl/login.php',
    'redirectUri'       => 'http://localhost:8080/login.php',
]);

function redirect($to, $alert = null)
{
    $_SESSION['alert'] = $alert;
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
        redirect('/login.php?reset', 'Invalid State');
    }

    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    try {
        $username = $provider->getResourceOwner($token)->getNickname();

        if (!in_array($username, $allowed_users)) {
            redirect('/login.php?reset', 'Account not approved');
        }

        $_SESSION['logged_in'] = true;
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];

        redirect('/');
    } catch (Exception $error) {
        redirect('/login.php?reset', $error);
    }
}

if (isset($_GET['reset'])) {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
    }

    session_destroy();
    session_start();

    redirect('/login.php', $alert);
}


if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    redirect('/');
}

?>
<!DOCTYPE html>
<html>

<head>
    <!-- Config -->
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link rel="manifest" href="manifest.json?1.3.2" />
    <title>Login || Secure Transfer</title>

    <!-- Icons -->
    <link rel="apple-touch-icon" href="img/apple-touch-icon.png?1.3.2" sizes="180x180" />
    <link rel="icon" type="image/png" href="img/favicon-32x32.png?1.3.2" sizes="32x32" />
    <link rel="icon" type="image/png" href="img/favicon-16x16.png?1.3.2" sizes="16x16" />
    <link rel="manifest" href="manifest.json?1.3.2" />
    <link rel="mask-icon" href="img/safari-pinned-tab.svg?1.3.2" color="#ffcc00" />
    <link rel="shortcut icon" href="img/favicon.ico">
    <meta name="msapplication-config" content="browserconfig.xml">
    <meta name="theme-color" content="#ffe57e" />

    <!-- Styles -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
</head>

<body>
<div class="row">
    <div class="col s12 m8 offset-m2 l4 offset-l4">
        <div class="card">
            <div class="card-action blue accent-4 white-text">
                <h3>Secure Transfer</h3>
            </div>
            <div class="card-content">
                <div class="row center">
                    <a class="waves-effect waves-light btn-large blue accent-4" href="?authenticate">
                        Login with GitHub
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <?php 
        if (isset($_SESSION['alert']) && !empty($_SESSION['alert'])) {
            echo "<script>M.toast({html: \"{$_SESSION['alert']}\"});</script>";
            unset($_SESSION['alert']);
        }
    ?>>
</body>

</html>
