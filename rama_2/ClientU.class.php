<?php

/*
-------------------------------------------------------------------------------------------
 | @copyright  Copyright (C) 2019 - 2020 Borys Nazarenkov. All rights reserved.           |
 | @license    GNU General Public License version 3 or later; see LICENSE.txt             |
 | @see        https://github.com/borivit/CargaDes/                                       |
-------------------------------------------------------------------------------------------
 | Файл: ClientU.class.php
 | Назначение: Формирует код для загрузки файла на сервер пользователем через боаузер
-------------------------------------------------------------------------------------------
*/

class ClientU extends Tecno
{
    public $url_server;//Путь к скрипту на сервере. Пример: "http://borivit.com/test/priem.php"
    public $multiples = false;//True множественная загрузка файлов, False загрузка по одному файлу
    public $param = 0;//Добавление данных в форму массивом в переменную param - сервер получит param=>array(ваш массив)
    public $ajaxParam = '';//Добавление данных в форму - data.append("key", "val");
    public $returns = 'console.log("DONE:200");';//Добавление действий после выполнения ajax
    public $err_file_zero;//Текст ошибки о пустом файле
    public $allowed_ext = 'gif,jpg,png,jpe,jpeg,zip,rar,exe,doc,pdf,swf,flv,avi,mp4,mp3';//Разрешенные расширения файлов
    public $err_file_ext;//Текст ошибки об отсуствии расширения в списке
    public $max_file_size = 0;//Ограничение на размер загружаемых файлов в байтах, по умолчанию отключено
    public $err_file_size;//Текст ошибки о привышении установленного размера файла
    public $max_file_count = 0;//Ограничение на количество загружаемых файлов, по умолчанию отключено
    public $btn_input;//Имя кнопки выбора файлов
    public $btn_enviar;//Имя кнопки отправки файлов
    public $btn_del;//Имя кнопки удаления файлов из очереди
    public $style = './vendor/borivit/cargades/rama_2/css/style.cargades.css';//Подключение стиля
    public $color = '4098D3';//Цвет линии прогресса загрузки
    public $debug = false;
    public $lang = 'ru';

    /*******************************************
     * Инициализация переменных
     *******************************************************************
     * @param $url_server - Путь к скрипту на сервере.
     * @param bool $multiple - множественная загрузка файлов
     * @param string $lang - Язык сообщений
     */
    public function __construct($url_server, $multiple = false, $lang = 'ru')
    {
        $this->url_server = $url_server;
        $this->multiples = $multiple ? 'multiple' : '';

        $this->param = !empty($this->param) ? json_encode($this->param) : 0;
        $this->err_file_zero = $this->msg('err_file_zero', $this->lang);
        $this->err_file_ext = $this->msg('err_file_ext', $this->lang);
        $this->err_file_ext = str_replace('{extensions}', $this->allowed_ext, $this->err_file_ext);
        $this->err_file_size = $this->msg('err_file_size', $this->lang);
        $this->err_file_size = str_replace('{sizeLimit}', $this->max_file_size . 'b', $this->err_file_size);
        $this->btn_input = $this->msg('btn_input', $this->lang);
        $this->btn_enviar = $this->msg('btn_enviar', $this->lang);
        $this->btn_del = $this->msg('btn_del', $this->lang);
        $this->lang = $lang;
    }

    /*******************************************
     * Загрузка файлов на сервер через браузер с индикацией процесса
     *******************************************************************
     * @return bool|string - готовый скрипт
     */

    public function code()
    {
        if (!file_exists(dirname(__FILE__) . '/plantilla.pl')) {
            return $this->msg('error_3', $this->lang);
        }

        $pl = file_get_contents(dirname(__FILE__) . '/plantilla.pl');

        $this->setBlockUnhide('client_u');
        $this->setBlockHide('server');

        $this->set('{url_server}', $this->url_server);
        $this->set('{multiples}', $this->multiples);
        $this->set('{param}', $this->param);
        $this->set('{ajaxParam}', $this->ajaxParam);
        $this->set('{returns}', $this->returns);
        $this->set('{err_file_zero}', $this->err_file_zero);
        $this->set('{allowed_ext}', $this->allowed_ext);
        $this->set('{err_file_ext}', $this->err_file_ext);
        $this->set('{max_file_size}', $this->max_file_size);
        $this->set('{err_file_size}', $this->err_file_size);
        $this->set('{max_file_count}', $this->max_file_count);
        $this->set('{btn_input}', $this->btn_input);
        $this->set('{btn_enviar}', $this->btn_enviar);
        $this->set('{btn_del}', $this->btn_del);
        $this->set('{style}', $this->style);
        $this->set('{color}', $this->color);
        $this->set('{debug}', !$this->debug ? 'false' : 'true');

        $result = $this->compilePl($pl);

        if (empty($result)) return false;
        return $result;

    }
}