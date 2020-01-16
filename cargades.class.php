<?php
/*
-------------------------------------------------------------------------------------------
 | @copyright  Copyright (C) 2014 - 2020 Borys Nazarenkov. All rights reserved. 	|
 | @license    GNU General Public License version 3 or later; see LICENSE.txt           |
 | @see        https://github.com/borivit/CargaDes/				        |
-------------------------------------------------------------------------------------------
 | Файл: cargades.class.php
 | Назначение: Скачка/закачка файлов
-------------------------------------------------------------------------------------------
*/

class CargaDes {
	
	public static $debug = false;
	public static $l = 0;//login
	public static $p = 0;//Pass
	
	/*******************************************
	 * Отдача файла с сервера с возможностью докачки - new CargaDes($realFilePath, $speed);
	 *******************************************************************
	 * @param string $realFilePath - Путь к отдаваемому файлу
	 * @param int $apach - True отдача производится средствами Apache
	 *	(должна быть включена директива XSendFile On), ограничение скорости отдачи в этом случае не работает
	 * @param int $speed - Скорость отдачи файла
	 * @return - В случае ошибки выдаст сообшение или true в случае удачи
	 */
	public function __construct($realFilePath, $apach=0, $speed=0){
		
		if( !$fileCType = self::mime_type($realFilePath) ){//Проверим, что файл существует и присвоим соотв. mime тип, иначе будет общий
			die('<script>alert("Файл не существует!");</script>');
			return false;
		}
		
		$CLen = filesize($realFilePath);//Размер файла
		$filename = basename($realFilePath); // запрашиваемое имя
		
		$rangePosition = self::httpRange($filename, $fileCType, $CLen);// Формируем HTTP-заголовки ответа
		
		if( !$apach ){
			$rangePosition = self::httpRange($filename, $fileCType, $CLen);// Формируем HTTP-заголовки ответа
			
			if( !self::descargaFile($realFilePath, $rangePosition, $speed) ){//Встаем на позицию $rangePosition и выдаем в поток содержимое файла
				die('<script>alert("Ошибка открытия файла!");</script>');
				return false;
			}
		}else{
			header('X-SendFile: '. $realFilePath);
			header('Content-Type: '. $fileCType);
			header('Content-Disposition: attachment; filename='. $filename);
			exit;
		}
		return true;
	}
	
	/*******************************************
	 * Докачка файла
	 *******************************************************************
	 * $_SERVER['HTTP_RANGE'] — номер байта, c которого надо возобновить передачу содержимого файла.
	 *	проверим, что заголовок Range: bytes=range- был послан браузером или менеджером закачек
	 * @param string $filename - запрашиваемое имя
	 * @param string $fileCType - mime тип файла
	 * @param int $CLen - Размер файла
	 * @return - номер байта, c которого надо возобновить передачу содержимого файла
	 */
	private function httpRange($filename, $fileCType, $CLen){
		
		if( isset($_SERVER['HTTP_RANGE']) ){
			$matches = array();
			if( preg_match('/bytes=(\d+)-/', $_SERVER['HTTP_RANGE'], $matches) ){
				$rangePosition = intval($matches[1]);
				$newCLen = $CLen - $rangePosition;
				header ( 'HTTP/1.1 206 Partial content', true, 200 );
				header ( 'Status: 206 Partial content' );
				self::headerD($filename, $fileCType);
				header ( 'Content-Range: bytes ' . $rangePosition . '-' . $CLen - 1 . '/' . $CLen);
				header ( 'Content-Length: ' . $newCLen );
			}else {return false;}
		}else{
			header ( 'HTTP/1.1 200 OK', true, 200 );
			header ( 'Status: 200 OK' );
			self::headerD($filename, $fileCType);
			header ( 'Content-Length: ' . $CLen );
			$rangePosition = 0;
		}
		return $rangePosition;
	}
	
