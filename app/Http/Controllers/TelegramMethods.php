<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TelegramMethods extends Controller
{

    /**
     * Отправка логов
     *
     * @param array $dataQuery - параметры запроса
     * @return void
     */
    public static function sendLogMessage(array $dataQuery)
    {
        $token = env('TELEGRAM_TOKEN');
        $dataQuery["chat_id"] = env('TELEGRAM_MY_CHAT');
        $urlQuery = "https://api.telegram.org/bot". $token ."/sendMessage";
        return ParserMethods::postQuery($urlQuery, $dataQuery);
    }

    /**
     * Отправка запросов в Telegram
     *
     * @param string $methodQuery - метод запроса
     * @param array $arrayQuery - массив с параметрами
     * @return void|null
     */
    public static function sendQueryTelegram(string $methodQuery, array $arrayQuery = [])
    {
        try {
            $token = env('TELEGRAM_TOKEN');

            $urlQuery = "https://api.telegram.org/bot". $token ."/" . $methodQuery;
            $resultQuery = ParserMethods::postQuery($urlQuery, $arrayQuery);

            if ($methodQuery == 'editMessageText') {
                if (empty($resultQuery['ok'])) {
                    $urlQuery = "https://api.telegram.org/bot". $token ."/sendMessage";
                    $resultQuery = ParserMethods::postQuery($urlQuery, $arrayQuery);
                }
            }

            return $resultQuery;

        } catch (\Exception $e) {
            ExceptionController::sendLogException($e);
        }
    }
}
