<?php

$path = realpath(dirname(__FILE__));
$apiScript = realpath($path."/../webmaster_api.class.php");

// Get this code on https://oauth.yandex.com/client/new page
$client_id = '';
$client_secret = '';

require_once ($path."/tpl.php");

require_once($apiScript);




include $path."/auth.php";
