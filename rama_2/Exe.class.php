<?php

/*
-------------------------------------------------------------------------------------------
 | @copyright  Copyright (C) 2019 - 2020 Borys Nazarenkov. All rights reserved.           |
 | @license    GNU General Public License version 3 or later; see LICENSE.txt             |
 | @see        https://github.com/borivit/CargaDes/                                       |
-------------------------------------------------------------------------------------------
 | Файл: Exe.class.php
 | Назначение: Выполняет команд
-------------------------------------------------------------------------------------------
*/

class Exe implements Command
{
    private $com;
    private $arg_1;
    private $arg_2;

    /*******************************************
     * Инициализация переменных
     *******************************************************************
     * @param object $class_com - Экземпляр класса
     * @param string $arg_1 - Указатель типа команды
     * @param bool $arg_2 - Необязательный аргумент
     */
    public function __construct($class_com, $arg_1 = 's', $arg_2 = false)
    {
        $this->com = $class_com;
        $this->arg_1 = $arg_1;
        $this->arg_2 = $arg_2;
    }

    /*******************************************
     * Выполнение команд
     *******************************************************************
     * @return bool|string - Либо результат выполнения команды, либо ошибка типа команды
     */
    public function exe()
    {
        if ($this->arg_1 == 's' or $this->arg_1 == 'u') return $this->com->start($this->arg_1);
        if ($this->arg_1 == 'p') return $this->com->code($this->arg_2);
        return false;
    }
}