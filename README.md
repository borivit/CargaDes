# CargaDes
## Curl php class - этот класс предназначен для передачи файлов с помощью Curl.
Класс очень прост в использовании и ниже я приведу примеры его использования.

## License
Это программное обеспечение распространяется под лицензией [LGPL 3](http://www.gnu.org/licenses/gpl-3.0.html), вместе с [GPL Cooperation Commitment](https://gplcc.github.io/gplcc/). Пожалуйста, прочитайте ЛИЦЕНЗИЮ для получения информации о доступности и распространении программного обеспечения.

## Примеры использования CargaDes
```php
<?php

include cargades.class.php

//--- Скачивание файла с сервера через браузер
$realFilePath = dirname(  __FILE__ ) . file.zip;
new CargaDes( $realFilePath );

//--- Забераем файл с удаленного сервера на свой сервер
echo CargaDes::_serverProgress();

$remoteUrl = 'http://borivit.com/test/file.zip';
$realFilePath = dirname(  __FILE__ );

$result = CargaDes::_serverD($remoteUrl, $realFilePath);
	
if( $result != false ) {die('Error:'. $result);}

//--- Отдаем файл на удаленный сервер со своего сервера
echo CargaDes::_serverProgress();

$post_files = dirname(  __FILE__ ) . file.zip;//Можно использовать массив файлов
$post = array( 'login' => 'test', 'pass' => '12345' );//Любые значения которые вы хотите передать на сервер
$fileU = CargaDes::_serverFiles($post_files, $post);
	
if( !$fileU ) {die('Error array');}
	
$result = CargaDes::_serverU("http://borivit.com/test/priem.php",$fileU);
	
if( $result != false ) {die('Error:'. $result);}

//--- Загрузка файлов на сервер через браузер с индикацией процесса
echo CargaDes::_clientU("http://borivit.com/test/priem.php");

?>
```
