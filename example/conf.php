<?php


$path = realpath(dirname(__FILE__));
$apiScript = realpath($path."/../webmaster_api.class.php");

require_once ($path."/tpl.php");

require_once($apiScript);

session_start();
$client_id = 'cde79bf9920a413d9da83f74c945c26a';
$client_secret = '7989137ae57d4098b3a8ae00be4ad6f9';

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
