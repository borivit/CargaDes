<?php

/*
-------------------------------------------------------------------------------------------
 | @copyright  Copyright (C) 2019 - 2020 Borys Nazarenkov. All rights reserved.           |
 | @license    GNU General Public License version 3 or later; see LICENSE.txt             |
 | @see        https://github.com/borivit/CargaDes/                                       |
-------------------------------------------------------------------------------------------
 | Файл: CargaDes.class.php
 | Назначение: Управляет запуском команд
-------------------------------------------------------------------------------------------
*/

/*******************************************
 * Интерфейс объявляет метод для выполнения команд.
 *******************************************************************
 */
interface Command
{
    public function exe();
}

class CargaDes
{
    public $onStart;

    /*******************************************
     * Инициализация команд.
     *******************************************************************
     * @param Command $command
     */
    public function setOnStart(Command $command)
    {
        $this->onStart = $command;
    }

    /*******************************************
     * Старт выбранной команды
     *******************************************************************
     */
    public function Start()
    {
        if ($this->onStart instanceof Command) {
            return $this->onStart->exe();
        }
        return false;
    }
}