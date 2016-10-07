<?php
/**
 * How to use webmaster api.
 *
 * Add new host
 */


// Initializtion: get config and primary classes
require_once(dirname(__FILE__) . "/.init.php");

use yandex\webmaster\api\webmasterApi;

// Init webmaster api with your access token
$wmApi = new webmasterApi($token);


//array with errors (will used in form behind
$postErrors = array();


$url = (isset($_POST['url'])) ? $_POST['url'] : '';
$url = trim($url);
if(!empty($url))
{
    $ret = $wmApi->addHost($url);
    if(!$ret)
    {
        webmaster_api_example_tpl::err500();
    }
    if(!empty($ret->error_code))
    {
        $postErrors[] = $ret->error_message;
    }elseif(empty($ret->host_id)) webmaster_api_example_tpl::err500();
    else
    {
        webmaster_api_example_tpl::redirect("./host.php?host_id=".$ret->host_id);
    }
}




// Let's show it
webmaster_api_example_tpl::init()->header('Add new host');
?>
<?php
if(count($postErrors))
{
    ?>
    <ul class="errorlist">
        <?php
        foreach ($postErrors as $error)
        {
            ?>
            <li>
                <?=htmlentities($error)?>
            </li>
            <?
        }
        ?>
    </ul>
    <?
}
?>
<form action="./new.php" method="post">
    <label for="url">URL:</label>
    <input type="text" name="url" value="<?=htmlentities($url)?>">
    <input type="submit" value="Add">
</form>