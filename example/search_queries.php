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



$queries = $wmApi->getPopularQueries($hostID,'TOTAL_CLICKS',array('TOTAL_SHOWS','TOTAL_CLICKS','AVG_SHOW_POSITION','AVG_CLICK_POSITION'));



// Если саммари - с ошибкой, это какой-то полтергейст, ибо мы уже проверили факт наличия этого хоста и его верификации
if(!empty($queries->error_code)) webmaster_api_example_tpl::err500();
// Let's show it
webmaster_api_example_tpl::init()->header($info->unicode_host_url.' | Popular Queries');



?>
<a href="host.php?host_id=<?=$hostID?>">Общая информация</a>

<div class="hostinfo">
    <h2>Популярные запросы</h2>
    <?
    if(!count($queries->queries))
    {
        ?>
        <span class="hostinfo_item">
            У вас нет показов в поиске
        </span>
        <?
    } else
    {
        ?>
        <table>
            <tr>
                <th>
                    Запрос
                </th>
                <th>
                    Показов
                </th>
                <th>
                    Кликов
                </th>
                <th>
                    Ср. Позиция
                </th>
                <th>
                    Ср. Позиция Клика
                </th>
            </tr>

            <?php
        foreach ($queries->queries as $query)
        {
            ?>
            <tr>
                <td>
                    <?=$query->query_text?>
                </td>
                <th style="font-style: italic; padding:0px 10px 0px 10px">
                    <?=$query->indicators->TOTAL_SHOWS?>
                </th>
                <th style="font-style: italic; padding:0px 10px 0px 10px">
                    <?=$query->indicators->TOTAL_CLICKS?>
                </th>
                <th style="font-style: italic; padding:0px 10px 0px 10px">
                    <?=(isset($query->indicators->AVG_SHOW_POSITION))?round($query->indicators->AVG_SHOW_POSITION,2):'n/a';?>
                </th>
                <th style="font-style: italic; padding:0px 10px 0px 10px">
                    <?=(isset($query->indicators->AVG_CLICK_POSITION))?round($query->indicators->AVG_SHOW_POSITION,2):'n/a';?>
                </th>
            </tr>
            <?
        }
        ?>
        </table>
        <?
    }
    ?>
</div>

