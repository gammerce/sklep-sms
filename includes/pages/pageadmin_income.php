<?php

class PageAdminIncome extends PageAdmin
{
    const PAGE_ID = 'income';

    protected $privilage = 'view_income';

    protected $months = [
        '',
        "january",
        "february",
        "march",
        "april",
        "may",
        "june",
        "july",
        "august",
        "september",
        "october",
        "november",
        "december",
    ];

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('income');
    }

    protected function content($get, $post)
    {
        global $heart, $db, $settings, $lang, $templates;

        $G_MONTH = isset($get['month']) ? $get['month'] : date("m");
        $G_YEAR = isset($get['year']) ? $get['year'] : date("Y");

        $table_row = "";
        // Uzyskanie wszystkich serwerów
        foreach ($heart->get_servers() as $id => $server) {
            $obejcts_ids[] = $id;
            $table_row .= create_dom_element("td", $server['name']);
        }
        $obejcts_ids[] = 0;

        $result = $db->query($db->prepare(
            "SELECT t.income, t.timestamp, t.server " .
            "FROM ({$settings['transactions_query']}) as t " .
            "WHERE t.free = '0' AND IFNULL(t.income,'') != '' AND t.payment != 'wallet' AND t.timestamp LIKE '%s-%s-%%' " .
            "ORDER BY t.timestamp ASC",
            [$G_YEAR, $G_MONTH]
        ));

        // Sumujemy dochód po dacie (z dokładnością do dnia) i po serwerze
        $data = [];
        while ($row = $db->fetch_array_assoc($result)) {
            $temp = explode(" ", $row['timestamp']);

            $data[$temp[0]][in_array($row['server'], $obejcts_ids) ? $row['server'] : 0] += $row['income'];
        }

        // Dodanie wyboru miesiąca
        $months = '';
        for ($i = 1; $i <= 12; $i++) {
            $months .= create_dom_element("option", $lang->translate($this->months[$i]), [
                'value'    => str_pad($i, 2, 0, STR_PAD_LEFT),
                'selected' => $G_MONTH == $i ? "selected" : "",
            ]);
        }

        // Dodanie wyboru roku
        $years = "";
        for ($i = 2014; $i <= intval(date("Y")); $i++) {
            $years .= create_dom_element("option", $i, [
                'value'    => $i,
                'selected' => $G_YEAR == $i ? "selected" : "",
            ]);
        }

        $buttons = eval($templates->render("admin/income_button"));

        // Pobranie nagłówka tabeli
        $thead = eval($templates->render("admin/income_thead"));

        //
        // Pobranie danych do tabeli

        // Pobieramy ilość dni w danym miesiącu
        $num = cal_days_in_month(CAL_GREGORIAN, $G_MONTH, $G_YEAR);

        $tbody = "";
        $servers_incomes = [];
        // Lecimy pętla po każdym dniu
        for ($i = 1; $i <= $num; ++$i) {
            // Tworzymy wygląd daty
            $date = $G_YEAR . "-" . str_pad($G_MONTH, 2, 0, STR_PAD_LEFT) . "-" . str_pad($i, 2, 0, STR_PAD_LEFT);

            // Jeżeli jest to dzień z przyszłości
            if ($date > date("Y-m-d")) {
                continue;
            }

            // Zerujemy dochód w danym dniu na danym serwerze
            $day_income = 0;
            $table_row = "";

            // Lecimy po każdym obiekcie, niezależnie, czy zarobiliśmy na nim czy nie
            foreach ($obejcts_ids as $object_id) {
                $income = $data[$date][$object_id];
                $day_income += $income;
                $servers_incomes[$object_id] += $income;
                $table_row .= create_dom_element("td", number_format($income / 100.0, 2));
            }

            // Zaokraglenie do dowch miejsc po przecinku zarobku w danym dniu
            $day_income = number_format($day_income / 100.0, 2);

            $tbody .= eval($templates->render("admin/income_trow"));
        }

        // Pobranie podliczenia tabeli
        $table_row = "";
        $total_income = 0;
        // Lecimy po wszystkich obiektach na których zarobiliśmy kasę
        foreach ($servers_incomes as $server_income) {
            $total_income += $server_income; // Całk przychód
            $table_row .= create_dom_element("td", number_format($server_income / 100.0, 2));
        }

        // Jeżeli coś się policzyło, są jakieś dane
        if (strlen($tbody)) {
            $total_income = number_format($total_income / 100.0, 2);
            $tbody .= eval($templates->render("admin/income_trow2"));
        } else // Brak danych
        {
            $tbody = eval($templates->render("admin/no_records"));
        }

        // Pobranie wygladu strony
        $output = eval($templates->render("admin/table_structure"));

        return $output;
    }
}