	/*******************************************
	 * Формируем основные HTTP-заголовки ответа
	 *******************************************************************
	 * @param string $filename - запрашиваемое имя
	 * @param string $fileCType - mime тип файла 
	 */
	private function headerD($filename, $fileCType){
		// Last-Modified - Дата последнего изменения содержимого. Поле актуально только для
		// статических страниц. Apache заменяет это поле значением поля Date для динамически
		// генерируемых страниц, в том числе для страниц содержащих SSI.
		header ( 'Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');// always modified
		// HTTP/1.1
		// Cache-control: no-cache - Управление кэшем. Значение no-cache определяет запрет кэша
		// данной страницы. Для версии протокола HTTP/1.0 действует "Pragma: no-cache".
		header ( 'Cache-Control: no-store, no-cache, must-revalidate ');
		header ( 'Cache-Control: post-check=0, pre-check=0', false);
		// HTTP/1.0
		header ( 'Pragma: no-cache' );
		header ( 'Accept-Ranges: bytes');//Поддержка докачки
		header ( 'Content-Disposition: attachment; filename="' . $filename . '"' );//Указывает на скачиваемый контент; 
		//большинство браузеров отображают диалог "Сохранить как" с заранее заполненным именем файла из параметра filename, если он задан.
		header ( 'Content-Description: File Transfer' );//
		header ( 'Content-Type: ' . $fileCType );//тип файла
		header ( 'Content-Transfer-Encoding: binary');// Означает, что никакой трансформации содержимого не производится
	}
	
	/*******************************************
	 * Выдача в поток содержимого файла
	 *******************************************************************
	 * @param string $realFilePath - Путь к отдаваемому файлу
	 * @param int $rangePosition - номер байта, c которого надо возобновить передачу содержимого файла
	 * @param int $speed - Скорость отдачи файла
	 * @return bool
	 */
	private function descargaFile($realFilePath, $rangePosition, $speed){
		
		// теперь необходимо встать на позицию $rangePosition и выдать в поток содержимое файла
		if( $handle = @fopen($realFilePath, 'rb') ){
			$sleep_time = $speed?(8 / $speed) * 1e6:0;
			fseek($handle, $rangePosition);
			while( !feof($handle) and !connection_status() ){
				print fread($handle, (1024 * 8));
				usleep( $sleep_time );
			}
			fclose($handle);
			return true;
		}else{
			return false;
		}
	}
	
	/*******************************************
	 * Возвращает MIME-тип содержимого файла и проверка на его существование - CargaDes::mime_type($realFilePath);
	 *******************************************************************
	 * @param string $realFilePath - Путь к проверяемому файлу.
	 * @return - тип файла
	 */
	public static function mime_type($realFilePath){
		
		if( !function_exists('mime_content_type') and file_exists($realFilePath) ){

			$mime_types = array(

				'txt' => 'text/plain',
				'htm' => 'text/html',
				'html' => 'text/html',
				'php' => 'text/html',
				'css' => 'text/css',
				'js' => 'application/javascript',
				'json' => 'application/json',
				'xml' => 'application/xml',
				'swf' => 'application/x-shockwave-flash',
				'flv' => 'video/x-flv',

				// images
				'png' => 'image/png',
				'jpe' => 'image/jpeg',
				'jpeg' => 'image/jpeg',
				'jpg' => 'image/jpeg',
				'gif' => 'image/gif',
				'bmp' => 'image/bmp',
				'ico' => 'image/vnd.microsoft.icon',
				'tiff' => 'image/tiff',
				'tif' => 'image/tiff',
				'svg' => 'image/svg+xml',
				'svgz' => 'image/svg+xml',

				// archives
				'zip' => 'application/x-zip-compressed',
				'rar' => 'application/x-rar-compressed',
				'exe' => 'application/x-msdownload',
				'msi' => 'application/x-msdownload',
				'cab' => 'application/vnd.ms-cab-compressed',

				// audio/video
				'mp3' => 'audio/mpeg',
				'qt' => 'video/quicktime',
				'mov' => 'video/quicktime',

				// adobe
				'pdf' => 'application/pdf',
				'psd' => 'image/vnd.adobe.photoshop',
				'ai' => 'application/postscript',
				'eps' => 'application/postscript',
				'ps' => 'application/postscript',

				// ms office
				'doc' => 'application/msword',
				'rtf' => 'application/rtf',
				'xls' => 'application/vnd.ms-excel',
				'ppt' => 'application/vnd.ms-powerpoint',

				// open office
				'odt' => 'application/vnd.oasis.opendocument.text',
				'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
			);

			$ext = strtolower(array_pop(explode('.',$realFilePath)));
			if(array_key_exists($ext, $mime_types)){
				return $mime_types[$ext];
			}elseif(function_exists('finfo_open')){
				$finfo = finfo_open(FILEINFO_MIME);
				$mimetype = finfo_file($finfo, $realFilePath);
				finfo_close($finfo);
				return $mimetype;
			}else{
				return 'application/octet-stream';
			}
		}else{
			if( file_exists($realFilePath) ) {return mime_content_type($realFilePath);}
			else {return false;}
			
		}
	}

