<?php
namespace App\Services\Interfaces;

interface IServiceActionExecute
{
    /**
     * Wykonuje jakąś akcję, przydatne przy pobieraniu danych przez jQuery
     * i funkcję fetch_data
     *
     * @param string $action Akcja do wykonania
     * @param array  $body Dane $_POST
     *
     * @return string
     */
    public function actionExecute($action, $body);
}
