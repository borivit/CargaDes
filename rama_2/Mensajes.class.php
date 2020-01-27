<?php

/*******************************************
 * Класс сообщений.
 *******************************************************************
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
     * @return string
     */
    public function msg($var, $lng)
    {
        return $this->idioma[$lng][$var];
    }

}