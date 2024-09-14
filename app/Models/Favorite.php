<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    protected $table = 'favorites';

    protected $fillable = [
        'id_telegram',
        'favorite_value',
        'created_at',
        'updated_at',
    ];

    /**
     * Изменение статуса "Избранное"
     *
     * @param int $telegramID - id Telegram пользователя
     * @param int $idArticle - id записи
     * @return object
     */
    public static function changeFavorite(int $telegramID, int $idArticle): ?object
    {
        $favoriteData = static::where('id_telegram', $telegramID)->first();

        if (empty($favoriteData->favorite_value)) {
            $favoriteValue = [];
            $favoriteValue[] = $idArticle;
        } else {
            $favoriteValue = json_decode($favoriteData->favorite_value, true) ?? [];

            if (!in_array($idArticle, $favoriteValue)) {
                $favoriteValue[] = $idArticle;
            } else {
                $favoriteValue = array_diff($favoriteValue, [$idArticle]);
            }
        }

        $favoriteValue = json_encode($favoriteValue);
        static::updateOrCreate(
            [
                'id_telegram' => $telegramID
            ],
            [
                'id_telegram' => $telegramID,
                'favorite_value' => $favoriteValue,
                'updated_at' => date('d.m.Y H:i'),
            ]
        );

        return static::getFavorite($telegramID);
    }

    /**
     * Получение списка записей добавленных в "Избранное"
     *
     * @param int $telegramID - id Telegram пользователя
     * @return object
     */
    public static function getFavorite(int $telegramID): ?object
    {
        $favoriteData = Favorite::where('id_telegram', $telegramID)->first();
        if (!empty($favoriteData)) {
            $favoriteData->favorite_value = !empty($favoriteData->favorite_value) ? json_decode($favoriteData->favorite_value, true) : [];
        }

        return $favoriteData;
    }

}
