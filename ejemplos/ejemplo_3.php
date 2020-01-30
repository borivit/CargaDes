<?php
spl_autoload_register(function ($class) {
    include $_SERVER['DOCUMENT_ROOT'] . $_GET['path_'] . $class . '.class.php';
});
header('Content-Type: text/html; charset=UTF-8');

$path = str_replace(clean_url($_GET['url_']), '', $_GET['url_']);

$CargaDes = new CargaDes;
//--- Забераем файл с удаленного сервера на свой сервер
$server = new Server();
$server->style = 'http://' . clean_url($_GET['url_']) . $_GET['path_'] . "css/style.cargades.css";
$CargaDes->setOnStart(new Exe($server, 'p'));//Код индикатора
echo "Индикатор прогресса: " . $CargaDes->Start();

$server->remoteUrl = "http://" . $_GET['url_'] . "test.txt";
$server->realFilePath = $_SERVER['DOCUMENT_ROOT'] . $path . "test/test_D.txt";

$CargaDes->setOnStart(new Exe($server, 's'));
$r = $CargaDes->Start();

if ($r['test'] == false) {
    echo $r['result'];
}
//-----------------------------
echo '<br><br><pre>
//--- Забераем файл с удаленного сервера на свой сервер<br>
$CargaDes = new CargaDes;<br>
$server = new Server();<br>
$server->style = "http://' . clean_url($_GET['url_']) . '/vendor/borivit/cargades/rama_2/css/style.cargades.css";<br>
$CargaDes->setOnStart(new Exe($server, \'p\'));//Код индикатора<br>
echo "Индикатор прогресса: " . $CargaDes->Start();<br><br>

$server->remoteUrl = "http://' . $_GET['url_'] . 'test.txt";//Файл который забераем<br>
$server->realFilePath = ' . $_SERVER['DOCUMENT_ROOT'] . $path . 'test/test_D.txt";//Путь куда его кладем<br><br>

$CargaDes->setOnStart(new Exe($server, \'s\'));<br>
$r = $CargaDes->Start();<br><br>

if ($r[\'test\'] == false) {<br>
    echo $r[\'result\'];<br>
}</pre><br>
';
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