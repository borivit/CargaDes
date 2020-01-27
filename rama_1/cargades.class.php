<?php
/*
-------------------------------------------------------------------------------------------
 | @copyright  Copyright (C) 2014 - 2020 Borys Nazarenkov. All rights reserved.           |
 | @license    GNU General Public License version 3 or later; see LICENSE.txt             |
 | @see        https://github.com/borivit/CargaDes/                                       |
-------------------------------------------------------------------------------------------
 | Файл: cargades.class.php
 | Назначение: Скачка/закачка файлов
-------------------------------------------------------------------------------------------
*/

class CargaDes
{

    public $debug = false;
    public $erroff = false;//Отключение вывода ошибок
    public $l = 0;//Логин отправляемый на сервер, если есть авторизация типа .htaccess
    public $p = 0;//Пароль отправляемый на сервер, если есть авторизация типа .htaccess
    public $ch;
    public $style = '/bss/cps/shkurka/Default/style/system/style.cargades.css';
    public $color = '4098D3';//Цвет линии прогресса загрузки

    public function __construct($debug = false, $erroff = false)
    {
        $this->debug = $debug;
        $this->erroff = $erroff;
    }

    /*******************************************
     * Отдача файла с сервера с возможностью докачки - new CargaDes($realFilePath, $speed);
     *******************************************************************
     * @param string $realFilePath - Путь к отдаваемому файлу
     * @param int $apach - True отдача производится средствами Apache
     *    (должна быть включена директива XSendFile On), ограничение скорости отдачи в этом случае не работает
     * @param int $speed - Скорость отдачи файла
     * @return bool|string - В случае ошибки выдаст сообшение или true в случае удачи
     */

    public function clientD($realFilePath, $apach = 0, $speed = 0)
    {

        if (!$fileCType = $this->mime_type($realFilePath)) {//Проверим, что файл существует и присвоим соотв. mime тип, иначе будет общий
            if (!$this->erroff) {
                die('<script>alert("Файл не существует!");</script>');
            }
            return "Файл не существует!";
        }

        $CLen = filesize($realFilePath);//Размер файла
        $filename = basename($realFilePath); // запрашиваемое имя

        if (!$apach) {
            $rangePosition = $this->httpRange($filename, $fileCType, $CLen);// Формируем HTTP-заголовки ответа

            if (!$this->descargaFile($realFilePath, $rangePosition, $speed)) {//Встаем на позицию $rangePosition и выдаем в поток содержимое файла
                if (!$this->erroff) {
                    die('<script>alert("Ошибка открытия файла!");</script>');
                }
                return "Ошибка открытия файла!";
            }
        } else {
            header('X-SendFile: ' . $realFilePath);
            header('Content-Type: ' . $fileCType);
            header('Content-Disposition: attachment; filename=' . $filename);
        }
        return false;
    }

