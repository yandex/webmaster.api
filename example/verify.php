<?php
/**
 * How to use webmaster api.
 *
 * Verify host
 */


// Initializtion: get config and primary classes
require_once(dirname(__FILE__) . "/.init.php");

use yandex\webmaster\api\webmasterApi;

// Init webmaster api with your access token
$wmApi = webmasterApi::initApi($token);
if(isset($wmApi->error_message)) die($wmApi->error_message);

if(!isset($_REQUEST['host_id'])) webmaster_api_example_tpl::err404();

$hostID = $_REQUEST['host_id'];
//Get list of hosts added to your account
$hostInfo = $wmApi->getHostInfo($hostID);

if(!isset($hostInfo->verified)) webmaster_api_example_tpl::err404();

$verInfo = $wmApi->checkVerification($hostID);

if(isset($_REQUEST['use_method']))
{
    if(!in_array($_REQUEST['use_method'],$verInfo->applicable_verifiers)) webmaster_api_example_tpl::err404();
    if($verInfo->verification_state!=='IN_PROGRESS'&&$verInfo->verification_state!=='VERIFIED')
    {
        $start_verify_info = $wmApi->verifyHost($hostID,$_REQUEST['use_method']);
        webmaster_api_example_tpl::redirect("verify.php?host_id=".$hostID);
    }
}


// Let's show it
webmaster_api_example_tpl::init()->header('Verification host '.$hostInfo->unicode_host_url);



if($verInfo->verification_state!=='NONE')
{
    ?>
    Verification UIN: <?=$verInfo->verification_uin?><br />
    Last verification method: <?=$verInfo->verification_type?><br />
    Current status: <?=$verInfo->verification_state?><br />
    <?php if (isset($verInfo->fail_info->message)) {
    echo "Last error: " . $verInfo->fail_info->message;
} ?>
    <?php
}


if($verInfo->verification_state==='IN_PROGRESS')
{
    ?>
    Please, wait some time to end of validation! <br> <a href="javascript:location.reload(false)">reload page</a>
    <?
}elseif($verInfo->verification_state==='VERIFIED')
{
    ?>
    Site currently verified
    <?
}else {
    ?>
    <div>Verify host by:</div>
    <?
    foreach ($verInfo->applicable_verifiers as $verifier) {
        echo '<a href="verify.php?host_id=' . $hostID . '&use_method=' . $verifier . '" style="display:block; float:left; padding-right:10px;">';
        switch ($verifier) {
            case "DNS":
                echo "DNS";
                break;
            case "HTML_FILE":
                echo "html file";
                break;
            case "META_TAG":
                echo "meta tag";
                break;
            case "WHOIS":
                echo "whois info";
                break;
        }
        echo '</a>';
    }
    ?>
    <a href="verify.php?method="></a>
    <?
}
?>