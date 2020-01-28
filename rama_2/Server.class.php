<?php

/*******************************************
 * Отправляет/получает файлы между серверами.
 *******************************************************************
 */
class Server extends Tecno
{
    public $remoteUrl;//Путь к удаленному серверу.
    public $realFilePath;//Полный путь куда кладем скачанный файл или путь откуда берем файл для отправки(массив для отправки нескольких файлов)
    public $post = array();//Массив данных для передачи методом POST вместе с файлами
    public $progress = 1;//Подключение/отключение индикатора
    public $speed = 0;//Ограничение скорости
    public $style = './vendor/borivit/cargades/rama_2/css/style.cargades.css';//Подключение стиля
    public $color = '4098D3';//Цвет линии прогресса загрузки
    public $erroff = false;//Отключение вывода ошибок
    public $debug = false;//Отладка
    private $ch;
    public $lang = 'ru';

    /*******************************************
     * Инициализация переменных
     *******************************************************************
     * @param int $remoteUrl - Путь к удаленному серверу.
     * @param int $realFilePath - Полный путь куда/откуда кладем/берем скачанный/для отправки файл или (массив для отправки нескольких файлов)
     * @param string $lang - Язык сообщений
     */
    public function __construct($remoteUrl = 0, $realFilePath = 0, $lang = 'ru')
    {
        $this->remoteUrl = $remoteUrl;
        $this->realFilePath = $realFilePath;
        $this->lang = $lang;
    }


    /*******************************************
     * Скачивание/закачивание файлов
     *******************************************************************
     * @param $type
     * @return bool|string - Либо False, либо ошибка
     */
    public function start($type)
    {
        if ($type == 's') {
            return $this->serverD();
        } elseif ($type == 'u') {
            $fileU = $this->serverFiles();
            if (!$fileU) return $this->msg('error_4', $this->lang);
            return $this->serverU($fileU);
        }
        return true;
    }

    /*******************************************
     * Подключение индикатора прогресса
     *******************************************************************
     * @param string $idp - Префикс класса индикатора
     * @return string - готовый скрипт
     */
    public function code($idp = '')
    {
        if (!file_exists(dirname(__FILE__) . '/plantilla.pl')) {
            return $this->msg('error_3', $this->lang);
        }

        $pl = file_get_contents(dirname(__FILE__) . '/plantilla.pl');

        $this->setBlockUnhide('server');
        $this->setBlockHide('client_u');

        $this->set('{idp}', $idp);
        $this->set('{style}', $this->style);
        $this->set('{color}', $this->color);

        return $this->compilePl($pl);
    }

    /*******************************************
     * Инициализация Curl
     *******************************************************************
     * @param string $progressCallback - Функция прогресс-бара
     */
    public function curlInit($progressCallback)
    {
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_URL, $this->remoteUrl);
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

        //Обработка ошибок
        if ((curl_errno($this->ch) != 0 or $result === false) and !$this->erroff) {
            return array("result" => "<br>cURL Error: " . curl_error($this->ch) . " (" . curl_errno($this->ch) . ")", "test" => false);
        } elseif ($info['http_code'] != 200 and !$this->erroff) {
            return array("result" => "<br>HTTP-Error: " . $info['http_code'], "test" => false);
        } elseif ((curl_errno($this->ch) != 0 or $result === false or $info['http_code'] != 200) and $this->erroff) {
            return array("result" => false, "test" => false);
        }

        //Отладка
        if ($this->debug) {
            var_dump($result);
            echo '<br>';
            foreach ($info as $i => $key) {
                echo $i . ' -> ' . $key . '<br>';
            }
        }

        curl_close($this->ch);

