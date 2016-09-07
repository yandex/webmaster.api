<?php


$path = realpath(dirname(__FILE__));
$apiScript = realpath($path."/../webmaster_api.class.php");

require_once ($path."/tpl.php");

require_once($apiScript);



require_once ($path."/local.conf.php");
include $path."/auth.php";
