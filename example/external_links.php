<?php
/**
 * How to use webmaster api.
 *
 * External links
 */


// Initializtion: get config and primary classes
require_once(dirname(__FILE__) . "/.init.php");

use yandex\webmaster\api\webmasterApi;

// Init webmaster api with your access token
$wmApi = webmasterApi::initApi($token);
if (isset($wmApi->error_message)) die($wmApi->error_message);

// Get host_id
if (empty($_REQUEST['host_id'])) webmaster_api_example_tpl::err404();
$hostID = $_REQUEST['host_id'];

$info = $wmApi->getHostInfo($hostID);
$info = webmaster_api_example_tpl::checkHost($info);


$limit = 100;
$offset = 0;
if (isset($_GET['offset']) && intval($_GET['offset'])) $offset = intval($_GET['offset']);
$externalLinks = $wmApi->getExternalLinks($hostID, $offset, $limit);

// Если саммари - с ошибкой, это какой-то полтергейст, ибо мы уже проверили факт наличия этого хоста и его верификации
if (!empty($externalLinks->error_code)) webmaster_api_example_tpl::err500();

// Let's show it
webmaster_api_example_tpl::init()->header($info->unicode_host_url . ' | External Links');


?>
<a href="host.php?host_id=<?= $hostID ?>">Общая информация</a>

<div class="hostinfo">
    <h2>Внешние ссылки</h2>
    <?php
    foreach ($externalLinks->links as $link) {
        ?>
        <span class="hostinfo_item ">
            C <?= $link->source_url ?>
            на <?= $link->destination_url ?>
            (<small><?= date('d.m.Y', strtotime($link->source_last_access_date)) ?></small>)<br>
        </span>
        <?php
    }


    if ($offset > 0) {
        ?>
        <a href="./external_links.php?host_id=<?= $hostID ?>&offset=<?= (($offset - $limit) < 0 ? 0 : ($offset - $limit)); ?>">Показать
            предудщие <?= $limit ?> ссылок</a>
        <?php
    }

    if (count($externalLinks->links) == $limit) {
        ?>
        <a href="./external_links.php?host_id=<?= $hostID ?>&offset=<?= $offset + $limit ?>">Показать
            следующие <?= $limit ?> ссылок</a>
        <?php
    }
    ?>

</div>

