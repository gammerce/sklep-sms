<?php

interface IService_ActionExecute
{
    /**
     * Wykonuje jakąś akcję, przydatne przy pobieraniu danych przez jQuery
     * i funkcję fetch_data
     *
     * @param string $action Akcja do wykonania
     * @param array  $post Dane $_POST
     *
     * @return string
     */
    public function action_execute($action, $post);
}
