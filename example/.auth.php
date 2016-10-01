<?php
session_start();

if(empty($client_id)||empty($client_secret))
{
    trigger_error('Please, enter your client id and secret code to config.php file', E_USER_ERROR);
    die();
}

// Check access token
if(!isset($_SESSION['access_token']))
{
    // if there we have callback param from ouath server try get access token by getAccessToken
    if(isset($_GET['code']))
    {
        $res = webmasterApi::getAccessToken($_GET['code'], $client_id, $client_secret);

        if (isset($res->access_token)) {
            $_SESSION['access_token'] = $res->access_token;
        } else die('bad code, try again');
    }
    // Else: redirect to oauth server
    else
    {
        webmaster_api_example_tpl::redirect("https://oauth.yandex.ru/authorize?response_type=code&client_id=" . $client_id);
    }
}

$token = $_SESSION['access_token'];