        return array("result" => $result, "test" => "ok");
    }

    /*******************************************
     * Формируем файлы для отправки на сервер - CargaDes::_serverFiles();
     *******************************************************************
     * @return array|bool - Сформированный массив файлов
     */
    public function serverFiles()
    {
        $i = 0;
        $files = array();
        if (phpversion() < 5.5) {
            if (!is_array($this->realFilePath)) {
                if (!file_exists($this->realFilePath)) return false;
                $files = array('upload' => "@" . $this->realFilePath);
                $post = $this->post ? $this->post + $files : $files;
            } else {
                foreach ($this->realFilePath as $filePath) {
                    if (!file_exists($filePath)) return false;
                    $files['upload[' . $i++ . ']'] = "@" . $filePath;
                }
                $post = $this->post ? $this->post + $files : $files;
            }
        } else {
            if (!is_array($this->realFilePath)) {
                if (!file_exists($this->realFilePath)) return false;
                $files = array('upload' => curl_file_create($this->realFilePath, $this->mime_type($this->realFilePath), basename($this->realFilePath)));
                $post = $this->post ? $this->post + $files : $files;
            } else {
                foreach ($this->realFilePath as $filePath) {
                    if (!file_exists($filePath)) return false;
                    $files['upload[' . $i++ . ']'] = curl_file_create($filePath, $this->mime_type($filePath), basename($filePath));
                }
                $post = $this->post ? $this->post + $files : $files;
            }
        }
        return $post;
    }

    /*******************************************
     * Забераем файл с удаленного сервера на свой сервер - $CargaDes->serverD();
     *******************************************************************
     * @return bool|string - False успешное завершение или текст ошибки
     */
    public function serverD()
    {
        $progressCallback = false;
        if (phpversion() < 5.5 and $this->progress) {
            $progressCallback = function ($download_size, $downloaded_size, $upload_size, $uploaded_size) {
                $proc = @round($downloaded_size / $download_size * 100);
                if ($download_size > 0) echo '<script id="sct">updateProgress("' . $proc . '");</script>';
                flush();
                if ($proc >= 100) {
                    return;
                }
            };
        } elseif ($this->progress) {
            $progressCallback = function ($resource, $download_size, $downloaded_size, $upload_size, $uploaded_size) {
                $proc = @round($downloaded_size / $download_size * 100);
                if ($download_size > 0) echo '<script id="sct">updateProgress("' . $proc . '");</script>';
                flush();
                if ($proc >= 100) return;
            };
        }

        $fh = fopen($this->realFilePath, 'w');

        $this->curlInit($progressCallback);

        if ($this->speed) {
            curl_setopt($this->ch, CURLOPT_MAX_RECV_SPEED_LARGE, $this->speed);
        }

        $r = $this->curlExe();

        fwrite($fh, $r['result']);
        fclose($fh);

        return $r;
    }

    /*******************************************
     * Отдаем файл на удаленный сервер со своего сервера - $CargaDes->serverU($fileU);
     *******************************************************************
     * @param string/array  $fileU     - Массив для POST отправки.
     * @return bool|string - False успешное завершение или текст ошибки
     */

    public function serverU($fileU)
    {
        $progressCallback = false;
        if (phpversion() < 5.5) {
            if ($this->progress) {
                $progressCallback = function ($download_size, $downloaded_size, $upload_size, $uploaded_size) {
                    $proc = @round($uploaded_size / $upload_size * 100);
                    if ($upload_size > 0) echo '<script id="sct">updateProgress("' . $proc . '");</script>';
                    flush();
                    if ($proc >= 100) return;
                };
            }
        } else if ($this->progress) {
            $progressCallback = function ($resource, $download_size, $downloaded_size, $upload_size, $uploaded_size) {
                $proc = @round($uploaded_size / $upload_size * 100);
                if ($upload_size > 0) echo '<script id="sct">updateProgress("' . $proc . '");</script>';
                flush();
                if ($proc >= 100) return;
            };
        }

        $this->curlInit($progressCallback);

        if ($this->speed) {
            curl_setopt($this->ch, CURLOPT_MAX_SEND_SPEED_LARGE, $this->speed);
        }

        curl_setopt($this->ch, CURLOPT_POST, 1); // указываем метод POST
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $fileU);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0); // проверка peer для ssl отключена

        return $this->curlExe();
    }
}