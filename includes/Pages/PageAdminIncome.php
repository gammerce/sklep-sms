<?php
namespace App\Pages;

use App\Html\HeadCell;
use App\Http\Services\IncomeService;

class PageAdminIncome extends PageAdmin
{
    const PAGE_ID = 'income';

    protected $privilege = 'view_income';

    private $months = [
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
        $this->heart->scriptAdd("https://cdn.jsdelivr.net/npm/chart.js@2.9.3/dist/Chart.min.js");

        /** @var IncomeService $incomeService */
        $incomeService = $this->app->make(IncomeService::class);

        $queryYear = array_get($query, 'year', date("Y"));
        $queryMonth = array_get($query, 'month', date("m"));

        $tableRow = "";
        $serversIds = [];
        foreach ($this->heart->getServers() as $server) {
            $serversIds[] = $server->getId();
            $tableRow .= new HeadCell($server->getName());
        }
        $serversIds[] = 0;

        $data = $incomeService->get($queryYear, $queryMonth);

        $months = '';
        for ($dayId = 1; $dayId <= 12; $dayId++) {
            $months .= create_dom_element("option", $this->lang->translate($this->months[$dayId]), [
                'value' => str_pad($dayId, 2, 0, STR_PAD_LEFT),
                'selected' => $queryMonth == $dayId ? "selected" : "",
            ]);
        }

        $years = '';
        for ($dayId = 2014; $dayId <= intval(date("Y")); $dayId++) {
            $years .= create_dom_element("option", $dayId, [
                'value' => $dayId,
                'selected' => $queryYear == $dayId ? "selected" : "",
            ]);
        }

        $buttons = $this->template->render("admin/income_button", compact('years', 'months'));
        $thead = $this->template->render("admin/income_thead", compact('tableRow'));

        //
        // Pobranie danych do tabeli

        // Days amount in a month
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $queryMonth, $queryYear);

        $tbody = "";
        $serversIncomes = [];
        $labels = [];
        for ($dayId = 1; $dayId <= $daysInMonth; ++$dayId) {
            $date =
                $queryYear .
                "-" .
                str_pad($queryMonth, 2, 0, STR_PAD_LEFT) .
                "-" .
                str_pad($dayId, 2, 0, STR_PAD_LEFT);

            $labels[] = $date;

            // Day from the future
            if ($date > date("Y-m-d")) {
                continue;
            }

            // Zerujemy dochód w danym dniu na danym serwerze
            $dayIncome = 0;
            $tableRow = "";

            foreach ($serversIds as $serverId) {
                if (!isset($serversIncomes[$serverId])) {
                    $serversIncomes[$serverId] = 0;
                }

                $income = array_get(array_get($data, $date), $serverId, 0);
                $dayIncome += $income;
                $serversIncomes[$serverId] += $income;
                $tableRow .= create_dom_element("td", number_format($income / 100.0, 2));
            }

            $dayIncome = number_format($dayIncome / 100.0, 2);

            $tbody .= $this->template->render(
                "admin/income_trow",
                compact('date', 'tableRow', 'dayIncome')
            );
        }

        // Table summary
        $tableRow = "";
        $totalIncome = 0;
        foreach ($serversIncomes as $serverIncome) {
            $totalIncome += $serverIncome;
            $tableRow .= create_dom_element("td", number_format($serverIncome / 100.0, 2));
        }

        if (strlen($tbody)) {
            $totalIncome = number_format($totalIncome / 100.0, 2);
            $tbody .= $this->template->render(
                "admin/income_trow2",
                compact('tableRow', 'totalIncome')
            );
        } else {
            $tbody = $this->template->render("admin/no_records");
        }

        $aboveTable = $this->template->render("admin/income_chart", [
            "id" => "income_chart",
            "labels" => json_encode($labels),
            "dataset" => json_encode($this->getDataset($labels, $data)),
        ]);
        $tfootClass = '';
        $pagination = '';
        $title = $this->title;
        return $this->template->render(
            "admin/table_structure",
            compact('aboveTable', 'buttons', 'thead', 'tbody', 'pagination', 'tfootClass', 'title')
        );
    }

    private function getDataset(array $labels, array $data)
    {
        $dataset = [
            0 => $this->createDatasetEntry($this->lang->translate('other'), $this->getColor(0)),
        ];

        foreach ($this->heart->getServers() as $server) {
            $dataset[$server->getId()] = $this->createDatasetEntry(
                $server->getName(),
                $this->getColor(count($dataset))
            );
        }

        foreach ($labels as $label) {
            foreach (array_keys($dataset) as $serverId) {
                $income = array_get(array_get($data, $label), $serverId, 0);
                $dataset[$serverId]["data"][] = number_format($income / 100.0, 2);
            }
        }

        return array_values($dataset);
    }

    private function createDatasetEntry($label, $color)
    {
        return [
            "label" => $label,
            "data" => [],
            "fill" => false,
            "backgroundColor" => $color,
            "borderColor" => $color,
        ];
    }

    private function getColor($number)
    {
        $colors = [
            "rgb(255, 99, 132)",
            "rgb(54, 162, 235)",
            "rgb(75, 192, 192)",
            "rgb(201, 203, 207)",
            "rgb(255, 159, 64)",
            "rgb(153, 102, 255)",
            "rgb(255, 205, 86)",
        ];

        return $colors[$number % count($colors)];
    }
}
