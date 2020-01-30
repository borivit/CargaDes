# CargaDes
## Curl php class - этот класс предназначен для передачи файлов с помощью Curl или браузера.
Класс очень прост в использовании и ниже я приведу примеры его использования, также в папке ejemplos есть рабочие примеры.

## License
Это программное обеспечение распространяется под лицензией [LGPL 3](http://www.gnu.org/licenses/gpl-3.0.html), вместе с [GPL Cooperation Commitment](https://gplcc.github.io/gplcc/). Пожалуйста, прочитайте ЛИЦЕНЗИЮ для получения информации о доступности и распространении программного обеспечения.

## Примеры использования CargaDes
```php
<?php

$CargaDes = new CargaDes;

//--- Отдаем файл пользователю через браузер
$realFilePath = dirname(  __FILE__ ) . file.zip;
$CargaDes->setOnStart(new Exe(new ClientD($realFilePath)));
$CargaDes->Start();

//--- Загрузка файлов на сервер через браузер с индикацией прогресса
$client_u = new ClientU('http://borivit.com/test/priem.php', 1);
$CargaDes->setOnStart(new Exe($client_u, 'p' ));
echo $CargaDes->Start();

//--- Забераем файл с удаленного сервера на свой сервер
$server = new Server();
$CargaDes->setOnStart(new Exe($server, 'p'));//Код индикатора
echo $CargaDes->Start();

$server->remoteUrl = 'http://borivit.com/test/file.zip';
$server->realFilePath = dirname(  __FILE__ ) . '/file.zip';

$CargaDes->setOnStart(new Exe($server, 's'));
$r = $CargaDes->Start();
	
if ($r['test'] == false) { echo $r['result'];}

//--- Отдаем файл на удаленный сервер со своего сервера
$server = new Server();
$CargaDes->setOnStart(new Exe($server, 'p'));//Код индикатора
echo $CargaDes->Start();

$server->remoteUrl = 'http://borivit.com/test/priem.php';
$server->realFilePath = dirname(  __FILE__ ) . file.zip;//Можно использовать массив файлов
$server->post = array( 'login' => 'test', 'pass' => '12345' );//Любые значения которые вы хотите передать на сервер

$CargaDes->setOnStart(new Exe($server, 'u'));
$r = $CargaDes->Start();
	
if ($r['test'] == false) { echo $r['result'];}

?>
```
