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


$addError = false;
$content = '';
// If we have post with original text try post it:
if(isset($_POST['content']))
{
    $content = $_POST['content'];
    $postOTres = $wmApi->addOriginalText($hostID,$_POST['content']);
    if(!isset($postOTres->error_code)&&isset($postOTres->text_id))
    {
        webmaster_api_example_tpl::redirect('./original_texts.php?host_id='.$hostID."&added=".$postOTres->text_id);
    } else $addError = true;
}

// If we have delete request:
if(isset($_GET['delete'])&&$_GET['delete']==='true'&&!empty($_GET['text_id']))
{
    $delOTres = $wmApi->deleteOriginalText($hostID,$_GET['text_id']);
    if(empty($delOTres->error_code))
    {
        webmaster_api_example_tpl::redirect('./original_texts.php?host_id='.$hostID."&deleted=true");
    }
    die();
}


$originalTexts = $wmApi->getOriginalTexts($hostID);


// Если саммари - с ошибкой, это какой-то полтергейст, ибо мы уже проверили факт наличия этого хоста и его верификации
if(!empty($originalTexts->error_code)) webmaster_api_example_tpl::err500();

// Let's show it
webmaster_api_example_tpl::init()->header($info->unicode_host_url.' | Original texts');



// If was error adding text, show info
if($addError)
{
    echo '<div class=error>'.$postOTres->error_message.'</div>';
}

?>
<a href="host.php?host_id=<?=$hostID?>">Общая информация</a>

<div class="hostinfo">
    <h2>Оригинальные тексты</h2>
    <?php
    if(!count($originalTexts->original_texts))
    {
        ?>
        <span class="hostinfo_item">
            Вы не добавили ни одного оригинального текста
        </span>
        <?
    } else
    {
        foreach ($originalTexts->original_texts as $originalText)
        {
            ?>
            <span class="hostinfo_item original_text">
                <?=$originalText->content_snippet?>
                <span style="font-style: italic">
                    <?=date('d.m.Y H:i:s',strtotime($originalText->date))?>
                </span>
                <a href="./original_texts.php?host_id=<?=$hostID?>&delete=true&text_id=<?=$originalText->id?>" onclick="return window.confirm('Are you sure you want to delete this text?');">удалить</a>
            </span>
            <?
        }
    }
?>
    <form action="./original_texts.php" method="post" enctype="application/x-www-form-urlencoded">
        <input type="hidden" name="host_id" value="<?=htmlspecialchars($hostID)?>">
<textarea cols="100" rows="10" name="content"><?=htmlspecialchars($content)?></textarea>
        <input type="submit">
    </form>
</div>

