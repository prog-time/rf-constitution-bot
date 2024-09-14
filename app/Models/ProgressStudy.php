<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgressStudy extends Model
{
    protected $table = 'progress_study';

    protected $fillable = [
        'id_telegram',
        'progress_value',
        'created_at',
        'updated_at',
    ];

    /**
     * Изменение статуса "Прочитано"
     *
     * @param int $telegramID - id Telegram пользователя
     * @param int $idArticle - id записи
     * @return object
     */
    public static function changeProgressStudy(int $telegramID, int $idArticle): ?object
    {
        $progressStudyData = static::where('id_telegram', $telegramID)->first();

        if (empty($progressStudyData->progress_value)) {
            $progressValue = [];
            $progressValue[] = $idArticle;
        } else {
            $progressValue = json_decode($progressStudyData->progress_value, true) ?? [];
            if (!in_array($idArticle, $progressValue)) {
                $progressValue[] = $idArticle;
            } else {
                $progressValue = array_diff($progressValue, [$idArticle]);
            }
        }

        $progressValue = json_encode($progressValue);
        static::updateOrCreate(
            [
                'id_telegram' => $telegramID
            ],
            [
                'id_telegram' => $telegramID,
                'progress_value' => $progressValue,
                'updated_at' => date('d.m.Y H:i'),
            ]
        );

        return static::getProgressStudy($telegramID);
    }

    /**
     * Получение списка прочитанных записей
     *
     * @param int $telegramID - id Telegram пользователя
     * @return object
     */
    public static function getProgressStudy(int $telegramID): ?object
    {
        $progressStudy = ProgressStudy::where('id_telegram', $telegramID)->first();
        if (!empty($progressStudy)) {
            $progressStudy->progress_value = !empty($progressStudy->progress_value) ? json_decode($progressStudy->progress_value, true) : [];
        }

        return $progressStudy;
    }

}
