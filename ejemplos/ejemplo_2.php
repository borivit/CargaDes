<?php
spl_autoload_register(function ($class) {
    include $_SERVER['DOCUMENT_ROOT'] . $_GET['path_'] . $class . '.class.php';
});

header('Content-Type: text/html; charset=UTF-8');

echo '<pre>
//--- Загрузка файлов на сервер через браузер с индикацией прогресса<br>
$CargaDes = new CargaDes;<br>
$url_server = "http://' . $_GET['url_'] . 'test_priem.txt";//Путь к принимающему скрипту<br>
$client_u = new ClientU($url_server, 1);<br>
$client_u->style = "http://' . clean_url($_GET['url_']) . '/vendor/borivit/cargades/rama_2/css/style.cargades.css";<br>
$CargaDes->setOnStart(new Exe($client_u, "p"));<br>
echo $CargaDes->Start();<br><br>
</pre>';

$CargaDes = new CargaDes;
//--- Загрузка файлов на сервер через браузер с индикацией прогресса
$client_u = new ClientU('http://' . $_GET['url_'] . 'test_priem.php', 1);
$client_u->style = 'http://' . clean_url($_GET['url_']) . $_GET['path_'] . "css/style.cargades.css";
$CargaDes->setOnStart(new Exe($client_u, 'p'));
echo $CargaDes->Start();
//-----------------------------
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