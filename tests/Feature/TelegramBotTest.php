<?php

namespace Tests\Feature;

use App\Http\Controllers\TelegramMethods;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TelegramBotTest extends TestCase
{

    public function test_log_message_method(): void
    {
        $dataMessage = [
            'text' => 'Пример оповещения при ошибке',
        ];
        $resultQuery = TelegramMethods::sendLogMessage($dataMessage);

        /* проверка результата отправки лога */
        if (empty($resultQuery['ok'])) {
            $this->assertTrue(false);
        }

        /* проверка чата отправки */
        if ($resultQuery['result']['chat']['id'] === env('TELEGRAM_MY_CHAT')) {
            $this->assertTrue(false);
        }
    }

    private function createDataQuery()
    {

    }

    private function createDataMessage()
    {
        $test = [
            "update_id" => 396833459,
            "message" => [
                "message_id" => 234,
                "from" => [
                    "id" => 1424646511,
                    "is_bot" => false,
                    "first_name" => "u0418u043bu044cu044f",
                    "last_name" => "u041bu044fu0449u0443u043a",
                    "username" => "iliyalyachuk",
                    "language_code" => "ru"
                ],
                "chat" => [
                    "id" => 1424646511,
                    "first_name" => "u0418u043bu044cu044f",
                    "last_name" => "u041bu044fu0449u0443u043a",
                    "username" => "iliyalyachuk",
                    "type" => "private"
                ],
                "date" => 1713260928,
                "text" => "/start",
                "entities" => [
                    [
                        "offset" => 0,
                        "length" => 6,
                        "type" => "bot_command"
                    ]
                ]
            ]
        ];
    }

}
