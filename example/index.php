<?php
/**
 * How to use webmaster api.
 *
 * Get list of all added sites
 */


// Initializtion: get config and primary classes
require_once(dirname(__FILE__) . "/.init.php");

use yandex\webmaster\api\webmasterApi;

// Init webmaster api with your access token
$wmApi = webmasterApi::initApi($token);
if(isset($wmApi->error_message)) die($wmApi->error_message);


$errors = array();

if(isset($_REQUEST['delete'])&&$_REQUEST['delete']=='true'&&!empty($_REQUEST['host_id']))
{
    $deleted = $wmApi->deleteHost($_REQUEST['host_id']);

    if(isset($deleted->error_message)&&!empty($deleted->error_message))
    {
        $errors[] = $deleted;
    }
}

//Get list of hosts added to your account
$res = $wmApi->getHosts();


// If get error - then anything wrong
if(!empty($res->error_code)) webmaster_api_example_tpl::err500();



// Let's show it
webmaster_api_example_tpl::init()->header('Hosts added to your webmaster account');
?>
<a href="./new.php">add new host</a>
<ul class="hostlist">
    <?php
if(count($errors))
{
    foreach ($errors as $error) {
        ?>
        <div class="error">
            <?=$error->error_message?>
        </div>
        <?
    }
}
if(is_array($res->hosts) && count($res->hosts)>0)
{
    foreach ($res->hosts as $host)
    {
        $url = ($host->verified)?'./host.php':'./verify.php';
        $url .= '?host_id='.$host->host_id;
        ?>
        <li class="hostrow <?=($host->verified)?'verified':'notverified'?>">
            <a href="<?=$url?>"><?=$host->unicode_host_url?></a>  <a href="./index.php?host_id=<?=$host->host_id?>&delete=true"
                                                                        onclick="return window.confirm('Are you sure you want delete host &laquo;<?=$host->unicode_host_url?>&raquo;?');">[X]</a>
        </li>
        <?
    }
} else
{
    ?>
    <div clasee="no_hosts">There are no hosts added to your account</div>
    <?
}
?>
</ul>
