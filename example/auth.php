<?php

session_start();

if(empty($client_id)||empty($client_secret))
{
    die('Please, enter your client id and secret code to local.conf file');
}

if(!isset($_SESSION['access_token']))
{
    if(!isset($_GET['code']))
    {
        webmaster_api_example_tpl::redirect("https://oauth.yandex.ru/authorize?response_type=code&client_id=" . $client_id);
    }

    $res = webmasterApi::getAccessToken($_GET['code'],$client_id,$client_secret);

    if(isset($res->access_token))
    {
        $_SESSION['access_token'] = $res->access_token;
    } else die('bad code, try again');
}


$token = $_SESSION['access_token'];