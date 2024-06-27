<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ParserMethods extends Controller
{
    /**
     * Отправка POST запросов
     *
     * @param string $urlQuery
     * @param array $queryParams
     *
     * @return void
     */
    public static function postQuery(string $urlQuery, array|string $queryParams = [], array $queryHeading = [])
    {
        try {
            $ch = curl_init($urlQuery);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $queryParams);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $queryHeading);
            $resultQuery = curl_exec($ch);
            curl_close($ch);

            if (empty($resultQuery)) {
                throw new Exception('Запрос вызвал ошибку');
            }

            return json_decode($resultQuery, true) ?? $resultQuery;
        } catch (\Exception $e) {
            ExceptionController::sendLogException($e);
        }
    }

    /**
     * Отправка GET запросов
     *
     * @param string $urlQuery
     * @param array $queryParams
     *
     * @return void
     */
    public static function getQuery(string $urlQuery, array|string $queryParams = [], array $queryHeading = [])
    {
        try {
            if (!empty($queryParams)) {
                $urlQuery = $urlQuery ."?" . http_build_query($queryParams);
            }

            $ch = curl_init($urlQuery);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $queryHeading);
            $resultQuery = curl_exec($ch);
            curl_close($ch);

            return json_decode($resultQuery, true) ?? $resultQuery;
        } catch (\Exception $e) {
            ExceptionController::sendLogException($e);
        }
    }
}