    /*******************************************
     * Докачка файла
     *******************************************************************
     * $_SERVER['HTTP_RANGE'] — номер байта, c которого надо возобновить передачу содержимого файла.
     *    проверим, что заголовок Range: bytes=range- был послан браузером или менеджером закачек
     * @param string $filename - запрашиваемое имя
     * @param string $fileCType - mime тип файла
     * @param int $CLen - Размер файла
     * @return bool|int - номер байта, c которого надо возобновить передачу содержимого файла
     */
    private function httpRange($filename, $fileCType, $CLen)
    {

        if (isset($_SERVER['HTTP_RANGE'])) {
            $matches = array();
            if (preg_match('/bytes=(\d+)-/', $_SERVER['HTTP_RANGE'], $matches)) {
                $rangePosition = intval($matches[1]);
                $newCLen = $CLen - $rangePosition;
                header('HTTP/1.1 206 Partial content', true, 200);
                header('Status: 206 Partial content');
                $this->headerD($filename, $fileCType);
                header('Content-Range: bytes ' . $rangePosition . '-' . $CLen - 1 . '/' . $CLen);
                header('Content-Length: ' . $newCLen);
            } else {
                return false;
            }
        } else {
            header('HTTP/1.1 200 OK', true, 200);
            header('Status: 200 OK');
            $this->headerD($filename, $fileCType);
            header('Content-Length: ' . $CLen);
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
    private function headerD($filename, $fileCType)
    {
        // Last-Modified - Дата последнего изменения содержимого. Поле актуально только для
        // статических страниц. Apache заменяет это поле значением поля Date для динамически
        // генерируемых страниц, в том числе для страниц содержащих SSI.
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');// always modified
        // HTTP/1.1
        // Cache-control: no-cache - Управление кэшем. Значение no-cache определяет запрет кэша
        // данной страницы. Для версии протокола HTTP/1.0 действует "Pragma: no-cache".
        header('Cache-Control: no-store, no-cache, must-revalidate ');
        header('Cache-Control: post-check=0, pre-check=0', false);
        // HTTP/1.0
        header('Pragma: no-cache');
        header('Accept-Ranges: bytes');//Поддержка докачки
        header('Content-Disposition: attachment; filename="' . $filename . '"');//Указывает на скачиваемый контент;
        //большинство браузеров отображают диалог "Сохранить как" с заранее заполненным именем файла из параметра filename, если он задан.
        header('Content-Description: File Transfer');//
        header('Content-Type: ' . $fileCType);//тип файла
        header('Content-Transfer-Encoding: binary');// Означает, что никакой трансформации содержимого не производится
    }

    /*******************************************
     * Выдача в поток содержимого файла
     *******************************************************************
     * @param string $realFilePath - Путь к отдаваемому файлу
     * @param int $rangePosition - номер байта, c которого надо возобновить передачу содержимого файла
     * @param int $speed - Скорость отдачи файла
     * @return bool
     */
    private function descargaFile($realFilePath, $rangePosition, $speed)
    {

        // теперь необходимо встать на позицию $rangePosition и выдать в поток содержимое файла
        $handle = @fopen($realFilePath, 'rb');

        if (!$handle) {
            return false;
        }
        $sleep_time = $speed ? (8 / $speed) * 1e6 : 0;
        fseek($handle, $rangePosition);
        while (!feof($handle) and !connection_status()) {
            print fread($handle, (1024 * 8));
            usleep($sleep_time);
        }
        fclose($handle);
        return true;
    }

    /*******************************************
     * Возвращает MIME-тип содержимого файла и проверка на его существование - CargaDes::mime_type($realFilePath);
     *******************************************************************
     * @param string $realFilePath - Путь к проверяемому файлу.
     * @return bool|string - тип файла
     */
    public function mime_type($realFilePath)
    {

        if (!file_exists($realFilePath)) {
            return false;
        }

        if (!function_exists('mime_content_type')) {

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

            $ext = strtolower(array_pop(explode('.', $realFilePath)));
            if (array_key_exists($ext, $mime_types)) {
                return $mime_types[$ext];
            } elseif (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME);
                $mimetype = finfo_file($finfo, $realFilePath);
                finfo_close($finfo);
                return $mimetype;
            } else {
                return 'application/octet-stream';
            }
        } else {
            return mime_content_type($realFilePath);
        }
    }

    /*******************************************
     * Инициализация Curl
     *******************************************************************
     * @param string $remoteUrl - Путь к удаленному серверу.
     * @param string $progressCallback - Функция прогресс-бара
     */
    public function curlInit($remoteUrl, $progressCallback)
    {

        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_URL, $remoteUrl);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 1000);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);

        if (!empty($progressCallback)) {
            curl_setopt($this->ch, CURLOPT_NOPROGRESS, false);
            curl_setopt($this->ch, CURLOPT_PROGRESSFUNCTION, $progressCallback);
        }

        if (!empty($this->l) and !empty($this->p)) {
            curl_setopt($this->ch, CURLOPT_USERPWD, $this->l . ':' . $this->p);
        }

    }

    /*******************************************
     * Завершение Curl
     *******************************************************************
     * @return bool|string - Полученный результат
     */
    public function curlExe()
    {
        $result = curl_exec($this->ch);
        $info = curl_getinfo($this->ch);

        if ((curl_errno($this->ch) != 0 or $result === false) and !$this->erroff) {
            return "<br>cURL Error: " . curl_error($this->ch) . ' (' . curl_errno($this->ch) . ')';
        } elseif ($info['http_code'] != 200 and !$this->erroff) {
            return "<br>HTTP-Error: " . $info['http_code'];
        } elseif ((curl_errno($this->ch) != 0 or $result === false or $info['http_code'] != 200) and $this->erroff) {
            return false;
        }

        if ($this->debug) {//Отладка
            var_dump($result);
            echo '<br>';
            foreach ($info as $i => $key) {
                echo $i . ' -> ' . $key . '<br>';
            }
        }

        curl_close($this->ch);

        return $result;
    }

    /*******************************************
     * Забераем файл с удаленного сервера на свой сервер - $CargaDes->serverD($remoteUrl, $realFilePath, $progress, $speedS);
     *******************************************************************
     * @param string $remoteUrl - Путь к удаленному серверу.
     * @param string $realFilePath - Полный путь куда кладем скачанный файл
     * @param int $progress - Подключение индикатора
     * @param int $speedR - Ограничение скорости
     * @return bool - тип файла
     */
    public function serverD($remoteUrl, $realFilePath, $progress = 1, $speedR = 0)
    {
        $progressCallback = false;
        if (phpversion() < 5.5 and $progress) {
            $progressCallback = function ($download_size, $downloaded_size, $upload_size, $uploaded_size) {
                $proc = @round($downloaded_size / $download_size * 100);
                if ($download_size > 0) echo '<script id="sct">updateProgress("' . $proc . '");</script>';
                flush();
                if ($proc >= 100) {
                    return;
                }
            };
        } elseif ($progress) {
            $progressCallback = function ($resource, $download_size, $downloaded_size, $upload_size, $uploaded_size) {
                $proc = @round($downloaded_size / $download_size * 100);
                if ($download_size > 0) echo '<script id="sct">updateProgress("' . $proc . '");</script>';
                flush();
                if ($proc >= 100) return;
            };
        }

        $fh = fopen($realFilePath, 'w');

        $this->curlInit($remoteUrl, $progressCallback);

        if ($speedR) {
            curl_setopt($this->ch, CURLOPT_MAX_RECV_SPEED_LARGE, $speedR);
        }

        $result = $this->curlExe();

        fwrite($fh, $result);
        fclose($fh);

        return false;
    }

    /*******************************************
     * Отдаем файл на удаленный сервер со своего сервера - $CargaDes->serverU($remoteUrl, $fileU, $progress, $speedU);
     *******************************************************************
     * @param string $remoteUrl - Путь к удаленному серверу.
     * @param string/array  $fileU     - Массив для POST отправки.
     * @param int $progress - Подключение индикатора
     * @param int $speedS - Ограничение скорости
     * @return string
     */

    public function serverU($remoteUrl, $fileU, $progress = 1, $speedS = 0)
    {
        $progressCallback = false;
        if (phpversion() < 5.5) {
            if ($progress) {
                $progressCallback = function ($download_size, $downloaded_size, $upload_size, $uploaded_size) {
                    $proc = @round($uploaded_size / $upload_size * 100);
                    if ($upload_size > 0) echo '<script id="sct">updateProgress("' . $proc . '");</script>';
                    flush();
                    if ($proc >= 100) return;
                };
            }
        } else {
            if ($progress) {
                $progressCallback = function ($resource, $download_size, $downloaded_size, $upload_size, $uploaded_size) {
                    $proc = @round($uploaded_size / $upload_size * 100);
                    if ($upload_size > 0) echo '<script id="sct">updateProgress("' . $proc . '");</script>';
                    flush();
                    if ($proc >= 100) return;
                };
            }
        }

        $this->curlInit($remoteUrl, $progressCallback);

        if ($speedS) {
            curl_setopt($this->ch, CURLOPT_MAX_SEND_SPEED_LARGE, $speedS);
        }

        curl_setopt($this->ch, CURLOPT_POST, 1); // указываем метод POST
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $fileU);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0); // проверка peer для ssl отключена

        $result = $this->curlExe();

        $test = json_decode($result, true);

        if ($this->debug) {
            print_r($test);
        }//Отладка

        if ('error' == $test['e']) {
            return $test['e1'];
        } else {
            return false;
        }
    }

    /*******************************************
     * Формируем файлы для отправки на сервер - CargaDes::_serverFiles($realFilePath, $post);
     *******************************************************************
     * @param string/array    $realFilePath    - Полный путь к файлу.
     * @param array $post - Массив данных для передачи методом POST вместе с файлами
     * @return array|bool|string - Сформированный массив файлов
     */
    public function serverFiles($realFilePath, $post = array())
    {
        $i = 0;
        $files = array();
        if (phpversion() < 5.5) {
            if (!is_array($realFilePath)) {
                if (!file_exists($realFilePath)) return false;
                $files = array('upload' => "@" . $realFilePath);
                $post = $post ? $post + $files : $files;
            } else {
                foreach ($realFilePath as $filePath) {
                    if (!file_exists($filePath)) return false;
                    $files['upload[' . $i++ . ']'] = "@" . $filePath;
                }
                $post = $post ? $post + $files : $files;
            }
        } else {
            if (!is_array($realFilePath)) {
                if (!file_exists($realFilePath)) return false;
                $files = array('upload' => curl_file_create($realFilePath, $this->mime_type($realFilePath), basename($realFilePath)));
                $post = $post ? $post + $files : $files;
            } else {
                foreach ($realFilePath as $filePath) {
                    if (!file_exists($filePath)) return false;
                    $files['upload[' . $i++ . ']'] = curl_file_create($filePath, $this->mime_type($filePath), basename($filePath));
                }
                $post = $post ? $post + $files : $files;
            }
        }
        return $post;
    }

    /*******************************************
     * Подключение индикатора загрузки - CargaDes::_serverProgress();
     *******************************************************************
     * @param string $idp - Префикс класса индикатора
     * @return string - готовый скрипт
     */
    public function serverProgress($idp = '')
    {
        return '
		<link rel="stylesheet" type="text/css" href="' . $this->style . '?v=2" />
		<span class="server-progress-bar"><span class="server-progress' . $idp . '">0%</span></span>
		<script>
			var progress = document.querySelector(".server-progress' . $idp . '");
			function updateProgress(percentage){
				let computedStyle = getComputedStyle(progress);
				var width_css=computedStyle.width;
				width_css=width_css.replace("px","");
				var pb = width_css>100?Math.ceil(width_css/100):Math.ceil(100/width_css);
				var prc = percentage*pb;
				progress.style.boxShadow = "inset "+prc+"px 0px 0px #' . $this->color . '";
				progress.innerHTML = percentage + "%";
				document.getElementById("sct").remove();
			}
		</script>
		';
    }

    /*******************************************
     * Загрузка файлов на сервер через браузер с индикацией процесса - CargaDes::_clientU($url_server, $multiple);
     *******************************************************************
     * @param string $url_server - Путь к скрипту на сервере. Пример: "http://borivit.com/test/priem.php"
     * @param bool $multiple - True множественная загрузка файлов, False загрузка по одному файлу
     * @return - готовый скрипт
     */
    public $param = 0;//Добавление данных в форму массивом в переменную param - сервер получит param=>array(ваш массив)
    public $ajaxParam = '';//Добавление данных в форму - data.append("key", "val");
    public $returns = 'console.log("DONE:200");';//Добавление действий после выполнения ajax
    public $err_file_zero = 0;//Текст ошибки о пустом файле
    public $allowed_ext = 'gif,jpg,png,jpe,jpeg,zip,rar,exe,doc,pdf,swf,flv,avi,mp4,mp3';//Разрешенные расширения файлов
    public $err_file_ext = 0;//Текст ошибки об отсуствии расширения в списке
    public $max_file_size = 0;//Ограничение на размер загружаемых файлов в байтах, по умолчанию отключено
    public $err_file_size = 0;//Текст ошибки о привышении установленного размера файла
    public $max_file_count = 0;//Ограничение на количество загружаемых файлов, по умолчанию отключено
    public $btn_input = 0;//Имя кнопки выбора файлов
    public $btn_enviar = 0;//Имя кнопки отправки файлов
    public $btn_del = 0;//Имя кнопки удаления файлов из очереди

    public function clientU($url_server, $multiple = 0)
    {
        $multiple = $multiple ? 'multiple' : '';
        $param = !$this->param ? json_encode($this->param) : 0;
        $err_file_zero = !$this->err_file_zero ? "Файл {file} пустой, выберите файлы повторно." : $this->err_file_zero;
        $err_file_ext = !$this->err_file_ext ? "Файл {file} имеет неверное расширение. Только {extensions} разрешены к загрузке." : $this->err_file_ext;
        $err_file_ext = str_replace('{extensions}', $this->allowed_ext, $err_file_ext);
        $err_file_size = !$this->err_file_size ? "Файл {file} слишком большого размера, максимально допустимый размер файлов: {sizeLimit}." : $this->err_file_size;
        $err_file_size = str_replace('{sizeLimit}', $this->max_file_size . 'b', $err_file_size);
        $btn_input = !$this->btn_input ? 'Выбор файлов для загрузки' : $this->btn_input;
        $btn_enviar = !$this->btn_enviar ? 'Загрузить' : $this->btn_enviar;
        $btn_del = !$this->btn_del ? 'x' : $this->btn_del;

        return '
		<link rel="stylesheet" type="text/css" href="' . $this->style . '?v=2" />
		<script>
			function RemovE(id){
				document.getElementById(id).remove();
				delete document.getElementById("file-container").file[id];
			}
			function in_array(what, where) {
				for(var i=0; i<where.length; i++) {
					if(what == where[i]) {return true;}
				}
				return false;
			}
			function fadeOut(el,func) {
  
				var opacity = 1, f = "";
				if(func !== "undefined"){f = func;}
				
				var timer = setInterval(function() {
				
					if(opacity <= 0.3) {
					
						clearInterval(timer);
						document.getElementById(el).style.display = "none";
				
					}
				
					document.getElementById(el).style.opacity = opacity;
				 
					opacity -= opacity * 0.1;
			   
				}, 100);
				f
			}
			
			var arr = JSON.stringify(' . $param . ');
			//console.log(arr);
			
			document.addEventListener("DOMContentLoaded", function(){
				var input = document.getElementById("file-input");
				var button = document.getElementById("file-submit");
				var container = document.getElementById("file-container");
				var err_file_size = "' . $err_file_size . '";
				var err_file_ext = "' . $err_file_ext . '";
				var err_file_zero = "' . $err_file_zero . '";
				var debug = {debug};
					container.file = {};
				
				input.onchange = function(e){
					var FR = new FileReader();
					var files = e.target.files;
					
					for(var file of files){
						if(document.getElementsByClassName("file-info").length>=' . $this->max_file_count . ' && ' . $this->max_file_count . '>0) break;
						if(file.size!=0 && (file.size<' . $this->max_file_size . ' || ' . $this->max_file_size . '==0)){
							var id_name = [];
							id_name = file.name.split(".");
							var ext = "' . $this->allowed_ext . '";
							ext = ext.split(",");
							if(in_array(id_name[1], ext) == true){
								
								if(container.file.hasOwnProperty(id_name[0]) == false){
									container.innerHTML = "<div id=\""+id_name[0]+"\" class=\"file-info\"><span class=\"btn-del\" onclick=\"RemovE(\'"+id_name[0]+"\');\">' . $btn_del . '</span><span class=\"file-name\">"+file.name+"</span><span class=\"client-progress-bar\"><span class=\"client-progress\">0%</span></span></div>"+container.innerHTML;
									
									container.file[id_name[0]] = file;
								}
								
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
				};
				
				button.onclick = async function(evt){
					evt.preventDefault();
					var arr_files = [];
					arr_files = container.file;
					
					for (var key in arr_files){upload(key,arr_files);}
					console.log("Готово");
					
				};
				function upload(key, file){
					var data = new FormData();
					data.append("file", file[key]);
					
					' . $this->ajaxParam . '
					if(' . $param . '!=0) data.append("param",arr);
					
					var progress = document.getElementById(key).querySelector(".client-progress");
					let computedStyle = getComputedStyle(progress);
					var width_css=computedStyle.width;
					
					width_css=width_css.replace("px","");
					var pb = width_css>100?Math.ceil(width_css/100):Math.ceil(100/width_css);
					
					var xhr = new XMLHttpRequest();
					xhr.open("POST", "' . $url_server . '");
					
					xhr.upload.onprogress = function(evt){
						var percent = Math.ceil(evt.loaded / evt.total * 100);
						var prc = percent*pb;
						progress.style.boxShadow = "inset "+prc+"px 0px 0px #' . $this->color . '";
						progress.innerHTML = percent + "%";
					}
					
					xhr.onload = function () {
						if(debug == true){document.getElementById("file-form").innerHTML += "<br>responseText:<br>"+xhr.responseText;}
						if(xhr.status == "200"){' . $this->returns . '}
					};
					
					xhr.send(data);
					
				}
			});
		</script>
		<form id="file-form" method="post">
			<input class="btn-input" type="button" onclick="document.getElementById(\'file-input\').click();return false;" value="' . $btn_input . '">
			<input type="file" id="file-input" ' . $multiple . ' style="display:none;">
			<input class="btn-enviar" type="button" id="file-submit" value="' . $btn_enviar . '"/><br/>
			<div class="file-container" id="file-container"></div>
		</form>
		';
    }
}
