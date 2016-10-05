<?php
use yandex\webmaster\api\webmasterApi;

$cfg_path = realpath(dirname(__FILE__))."/config.php";

if(!is_file($cfg_path))
{
    trigger_error("Please, copy file conf.example.php to config.php and set up your client_id and client_secret here", E_USER_ERROR);
    die();
}
include($cfg_path);


// Add TPL class
require_once ($path."/.tpl.php");

// Add wmAPI class
require_once($apiScript);

// Check authorization
include $path."/.auth.php";