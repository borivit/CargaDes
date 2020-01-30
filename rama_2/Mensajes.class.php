<?php

/*
-------------------------------------------------------------------------------------------
 | @copyright  Copyright (C) 2019 - 2020 Borys Nazarenkov. All rights reserved.           |
 | @license    GNU General Public License version 3 or later; see LICENSE.txt             |
 | @see        https://github.com/borivit/CargaDes/                                       |
-------------------------------------------------------------------------------------------
 | Файл: Mensajes.class.php
 | Назначение: Выдает нужное сообщение на нужном языке
-------------------------------------------------------------------------------------------
*/

class Mensajes
{
    public $idioma = array(
        "ru" => array(
            "error_1" => "Ошибка открытия файла!",
            "error_2" => "Файл не существует!",
            "error_3" => "Файл шаблона отсуствует!",
            "error_4" => "Массив файлов не создан.",
            "err_file_zero" => "Файл {file} пустой, выберите файлы повторно.",
            "err_file_ext" => "Файл {file} имеет неверное расширение. Только {extensions} разрешены к загрузке.",
            "err_file_size" => "Файл {file} слишком большого размера, максимально допустимый размер файлов: {sizeLimit}.",
            "btn_input" => "Выбор файлов для загрузки",
            "btn_enviar" => "Загрузить",
            "btn_del" => "x"
        )
    );

    /*******************************************
     * Вывод сообщений.
     *******************************************************************
     * @param $var - Псевдоним сообщения
     * @param $lng - Язык сообщения
     * @return string - Сообщение на нужном языке
     */
    public function msg($var, $lng)
    {
        if (empty($this->idioma[$lng][$var])) return "error-" . $var;
        return $this->idioma[$lng][$var];
    }

}