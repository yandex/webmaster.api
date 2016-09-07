<?php


$path = realpath(dirname(__FILE__));
$apiScript = realpath($path."/../webmaster_api.class.php");

require_once ($path."/tpl.php");

require_once($apiScript);


$client_id = 'cde79bf9920a413d9da83f74c945c26a';
$client_secret = '7989137ae57d4098b3a8ae00be4ad6f9';

include $path."/auth.php";
