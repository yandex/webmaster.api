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

if(empty($_GET['host_id'])) webmaster_api_example_tpl::err404();
$hostID = $_GET['host_id'];

$info = $wmApi->getHostInfo($hostID);

$info = webmaster_api_example_tpl::checkHost($info);

$summary = $wmApi->getHostSummary($hostID);

// Если саммари - с ошибкой, это какой-то полтергейст, ибо мы уже проверили факт наличия этого хоста и его верификации
if(!empty($summary->error_code)) webmaster_api_example_tpl::err500();


$sitemap_errors = array();
// Добавляем файл sitemap
if(isset($_REQUEST['add_sitemap'])&&$_REQUEST['add_sitemap']==='true'&&isset($_REQUEST['sitemap_url']))
{
    $added = $wmApi->addSitemap($hostID,$_REQUEST['sitemap_url']);
    if(isset($added->error_message))
    {
        $sitemap_errors[] = $added->error_message;
    }else
    {
        webmaster_api_example_tpl::redirect("./host.php?host_id=".$hostID."#user_added_sitemaps");
    }
}

$sitemap_errors = array();
// Удаляем файл sitemap
if(isset($_REQUEST['delete_sitemap'])&&$_REQUEST['delete_sitemap']==='true'&&isset($_REQUEST['sitemap_id']))
{
    $deleted = $wmApi->deleteSitemap($hostID,$_REQUEST['sitemap_id']);
    if(isset($deleted->error_message))
    {
        $sitemap_errors[] = $deleted->error_message;
    }else
    {
        webmaster_api_example_tpl::redirect("./host.php?host_id=".$hostID."#user_added_sitemaps");
    }
}

// Let's show it
webmaster_api_example_tpl::init()->header($info->unicode_host_url.' | Info about host');
?>
<a href="original_texts.php?host_id=<?=$hostID?>">Оригинальные тексты</a>
<a href="search_queries.php?host_id=<?=$hostID?>">Популярные запросы</a>
<a href="history.php?host_id=<?=$hostID?>">История</a>
<div class="hostinfo">
    <h2>Общая информация</h2>
    <span class="hostinfo_item">
        Загружено страниц: <?=number_format($summary->downloaded_pages_count,0,'.',' ');?>
    </span>
    <span class="hostinfo_item">
        Исключено страниц: <?=number_format($summary->excluded_pages_count,0,'.',' ');?>
    </span>
    <span class="hostinfo_item">
        Страниц в поиске: <?=number_format($summary->searchable_pages_count,0,'.',' ');?>
    </span>
</div>


<div class="hostinfo">
    <h2>Владельцы хоста:</h2>
    <?
    $owners = $wmApi->getHostOwners($hostID);
    foreach ($owners->users as $user)
    {
        ?>
        <span class="hostinfo_item">
            <?=$user->user_login?>. Верефицирован методом <?=$user->verification_type?> <?=date('d.m.Y',strtotime($user->verification_date))?> c UIN <?=$user->verification_uin?>
            </span>
        <?
    }
    ?>
</div>


<div class="hostinfo">
    <h2>Файлы sitemap:</h2>
    <?
    $sitemaps = $wmApi->getHostSitemaps($hostID);

    if(!count($sitemaps->sitemaps))
    {
        ?>
        <span class="">Нет ни одного файла sitemap</span>
        <?
    }
    foreach ($sitemaps->sitemaps as $sitemap)
    {
        ?>
        <span class="hostinfo_item">
            <?=$sitemap->sitemap_url?>. Страниц: <?=$sitemap->urls_count?>. Ошибок: <?=$sitemap->errors_count?>.
            <?
            if(isset($sitemap->last_access_date)) echo "Загружен ".date('d.m.Y',strtotime($sitemap->last_access_date));
            else echo "Не загружен";


            if($sitemap->sitemap_type=='INDEX_SITEMAP')
            {
                $child_stmps = $wmApi->getHostSitemaps($hostID,$sitemap->sitemap_id);
                foreach ($child_stmps->sitemaps as $child_map)
                {
                    ?>
                    <span class="l2">
                            <?=$child_map->sitemap_url?>. Страниц: <?=$child_map->urls_count?>. Ошибок: <?=$child_map->errors_count?>.
            Загружен <?=date('d.m.Y',strtotime($child_map->last_access_date))?>
                        </span>
                    <?
                }
            }
            ?>
            </span>
        <?
    }
    ?>
</div>


<a name="user_added_sitemaps"></a>
<div class="hostinfo">
    <h2>Файлы sitemap, добавленные пользователем:</h2>
    <?
    if(count($sitemap_errors))
    {
        foreach ($sitemap_errors as $error)
        {
            ?>
            <div class="error"><?=$error?></div>
            <?
        }
    }
    ?>
    <?
    $user_sitemaps = $wmApi->getHostUserSitemaps($hostID);

    if(!count($user_sitemaps->sitemaps))
    {
        ?>
        <span class="">Нет ни одного файла sitemap</span>
        <?
    }

    foreach ($user_sitemaps->sitemaps as $sitemap)
    {
        ?>
        <span class="hostinfo_item">
            <?=$sitemap->sitemap_url?>. Добавлен: <?=date('d.m.Y h:i:s',strtotime($sitemap->added_date))?>.
            <a href="./host.php?host_id=<?=$hostID?>&delete_sitemap=true&sitemap_id=<?=$sitemap->sitemap_id?>#user_added_sitemaps" onclick="return window.confirm('Are you sure you want delete this sitemap?')">X</a>
            </span>


        <?
    }
    ?>
    <form action="./host.php?host_id=<?=$hostID?>&add_sitemap=true#user_added_sitemaps" method="post">
        <input type="text" value="" name="sitemap_url"> <input type="submit" value="add">
    </form>
</div>
