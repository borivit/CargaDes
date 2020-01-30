<?php

/*
-------------------------------------------------------------------------------------------
 | @copyright  Copyright (C) 2019 - 2020 Borys Nazarenkov. All rights reserved.           |
 | @license    GNU General Public License version 3 or later; see LICENSE.txt             |
 | @see        https://github.com/borivit/CargaDes/                                       |
-------------------------------------------------------------------------------------------
 | Файл: ClientD.class.php
 | Назначение: Отдает файл пользователю через браузер
-------------------------------------------------------------------------------------------
*/

class ClientD extends Tecno
{
    public $erroff = false;//Отключение вывода ошибок
    public $realFilePath;//Путь к отдаваемому файлу
    public $apach = false;//True отдача производится средствами Apache(должна быть включена директива XSendFile On), ограничение скорости отдачи в этом случае не работает
    public $speed = 0;//Скорость отдачи файла
    public $lang = 'ru';

    /*******************************************
     * Инициализация переменных
     *******************************************************************
     * @param $realFilePath - Путь к отдаваемому файлу
     * @param string $lang - Язык сообщений
     */
    public function __construct($realFilePath, $lang = 'ru')
    {
        $this->realFilePath = $realFilePath;
        $this->lang = $lang;
    }

    /*******************************************
     * Отдача файла с сервера с возможностью докачки
     *******************************************************************
     * @return bool|string - В случае ошибки выдаст сообшение или false в случае удачи
     */
    public function start()
    {

        if (!$fileCType = $this->mime_type($this->realFilePath)) {//Проверим, что файл существует и присвоим соотв. mime тип, иначе будет общий
            if (!$this->erroff) {
                die('<script>alert("' . $this->msg('error_2', $this->lang) . '\n' . $this->realFilePath . '");</script>');
            }
            return $this->msg('error_2', $this->lang) . '\n' . $this->realFilePath;
        }

        $CLen = filesize($this->realFilePath);//Размер файла
        $filename = basename($this->realFilePath); // запрашиваемое имя

        if (!$this->apach) {
            $rangePosition = $this->httpRange($filename, $fileCType, $CLen);// Формируем HTTP-заголовки ответа

            if (!$this->descargaFile($rangePosition)) {//Встаем на позицию $rangePosition и выдаем в поток содержимое файла
                if (!$this->erroff) {
                    die('<script>alert("' . $this->msg('error_1', $this->lang) . '");</script>');
                }
                return $this->msg('error_1', $this->lang);
            }
        } else {
            header('X-SendFile: ' . $this->realFilePath);
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
     * @param int $rangePosition - номер байта, c которого надо возобновить передачу содержимого файла
     * @return bool
     */
    private function descargaFile($rangePosition)
    {

        // теперь необходимо встать на позицию $rangePosition и выдать в поток содержимое файла
        $handle = @fopen($this->realFilePath, 'rb');

        if (!$handle) {
            return false;
        }

        $sleep_time = $this->speed ? (8 / $this->speed) * 1e6 : 0;

        fseek($handle, $rangePosition);

        while (!feof($handle) and !connection_status()) {
            print fread($handle, (1024 * 8));
            usleep($sleep_time);
        }

        fclose($handle);
        return true;
    }
}