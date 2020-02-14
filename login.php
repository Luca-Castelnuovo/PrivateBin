<?php

$github_client_id = 'f450a315131d7e68c858';
$github_client_secret = 'be0505d419268ad82506a5920dd8cc183a34b250';
$allowed_users = json_decode(file_get_contents("https://test.lucacastelnuovo.nl/users/Luca-Castelnuovo/configuration/transfer.json"))->allowed_users;


$provider = new League\OAuth2\Client\Provider\Github([
    'clientId'          => $github_client_id,
    'clientSecret'      => $github_client_secret,
    'redirectUri'       => 'https://transfer.lucacastelnuovo.nl/login',
]);

//Redirect user
function redirect($to, $alert = null)
{
    if (!empty($alert)) {
        alert_set($alert);
    }

    header('location: ' . $to);
    exit;
}


// Set message
function alert_set($alert)
{
    $_SESSION['alert'] = $alert;
}


// Read message
function alert_display()
{
    if (isset($_SESSION['alert']) && !empty($_SESSION['alert'])) {
        echo "<script>M.toast({html: \"{$_SESSION['alert']}\"});</script>";
        unset($_SESSION['alert']);
    }
}


function login($user)
{
    $username = $user->getNickname();

    if (!in_array($username, $allowed_users)) {
        redirect("/login?reset", 'Account not allowed');
    }

    $_SESSION['logged_in'] = true;
    $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['id'] = $username;

    redirect('/', 'You are logged in');
}


function loggedin()
{
    if ((!$_SESSION['logged_in']) || ($_SESSION['ip'] != $_SERVER['REMOTE_ADDR']) || (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800))) {
        redirect("/login?reset", 'Please login');
    } else {
        $_SESSION['LAST_ACTIVITY'] = time();
    }
}


function reset_session()
{
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
    }

    session_destroy();
    session_start();

    redirect('/login', $alert);
}


if (isset($_GET['authenticate'])) {
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['state'] = $provider->getState();
    header('Location: '.$authUrl);
}

if (isset($_GET['code'])) {
    if(empty($_GET['state']) || ($_GET['state'] !== $_SESSION['state']))  {
        redirect('/login?reset', $error);
    }

    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    try {
        $user = $provider->getResourceOwner($token);
        login($provider->getResourceOwner($token));
    } catch (Exception $error) {
        redirect('/login?reset', $error);
    }
}

if (isset($_GET['logout'])) {
    alert_set('You are logged out.');
    reset_session();
}

if (isset($_GET['reset'])) {
    reset_session();
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
    <?= alert_display() ?>
</body>

</html>
