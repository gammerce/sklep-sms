<?php
namespace App\Pages;

use App\Html\HeadCell;
use App\Http\Services\IncomeService;
use App\Models\Server;

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

    /** @var IncomeService */
    private $incomeService;

    public function __construct(IncomeService $incomeService)
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('income');
        $this->incomeService = $incomeService;
    }

    protected function content(array $query, array $body)
    {
        $this->heart->scriptAdd("https://cdn.jsdelivr.net/npm/chart.js@2.9.3/dist/Chart.min.js");

        $queryYear = array_get($query, 'year', date("Y"));
        $queryMonth = array_get($query, 'month', date("m"));

        $incomeFromPeriod = $this->incomeService->get($queryYear, $queryMonth);

        $labels = $this->getLabels($queryYear, $queryMonth);

        $thead = $this->renderTHead();
        $buttons = $this->renderButtons($queryYear, $queryMonth);

        $tbody = [];

        foreach ($labels as $date) {
            if ($date <= date("Y-m-d")) {
                $tbody[] = $this->renderTRow($date, array_get($incomeFromPeriod, $date, []));
            }
        }

        if (count($tbody)) {
            $tbody[] = $this->renderSummary($incomeFromPeriod);
        } else {
            $tbody = [$this->template->render("admin/no_records")];
        }

        $aboveTable = $this->template->render("admin/income_chart", [
            "id" => "income_chart",
            "labels" => json_encode($labels),
            "dataset" => json_encode($this->getDataset($labels, $incomeFromPeriod)),
        ]);

        return $this->template->render("admin/table_structure", [
            "aboveTable" => $aboveTable,
            "buttons" => $buttons,
            "thead" => $thead,
            "tbody" => implode("", $tbody),
            "pagination" => '',
            "tfootClass" => '',
            'title' => $this->title,
        ]);
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

    private function renderButtons($year, $month)
    {
        $months = '';
        for ($dayId = 1; $dayId <= 12; $dayId++) {
            $months .= create_dom_element("option", $this->lang->translate($this->months[$dayId]), [
                'value' => str_pad($dayId, 2, 0, STR_PAD_LEFT),
                'selected' => $month == $dayId ? "selected" : "",
            ]);
        }

        $years = '';
        for ($dayId = 2014; $dayId <= intval(date("Y")); $dayId++) {
            $years .= create_dom_element("option", $dayId, [
                'value' => $dayId,
                'selected' => $year == $dayId ? "selected" : "",
            ]);
        }

        return $this->template->render("admin/income_button", compact('years', 'months'));
    }

    private function renderTHead()
    {
        $tableRow = "";

        foreach ($this->heart->getServers() as $server) {
            $tableRow .= new HeadCell($server->getName());
        }

        return $this->template->render("admin/income_thead", compact('tableRow'));
    }

    private function renderTRow($date, array $incomes)
    {
        $dayIncome = 0;
        $tableRows = [];

        foreach ($this->getServersIds() as $serverId) {
            $income = array_get($incomes, $serverId, 0);
            $dayIncome += $income;
            $tableRows[] = create_dom_element("td", number_format($income / 100.0, 2));
        }

        $dayIncome = number_format($dayIncome / 100.0, 2);
        $tableRow = implode("", $tableRows);

        return $this->template->render(
            "admin/income_trow",
            compact('date', 'tableRow', 'dayIncome')
        );
    }

    private function renderSummary(array $income)
    {
        $serversIncomes = [];
        foreach ($income as $date => $incomes) {
            foreach ($incomes as $serverId => $incomeValue) {
                $serversIncomes[$serverId] =
                    array_get($serversIncomes, $serverId, 0) + $incomeValue;
            }
        }

        $tableRows = [];
        $totalIncome = 0;

        foreach ($this->getServersIds() as $serverId) {
            $serverIncome = array_get($serversIncomes, $serverId, 0);
            $serverIncomeText = number_format($serverIncome / 100.0, 2);
            $totalIncome += $serverIncome;
            $tableRows[] = create_dom_element("td", $serverIncomeText);
        }

        $totalIncome = number_format($totalIncome / 100.0, 2);
        $tableRow = implode("", $tableRows);

        return $this->template->render("admin/income_trow2", compact('tableRow', 'totalIncome'));
    }

    private function getServersIds()
    {
        $serversIds = array_map(function (Server $server) {
            return $server->getId();
        }, $this->heart->getServers());
        $serversIds[] = 0;

        return $serversIds;
    }

    private function getLabels($year, $month)
    {
        $labels = [];
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        for ($dayId = 1; $dayId <= $daysInMonth; ++$dayId) {
            $date =
                $year .
                "-" .
                str_pad($month, 2, 0, STR_PAD_LEFT) .
                "-" .
                str_pad($dayId, 2, 0, STR_PAD_LEFT);

            $labels[] = $date;
        }

        return $labels;
    }
}
