<?php
/**
 * How to use webmaster api.
 *
 * Original texts
 */


// Initializtion: get config and primary classes
require_once(dirname(__FILE__) . "/.init.php");

use yandex\webmaster\api\webmasterApi;

// Init webmaster api with your access token
$wmApi = webmasterApi::initApi($token);
if(isset($wmApi->error_message)) die($wmApi->error_message);

// Get host_id
if(empty($_REQUEST['host_id'])) webmaster_api_example_tpl::err404();
$hostID = $_REQUEST['host_id'];

$info = $wmApi->getHostInfo($hostID);
$info = webmaster_api_example_tpl::checkHost($info);



$history = $wmApi->getIndexingHistory($hostID);





// Если саммари - с ошибкой, это какой-то полтергейст, ибо мы уже проверили факт наличия этого хоста и его верификации
if(!empty($queries->error_code)) webmaster_api_example_tpl::err500();
// Let's show it
webmaster_api_example_tpl::init()->header($info->unicode_host_url.' | Popular Queries');



?>
<a href="host.php?host_id=<?=$hostID?>">Общая информация</a>

<div class="hostinfo">
    <?
    if(isset($history->indicators->DOWNLOADED))
    {
        ?>
        <h2>Загруженные</h2>
        <span class="hostinfo_item">
            <table border="1">
                <tr>
                <?
                foreach ($history->indicators->DOWNLOADED as $pages)
                {
                    ?>
                    <th>
                                <?=date('Y-m-d',strtotime($pages->date))?>
                    </th>
                    <?
                }
                ?>
                </tr>
                <tr>
                <?
                foreach ($history->indicators->DOWNLOADED as $pages)
                {
                    ?>
                    <td>
                        <?=$pages->value?>
                    </td>
                    <?
                }
                ?>
                </tr>
            </table>
        </span>
        <?
    }
    ?>
    <?
    if(isset($history->indicators->EXCLUDED))
    {
        ?>
        <h2>Исключенные</h2>
        <span class="hostinfo_item">
            <table border="1">
                <tr>
                <?
                foreach ($history->indicators->EXCLUDED as $pages)
                {
                    ?>
                    <th>
                                <?=date('Y-m-d',strtotime($pages->date))?>
                            </th>
                    <?
                }
                ?>
                </tr>
                <tr>
                <?
                foreach ($history->indicators->EXCLUDED as $pages)
                {
                    ?>
                    <td>
                        <?=$pages->value?>
                    </td>
                    <?
                }
                ?>
                </tr>
            </table>
        </span>
        <?
    }
    ?>
    <?
    if(isset($history->indicators->DOWNLOADED))
    {
        ?>
        <h2>В поиске</h2>
        <span class="hostinfo_item">
            <table border="1">
                <tr>
                <?
                foreach ($history->indicators->SEARCHABLE as $pages)
                {
                    ?>
                    <th>
                                <?=date('Y-m-d',strtotime($pages->date))?>
                            </th>
                    <?
                }
                ?>
                </tr>
                <tr>
                <?
                foreach ($history->indicators->SEARCHABLE as $pages)
                {
                    ?>
                    <td>
                        <?=$pages->value?>
                    </td>
                    <?
                }
                ?>
                </tr>
            </table>
        </span>
        <?
    }
    ?>
    <?
    $tic_history = $wmApi->getTicHistory($hostID);
    if(isset($tic_history->points))
    {
        ?>
        <h2>Тиц</h2>
        <span class="hostinfo_item">
            <table border="1">
                <tr>
                <?
                foreach ($tic_history->points as $pages)
                {
                    ?>
                    <th>
                                <?=date('Y-m-d',strtotime($pages->date))?>
                            </th>
                    <?
                }
                ?>
                </tr>
                <tr>
                <?
                foreach ($tic_history->points as $pages)
                {
                    ?>
                    <td>
                        <?=$pages->value?>
                    </td>
                    <?
                }
                ?>
                </tr>
            </table>
        </span>
        <?
    }
    ?>
</div>

