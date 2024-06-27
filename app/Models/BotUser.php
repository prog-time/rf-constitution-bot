<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotUser extends Model
{
    protected $table = 'bot_users';

    protected $fillable = [
        'id_telegram',
        'firstname',
        'lastname',
        'username',
        'study_status',
        'updated_at',
        'created_at',
    ];

    /**
     * Изменение информации о клиенте
     *
     * @param int $telegramID - id Telegram пользователя
     * @param array $dataUser - информация о пользователе
     * @return object
     */
    public static function changeUser(int $telegramID, array $dataUser): object
    {
        $userData = static::where('id_telegram', $telegramID)->first();
        if (empty($userData)) {
            static::create(
                [
                    'id_telegram' => $dataUser['id_telegram'] ?? $telegramID,
                    'firstname' => $dataUser['firstname'] ?? "",
                    'lastname' => $dataUser['lastname'] ?? "",
                    'lastname' => $dataUser['username'] ?? "",
                    'study_status' => 0,
                    'created_at' => date('d.m.Y H:i'),
                    'updated_at' => date('d.m.Y H:i'),
                ]
            );
        } else {
            $dataUser['updated_at'] = date('d.m.Y H:i');
            $userData->update($dataUser);
        }

        return static::where('id_telegram', $telegramID)->first();
    }

}
