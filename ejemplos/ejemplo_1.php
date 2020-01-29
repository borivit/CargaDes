<?php
spl_autoload_register(function ($class) {
    include $_SERVER['DOCUMENT_ROOT'] . '/vendor/borivit/cargades/rama_2/' . $class . '.class.php';
});

header('Content-Type: text/html; charset=UTF-8');

$path = str_replace(clean_url($_GET['url_']), '', $_GET['url_']);

$CargaDes = new CargaDes;
//--- Отдаем файл пользователю через браузер
$realFilePath = $_SERVER['DOCUMENT_ROOT'] . $path . 'test.txt';
$CargaDes->setOnStart(new Exe(new ClientD($realFilePath)));
echo $CargaDes->Start();
//--------------------------
function clean_url($url)
{

    if ($url == '') return;

    $url = str_replace("http://", "", strtolower($url));
    $url = str_replace("https://", "", $url);
    if (substr($url, 0, 4) == 'www.') $url = substr($url, 4);
    $url = explode('/', $url);
    $url = reset($url);
    $url = explode(':', $url);
    $url = reset($url);

    return $url;
}