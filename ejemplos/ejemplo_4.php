<?php
spl_autoload_register(function ($class) {
    include $_SERVER['DOCUMENT_ROOT'] . '/vendor/borivit/cargades/rama_2/' . $class . '.class.php';
});
header('Content-Type: text/html; charset=UTF-8');

$path = str_replace(clean_url($_GET['url_']), '', $_GET['url_']);

$CargaDes = new CargaDes;
//--- Отдаем файл на удаленный сервер со своего сервера
$server = new Server();
$server->style = 'http://' . clean_url($_GET['url_']) . "/vendor/borivit/cargades/rama_2/css/style.cargades.css";
$CargaDes->setOnStart(new Exe($server, 'p'));//Код индикатора
echo "Индикатор прогресса: " . $CargaDes->Start();

$server->remoteUrl = 'http://'.$_GET['url_'] . 'test_priem.php';
$server->realFilePath = $_SERVER['DOCUMENT_ROOT'] . $path . 'test.txt';//Можно использовать массив файлов
$server->post = array('login' => 'test', 'pass' => '12345');//Любые значения которые вы хотите передать на сервер

$CargaDes->setOnStart(new Exe($server, 'u'));
$r = $CargaDes->Start();

if ($r['test'] == false) {
    echo $r['result'];
}
//-----------------------------
echo '<br><br>
//--- Отдаем файл на удаленный сервер со своего сервера<br>
$CargaDes = new CargaDes;<br>
$server = new Server();<br>
$server->style = "http://' . clean_url($_GET['url_']) . '/vendor/borivit/cargades/rama_2/css/style.cargades.css";<br>
$CargaDes->setOnStart(new Exe($server, \'p\'));//Код индикатора<br>
echo "Индикатор прогресса: " . $CargaDes->Start();<br><br>

$server->remoteUrl = "http://'.$_GET['url_'] . $path . 'test_priem.php";//Путь к принимающему скрипту<br>
$server->realFilePath = '.$_SERVER['DOCUMENT_ROOT'] . $path . 'test.txt";//Путь отдаваемого файла//Можно использовать массив файлов<br>
$server->post = array(\'login\' => \'test\', \'pass\' => \'12345\');//Любые значения которые вы хотите передать на сервер<br><br>

$CargaDes->setOnStart(new Exe($server, \'u\'));<br>
$r = $CargaDes->Start();<br><br>

if ($r[\'test\'] == false) {<br>
    echo $r[\'result\'];<br>
}<br>
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