	/*******************************************
	 * Забераем файл с удаленного сервера на свой сервер - CargaDes::_serverD($remoteUrl, $realFilePath, $progress, $login, $pass, $speedS);
	 *******************************************************************
	 * @param string 	$remoteUrl 	- Путь к удаленному серверу.
	 * @param string 	$realFilePath 	- Полный путь куда кладем скачанный файл
	 * @param int 		$progress	- Подключение индикатора
	 * @param string	$login		- Логин отправляемый на сервер
	 * @param string	$pass		- Пароль отправляемый на сервер
	 * @param int 		$speedS		- Ограничение скорости
	 * @return - тип файла
	 */
	public static function _serverD($remoteUrl, $realFilePath, $progress=1, $login=0, $pass=0, $speedS=0){
		$login = !$login?self::$l:$login;
		$pass = !$pass?self::$p:$pass;
		
		if( phpversion() < 5.5 and $progress ){
			$progressCallback = function( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
				$proc = @round($downloaded_size / $download_size  * 100);
				if($download_size > 0) echo '<script id="sct">updateProgress("'.$proc.'");</script>';flush();
				//if( $download_size <= 1500000 ) usleep(30000); // just to see effect
				if( $proc >= 100 ) return;
			};
		}elseif( $progress ){
			$progressCallback = function( $resource, $download_size, $downloaded_size, $upload_size, $uploaded_size ){
				$proc = @round($downloaded_size / $download_size  * 100);
				if($download_size > 0) echo '<script id="sct">updateProgress("'.$proc .'");</script>';flush();
				//if( $download_size <= 1500000 ) usleep(30000); // just to see effect
				if( $proc >= 100 ) return;
			};
		}
				
		$fh = fopen($realFilePath, 'w');
		
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $remoteUrl );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 1000);
		if( $progress ){
			curl_setopt( $ch, CURLOPT_NOPROGRESS, false );
			curl_setopt( $ch, CURLOPT_PROGRESSFUNCTION, $progressCallback );
		}
		if( $login ) {curl_setopt( $ch, CURLOPT_USERPWD, $login.':'.$pass);}
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true);
		if( $speedS ) {curl_setopt( $ch, CURLOPT_MAX_RECV_SPEED_LARGE, $speedS );}
		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		if( curl_errno($ch) != 0 or $result === false ){
			return "<br>cURL Error: " . curl_error($ch).' ('.curl_errno($ch).')';
		}elseif( $info['http_code'] != 200 ) {return "<br>HTTP-Error: ".$info['http_code'];}
		
		if( self::$debug ){//Отладка
			var_dump($result);
			echo '<br>';
			foreach( $info as $i=>$key ) {echo $i .' -> '. $key .'<br>';}
		}
		
		curl_close($ch);
		
		fwrite($fh, $result);
		fclose($fh);
		return false;
	}
	
	/*******************************************
	 * Отдаем файл на удаленный сервер со своего сервера - CargaDes::_serverU($remoteUrl, $fileU, $progress, $login, $pass, $speedU);
	 *******************************************************************
	 * @param string	$remoteUrl 	- Путь к удаленному серверу.
	 * @param string/array 	$fileU		- Массив для POST отправки.
	 * @param int 		$progress	- Подключение индикатора
	 * @param string	$login		- Логин отправляемый на сервер, если есть авторизация типа .htaccess
	 * @param string	$pass		- Пароль отправляемый на сервер, если есть авторизация типа .htaccess
	 * @param int 		$speedU		- Ограничение скорости
	 * @return false или текст ошибки
	 */
	
	public static function _serverU($remoteUrl, $fileU, $progress=1, $login=0, $pass=0, $speedU=0){
		$login = !$login?self::$l:$login;
		$pass = !$pass?self::$p:$pass;
		
		if( phpversion() < 5.5 ){
			if( $progress ){
				$progressCallback = function( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
					$proc = @round($uploaded_size / $upload_size  * 100);
					if($upload_size > 0) echo '<script id="sct">updateProgress("'.$proc.'");</script>';flush();
					if( $upload_size <= 1500000 ) usleep(30000); // just to see effect
					if( $proc >= 100 ) return;
				};
			}
		}else{
			if( $progress ){
				$progressCallback = function( $resource, $download_size, $downloaded_size, $upload_size, $uploaded_size ){
					$proc = @round($uploaded_size / $upload_size  * 100);
					if($upload_size > 0) echo '<script id="sct">updateProgress("'.$proc.'");</script>';flush();
					if( $upload_size <= 1500000 ) usleep(30000); // just to see effect
					if( $proc >= 100 ) return;
				};
			}
		}
		
		
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $remoteUrl );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 1000 );
		if( $progress ){
			curl_setopt( $ch, CURLOPT_NOPROGRESS, false );
			curl_setopt( $ch, CURLOPT_PROGRESSFUNCTION, $progressCallback );
		}
		if( $speedU ) {curl_setopt( $ch, CURLOPT_MAX_RECV_SPEED_LARGE, $speedU );}
		if( $login ) {curl_setopt( $ch, CURLOPT_USERPWD, $login.':'.$pass);}//Для авторизации типа .htaccess
		curl_setopt( $ch, CURLOPT_POST, 1 ); // указываем метод POST
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $fileU );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 ); // проверка peer для ssl отключена
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );//Скрипт будет следовать за редиректами происходящими во время авторизации
		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		if( curl_errno($ch) != 0 ){
			return "<br>cURL Error: " . curl_error($ch).' ('.curl_errno($ch).')';
		}elseif( $info['http_code'] != 200 ) return "<br>HTTP-Error: ".$info['http_code'];
		
		if( self::$debug ){//Отладка
			var_dump($result);
			echo '<br>';
			foreach( $info as $i=>$key ) {echo $i .' -> '. $key .'<br>';}
		}
		
		curl_close($ch);
		$test = json_decode($result, true);
		
		if( self::$debug ) {print_r($test);}//Отладка
		
		if( 'error' == $test['e'] ) {return $test['e1'];}
		else {return false;}
	}
	
	/*******************************************
	 * Формируем файлы для отправки на сервер - CargaDes::_serverFiles($realFilePath, $post);
	 *******************************************************************
	 * @param string/array 	$realFilePath	- Полный путь к файлу.
	 * @param string	$post		- Массив данных для передачи методом POST вместе с файлами
	 * @return - Сформированный массив файлов
	 */
	public static function _serverFiles($realFilePath, $post=array()){
		$i=0;
		if( phpversion() < 5.5 ){
			if( !is_array($realFilePath) ){
				if( !file_exists($realFilePath) ) return false;
				$files = array( 'upload' => "@".$realFilePath );
				$post = $post?$post+$files:$files;
			}else{
				foreach( $realFilePath as $filePath ){
					if( !file_exists($filePath) ) return false;
					$files['upload['.$i++.']'] = "@".$filePath;
				}
				$post = $post?$post+$files:$files;
			}
		}else{
			if( !is_array($realFilePath) ){
				if( !file_exists($realFilePath) ) return false;
				$files = array( 'upload' => curl_file_create($realFilePath, self::mime_type($realFilePath), basename($realFilePath)) );
				$post = $post?$post+$files:$files;
			}else{
				foreach( $realFilePath as $filePath ){
					if( !file_exists($filePath) ) return false;
					$files['upload['.$i++.']'] = curl_file_create($filePath, self::mime_type($filePath), basename($filePath));
				}
				$post = $post?$post+$files:$files;
			}
		}
		return $post;
	}
	/*******************************************
	 * Подключение индикатора загрузки - CargaDes::_serverProgress();
	 *******************************************************************
	 * @param string	$style	- Имя файла стилей или False - файл стилей нужно грузить отдельно 
	 * @param string	$idp	- Префикс класса индикатора
	 * @param string	$color	- Цвет линии прогресса загрузки
	 * @return - готовый скрипт
	 */
	public static function _serverProgress($style='style.cargades.css', $idp='', $color='4098D3'){
		$style = $style?'<link rel="stylesheet" type="text/css" href="'.$style.'?v=2" />':'';
		return $style . '
		<span class="server-progress-bar"><span class="server-progress'.$idp.'">0%</span></span>
		<script>
			var progress = $(".server-progress'.$idp.'");
			function updateProgress(percentage){
				var width_css=progress.css("width");
				width_css=width_css.replace("px","");
				var pb = width_css>100?Math.ceil(width_css/100):Math.ceil(100/width_css);
				var prc = percentage*pb;
				progress.css("box-shadow","inset "+prc+"px 0px 0px #'.$color.'");
				progress.html(percentage + "%");
				document.getElementById("sct").remove();
			}
		</script>
		';
	}
	
	/*******************************************
	 * Загрузка файлов на сервер через браузер с индикацией процесса - CargaDes::_clientU($url_server, $multiple);
	 *******************************************************************
	 * @param string $url_server 	- Путь к скрипту на сервере. Пример: "http://borivit.com/test/priem.php"
	 * @param bool 	 $multiple 	- True множественная загрузка файлов, False загрузка по одному файлу
	 * @return - готовый скрипт
	 */
	public static $param = 0;//Добавление данных в форму массивом в переменную param - сервер получит param=>array(ваш массив)
	public static $ajaxParam = '';//Добавление данных в форму - data.append("key", "val");
	public static $return = '';//Добавление действий после выполнения ajax
	public static $err_file_zero = 0;//Текст ошибки о пустом файле
	public static $allowed_ext = 'gif,jpg,png,jpe,jpeg,zip,rar,exe,doc,pdf,swf,flv,avi,mp4,mp3';//Разрешенные расширения файлов
	public static $err_file_ext = 0;//Текст ошибки об отсуствии расширения в списке
	public static $max_file_size = 0;//Ограничение на размер загружаемых файлов в байтах, по умолчанию отключено
	public static $err_file_size = 0;//Текст ошибки о привышении установленного размера файла
	public static $max_file_count = 0;//Ограничение на количество загружаемых файлов, по умолчанию отключено
	public static $btn_input = 0;//Имя кнопки выбора файлов
	public static $btn_enviar = 0;//Имя кнопки отправки файлов
	public static $btn_del = 0;//Имя кнопки удаления файлов из очереди
	public static $color = 0;//Цвет линии прогресса загрузки
	public static $css = 0;//Путь к файлу стилей
	
	public static function _clientU($url_server, $multiple=0){
		
		$multiple = $multiple?'multiple':'';
		$param = !self::$param?json_encode(self::$param):0;
		$err_file_zero = !self::$err_file_zero?"Файл {file} пустой, выберите файлы повторно.":self::$err_file_zero;
		$err_file_ext = !self::$err_file_ext?"Файл {file} имеет неверное расширение. Только {extensions} разрешены к загрузке.":self::$err_file_ext;
		$err_file_ext = str_replace('{extensions}', self::$allowed_ext, $err_file_ext);
		$err_file_size = !self::$err_file_size?"Файл {file} слишком большого размера, максимально допустимый размер файлов: {sizeLimit}.":self::$err_file_size;
		$err_file_size = str_replace('{sizeLimit}', self::$max_file_size.'b', $err_file_size);
		$btn_input = !self::$btn_input?'Выбор файлов для загрузки':self::$btn_input;
		$btn_enviar = !self::$btn_enviar?'Загрузить':self::$btn_enviar;
		$btn_del = !self::$btn_del?'x':self::$btn_del;
		$color = !self::$color?'4098D3':self::$color;
		$css = !self::$css?'/':self::$css;
		
		return '
		<link rel="stylesheet" type="text/css" href="'.$css.'style.cargades.css?v=2" />
		<script>
			function RemovE(id){
				document.getElementById(id).remove();
				$("#file-input")[0].value = "";
			}
			
			var arr = JSON.stringify('.$param.');
			console.log(arr);
			
			$(document).ready(function(){
				var input = $("#file-input");
				var button = $("#file-submit");
				var container = $("#file-container");
				var err_file_size = "'.$err_file_size.'";
				var err_file_ext = "'.$err_file_ext.'";
				var err_file_zero = "'.$err_file_zero.'";
				
				input.on("change", function(){
					var files = $(this).prop("files");
					for(var file of files){
						if($(".file-info").length>='.self::$max_file_count.' && '.self::$max_file_count.'>0) break;
						if(file.size!=0 && (file.size<'.self::$max_file_size.' || '.self::$max_file_size.'==0)){
							var id_name = [];
							id_name = file.name.split(".");
							var ext = "'.self::$allowed_ext.'";
							ext = ext.split(",");
							if(jQuery.inArray(id_name[1], ext) !== -1){
								var elem = $("<div id=\""+id_name[0]+"\" class=\"file-info\"><span class=\"btn-del\" onclick=\"RemovE(\'"+id_name[0]+"\');\">'.$btn_del.'</span><span class=\"file-name\">"+file.name+"</span><span class=\"client-progress-bar\"><span class=\"client-progress\">0%</span></span></div>").appendTo(container);
								elem.get(0).file = file;
							}else{
								var err_ext = err_file_ext.replace("{file}", file.name);
								alert(err_ext);
							}
						}else{
							var err_file = err_file_size.replace("{file}", file.name);
							if(file.size==0) err_file = err_file_zero.replace("{file}", file.name);
							alert(err_file);
						}
					}
				});
				
				button.on("click", async function(evt){
					evt.preventDefault();
					await $(".file-info").map(upload);
					console.log("Готово");
					
				});
				async function upload(index, elem){
					var data = new FormData();
					data.append("file", elem.file);
					'.self::$ajaxParam.'
					if('.$param.'!=0) data.append("param",arr);
					var progress = $(elem).find(".client-progress");
					var width_css=progress.css("width");
					width_css=width_css.replace("px","");
					var pb = width_css>100?Math.ceil(width_css/100):Math.ceil(100/width_css);
					
					const res = await $.ajax({
						url: "'.$url_server.'",
						contentType: false,
						processData: false,
						//dataType: "json",
						data: data,
						type: "post",
						xhr: function(){
							var xhr = new XMLHttpRequest();
							xhr.upload.onprogress = function(evt){
								var percent = Math.ceil(evt.loaded / evt.total * 100);
								var prc = percent*pb;
								progress.css("box-shadow","inset "+prc+"px 0px 0px #'.$color.'");
								progress.html(percent + "%");
							}
						return xhr;
						}'.self::$return.'
					});
				}
			});
		</script>
		<form id="file-form" method="post">
			<input class="btn-input" type="button" onclick="$(function(){$(\'#file-input\').click();return false;});" value="'.$btn_input.'">
			<input type="file" id="file-input" '.$multiple.' style="display:none;">
			<input class="btn-enviar" type="button" id="file-submit" value="'.$btn_enviar.'"/><br/>
			<div class="file-container" id="file-container"></div>
		</form>
		';
	}
}

?>
