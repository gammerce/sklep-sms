<?php
namespace App\Pages;

use Admin\Table\HeadCell;

class PageAdminIncome extends PageAdmin
{
    const PAGE_ID = 'income';

    protected $privilege = 'view_income';

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

        $this->heart->pageTitle = $this->title = $this->lang->translate('income');
    }

    protected function content(array $query, array $body)
    {
        $queryMonth = isset($query['month']) ? $query['month'] : date("m");
        $queryYear = isset($query['year']) ? $query['year'] : date("Y");

        $tableRow = "";
        // Uzyskanie wszystkich serwerów
        foreach ($this->heart->getServers() as $id => $server) {
            $obejctsIds[] = $id;
            $tableRow .= new HeadCell($server['name']);
        }
        $obejctsIds[] = 0;

        $result = $this->db->query(
            $this->db->prepare(
                "SELECT t.income, t.timestamp, t.server " .
                    "FROM ({$this->settings['transactions_query']}) as t " .
                    "WHERE t.free = '0' AND IFNULL(t.income,'') != '' AND t.payment != 'wallet' AND t.timestamp LIKE '%s-%s-%%' " .
                    "ORDER BY t.timestamp ASC",
                [$queryYear, $queryMonth]
            )
        );

        // Sumujemy dochód po dacie (z dokładnością do dnia) i po serwerze
        $data = [];
        while ($row = $this->db->fetchArrayAssoc($result)) {
            $temp = explode(" ", $row['timestamp']);

            $data[$temp[0]][in_array($row['server'], $obejctsIds) ? $row['server'] : 0] +=
                $row['income'];
        }

        // Dodanie wyboru miesiąca
        $months = '';
        for ($i = 1; $i <= 12; $i++) {
            $months .= create_dom_element("option", $this->lang->translate($this->months[$i]), [
                'value' => str_pad($i, 2, 0, STR_PAD_LEFT),
                'selected' => $queryMonth == $i ? "selected" : "",
            ]);
        }

        // Dodanie wyboru roku
        $years = "";
        for ($i = 2014; $i <= intval(date("Y")); $i++) {
            $years .= create_dom_element("option", $i, [
                'value' => $i,
                'selected' => $queryYear == $i ? "selected" : "",
            ]);
        }

        $buttons = $this->template->render("admin/income_button", compact('years', 'months'));

        // Pobranie nagłówka tabeli
        $thead = $this->template->render("admin/income_thead", compact('tableRow'));

        //
        // Pobranie danych do tabeli

        // Pobieramy ilość dni w danym miesiącu
        $num = cal_days_in_month(CAL_GREGORIAN, $queryMonth, $queryYear);

        $tbody = "";
        $serversIncomes = [];
        // Lecimy pętla po każdym dniu
        for ($i = 1; $i <= $num; ++$i) {
            // Tworzymy wygląd daty
            $date =
                $queryYear .
                "-" .
                str_pad($queryMonth, 2, 0, STR_PAD_LEFT) .
                "-" .
                str_pad($i, 2, 0, STR_PAD_LEFT);

            // Jeżeli jest to dzień z przyszłości
            if ($date > date("Y-m-d")) {
                continue;
            }

            // Zerujemy dochód w danym dniu na danym serwerze
            $dayIncome = 0;
            $tableRow = "";

            // Lecimy po każdym obiekcie, niezależnie, czy zarobiliśmy na nim czy nie
            foreach ($obejctsIds as $objectId) {
                if (!isset($serversIncomes[$objectId])) {
                    $serversIncomes[$objectId] = 0;
                }

                $income = array_get($data, "$date.$objectId", 0);
                $dayIncome += $income;
                $serversIncomes[$objectId] += $income;
                $tableRow .= create_dom_element("td", number_format($income / 100.0, 2));
            }

            // Zaokraglenie do dowch miejsc po przecinku zarobku w danym dniu
            $dayIncome = number_format($dayIncome / 100.0, 2);

            $tbody .= $this->template->render(
                "admin/income_trow",
                compact('date', 'tableRow', 'dayIncome')
            );
        }

        // Pobranie podliczenia tabeli
        $tableRow = "";
        $totalIncome = 0;
        // Lecimy po wszystkich obiektach na których zarobiliśmy kasę
        foreach ($serversIncomes as $serverIncome) {
            $totalIncome += $serverIncome; // Całk przychód
            $tableRow .= create_dom_element("td", number_format($serverIncome / 100.0, 2));
        }

        // Jeżeli coś się policzyło, są jakieś dane
        if (strlen($tbody)) {
            $totalIncome = number_format($totalIncome / 100.0, 2);
            $tbody .= $this->template->render(
                "admin/income_trow2",
                compact('tableRow', 'totalIncome')
            );
        }
        // Brak danych
        else {
            $tbody = $this->template->render("admin/no_records");
        }

        // Pobranie wygladu strony
        $tfootClass = '';
        $pagination = '';
        $title = $this->title;
        return $this->template->render(
            "admin/table_structure",
            compact('buttons', 'thead', 'tbody', 'pagination', 'tfootClass', 'title')
        );
    }
}
