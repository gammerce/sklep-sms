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
        $serversIds = [0];
        foreach ($this->heart->getServers() as $server) {
            $serversIds[] = $server->getId();
            $tableRow .= new HeadCell($server->getName());
        }

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

            // Lecimy po każdym obiekcie, niezależnie, czy zarobiliśmy na nim czy nie
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
            "config" => json_encode($this->getLineChart($labels, $data)),
        ]);
        $tfootClass = '';
        $pagination = '';
        $title = $this->title;
        $table = $this->template->render(
            "admin/table_structure",
            compact('aboveTable', 'buttons', 'thead', 'tbody', 'pagination', 'tfootClass', 'title')
        );

        return $table;
    }

    private function getLineChart(array $labels, array $data)
    {
        $datasets = [
            0 => [
                "label" => $this->lang->translate('other'),
                "data" => [],
                "fill" => false,
            ],
        ];

        foreach ($this->heart->getServers() as $server) {
            $datasets[$server->getId()] = [
                "label" => $server->getName(),
                "data" => [],
                "fill" => false,
            ];
        }

        foreach ($labels as $label) {
            foreach (array_keys($datasets) as $serverId) {
                $income = array_get(array_get($data, $label), $serverId, 0);
                $datasets[$serverId]["data"][] = $income;
            }
        }

        return [
            "type" => 'line',
            "data" => [
                "labels" => $labels,
                "datasets" => array_values($datasets),
            ],
            "options" => [
                "responsive" => true,
                "tooltips" => [
                    "mode" => 'index',
                    "intersect" => false,
                ],
                "hover" => [
                    "mode" => 'nearest',
                    "intersect" => true,
                ],
            ],
        ];
    }
}
