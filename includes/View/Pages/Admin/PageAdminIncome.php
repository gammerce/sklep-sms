<?php
namespace App\View\Pages\Admin;

use App\Http\Services\IncomeService;
use App\Managers\ServerManager;
use App\Managers\WebsiteHeader;
use App\Models\Server;
use App\Support\PriceTextService;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\User\Permission;
use App\View\Html\HeadCell;
use App\View\Html\Option;
use Symfony\Component\HttpFoundation\Request;

class PageAdminIncome extends PageAdmin
{
    const PAGE_ID = "income";

    private array $months = [
        "",
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

    private IncomeService $incomeService;
    private WebsiteHeader $websiteHeader;
    private ServerManager $serverManager;
    private PriceTextService $priceTextService;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        IncomeService $incomeService,
        WebsiteHeader $websiteHeader,
        PriceTextService $priceTextService,
        ServerManager $serverManager
    ) {
        parent::__construct($template, $translationManager);

        $this->incomeService = $incomeService;
        $this->websiteHeader = $websiteHeader;
        $this->serverManager = $serverManager;
        $this->priceTextService = $priceTextService;
    }

    public function getPrivilege()
    {
        return Permission::VIEW_INCOME();
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("income");
    }

    public function getContent(Request $request)
    {
        $this->websiteHeader->addScript(
            "https://cdn.jsdelivr.net/npm/chart.js@2.9.3/dist/Chart.min.js"
        );

        $queryYear = $request->query->get("year", date("Y"));
        $queryMonth = $request->query->get("month", date("m"));

        $incomeFromPeriod = $this->incomeService->get($queryYear, $queryMonth);

        $labels = $this->getLabels($queryYear, $queryMonth);

        $thead = $this->renderTHead();
        $buttons = $this->renderButtons($queryYear, $queryMonth);

        $tbody = collect($labels)
            ->filter(fn($label) => $label <= date("Y-m-d"))
            ->map(
                fn($label) => $this->renderTRow($label, array_get($incomeFromPeriod, $label, []))
            );

        if ($tbody->isPopulated()) {
            $tbody->push($this->renderSummary($incomeFromPeriod));
        } else {
            $tbody->push($this->template->render("admin/no_records"));
        }

        $height = max(count($this->getServersIds()) * 20, 200);
        $aboveTable = $this->template->render("admin/income_chart", [
            "id" => "income_chart",
            "height" => "{$height}px",
            "labels" => json_encode($labels),
            "dataset" => json_encode($this->getDataset($labels, $incomeFromPeriod)),
        ]);

        $pageTitle = $this->template->render("admin/page_title", [
            "buttons" => $buttons,
            "title" => $this->getTitle($request),
        ]);

        return $this->template->render("admin/table_structure", [
            "aboveTable" => $aboveTable,
            "buttons" => $buttons,
            "pageTitle" => $pageTitle,
            "pagination" => "",
            "tbody" => $tbody->join(),
            "tfootClass" => "",
            "thead" => $thead,
        ]);
    }

    private function getDataset(array $labels, array $data)
    {
        $dataset = [
            0 => $this->createDatasetEntry($this->lang->t("other"), $this->getColor(0)),
        ];

        foreach ($this->serverManager->all() as $server) {
            $dataset[$server->getId()] = $this->createDatasetEntry(
                $server->getName(),
                $this->getColor(count($dataset))
            );
        }

        foreach ($labels as $label) {
            foreach (array_keys($dataset) as $serverId) {
                $income = array_get(array_get($data, $label), $serverId, 0);
                $dataset[$serverId]["data"][] = $this->priceTextService->getPlainPrice($income);
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
        $months = "";
        for ($dayId = 1; $dayId <= 12; $dayId++) {
            $months .= new Option(
                $this->lang->t($this->months[$dayId]),
                str_pad($dayId, 2, 0, STR_PAD_LEFT),
                [
                    "selected" => selected($month == $dayId),
                ]
            );
        }

        $years = "";
        for ($dayId = 2014; $dayId <= intval(date("Y")); $dayId++) {
            $years .= new Option($dayId, $dayId, [
                "selected" => selected($year == $dayId),
            ]);
        }

        return $this->template->render("admin/income_button", compact("years", "months"));
    }

    private function renderTHead()
    {
        $tableRow = "";

        foreach ($this->serverManager->all() as $server) {
            $tableRow .= new HeadCell($server->getName());
        }

        return $this->template->render("admin/income_thead", compact("tableRow"));
    }

    private function renderTRow($date, array $incomes)
    {
        $dayIncome = 0;
        $tableRows = [];

        foreach ($this->getServersIds() as $serverId) {
            $income = array_get($incomes, $serverId, 0);
            $dayIncome += $income;
            $tableRows[] = create_dom_element(
                "td",
                $this->priceTextService->getPlainPrice($income)
            );
        }

        $dayIncome = $this->priceTextService->getPlainPrice($dayIncome);
        $tableRow = implode("", $tableRows);

        return $this->template->render(
            "admin/income_trow",
            compact("date", "tableRow", "dayIncome")
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
            $serverIncomeText = $this->priceTextService->getPlainPrice($serverIncome);
            $totalIncome += $serverIncome;
            $tableRows[] = create_dom_element("td", $serverIncomeText);
        }

        $totalIncome = $this->priceTextService->getPlainPrice($totalIncome);
        $tableRow = implode("", $tableRows);

        return $this->template->render("admin/income_trow2", compact("tableRow", "totalIncome"));
    }

    /**
     * @return array
     */
    private function getServersIds()
    {
        return collect($this->serverManager->all())
            ->map(fn(Server $server) => $server->getId())
            ->push(0)
            ->all();
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
