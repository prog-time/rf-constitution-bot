<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExceptionController extends Controller
{

    /**
     * Обработка ошибок и исключений
     *
     * @param \Exception $exception
     * @param string $typeLog - тип лога
     * @param bool $dieStatus - статус отключения программы
     * @return void
     */
    public static function sendLogException(\Exception $exception, string $typeLog = 'error', bool $dieStatus = false): void
    {
        if ($typeLog === 'error') {
            $textLog = "Ошибка \n";
            $textLog .= $exception->getFile() . "\n";
            $textLog .= $exception->getMessage() . "\n";
            $textLog .= "Линия:  ". $exception->getLine();

        } else {
            $textLog = $exception->getMessage() . "\n";
        }

        $dataMessage = [
            'text' => $textLog,
        ];
        TelegramMethods::sendLogMessage($dataMessage);

        if (!empty($dieStatus)) {
            die();
        }
    }
}
