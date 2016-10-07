<?php

class webmaster_api_example_tpl
{
    public function header($title)
    {
    header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE HTML PUBLIC  "-//W3C//DTD HTML 4.01//EN" "www.w3.org/TR/html4/strict.dtd">
<html>
        <head>
            <title><?=$title?></title>
            <meta http-equiv="content-type" content="text/html; charset=UTF-8">
            <style>
                .verified a
                {
                    font-weight: bold;
                }

                .notverified
                {
                    font-style: italic;
                }


                .hostinfo
                {

                }

                .hostinfo_item
                {
                    display:block;
                    padding: 5px;
                }

                .hostinfo_item .l2
                {
                    display: block;
                    padding: 5px;
                    padding-left: 50px;
                }

                .original_text
                {
                    padding: 7px;
                    border-bottom: 1px dotted darkgray;
                }


                .error
                {
                    color:red;
                }
            </style>
        </head>
        <body>
        <a href="./index.php">Список сайтов</a>
        <h1>
            <?=$title?>
        </h1>
        <?php
    }


    static function init()
    {
        static $extpl;
        if(!is_a($extpl,'webmaster_api_example_tpl')) $extpl = new self();

        return $extpl;
    }

    public function footer()
    {
?>
        </body>
</html>
<?
    }


    static function err500($die = true)
    {
        $tpl = self::init();

        header('HTTP/1.0 500 Internal Server Error');
        $tpl->header('Error 500');
        echo "Error 500";
        $tpl->footer();
        if($die) die();
        return true;
    }


    static function err404($die = true)
    {
        $tpl = self::init();
        header('HTTP/1.0 404 Not Found');
        $tpl->header('Error 404');
        echo "Error 404";
        $tpl->footer();
        if($die) die();
        return true;
    }


    /**
     * @param $url
     * @param bool $die
     * @return bool
     */
    static function redirect($url, $die = true)
    {
        header('HTTP/1.0 302 Moved Temporarily');
        header('location: '.$url);
        if($die) die();
        return true;
    }



    static function checkHost($hostObject)
    {

        if(!empty($hostObject->error_code))
        {
            webmaster_api_example_tpl::err404();
        }

        if(!$hostObject->verified)
        {
            webmaster_api_example_tpl::redirect('./verify.php?host_id='.$hostObject->host_id);
        }

        $textStatus = false;
        if($hostObject->host_data_status!=='OK')
        {
            $textStatus = '';
            switch ($hostObject->host_data_status)
            {
                case "NOT_INDEXED":
                    $textStatus='Host is not indexed';
                    break;
                case "NOT_LOADED":
                    $textStatus='Host is not loaded';
                    break;
            }
            webmaster_api_example_tpl::init()->header($hostObject->unicode_host_url.' | '.$textStatus);
            webmaster_api_example_tpl::init()->footer();
            die();
        }
        return $hostObject;
    }
}

?>