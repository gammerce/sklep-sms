<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
use App\Models\Service;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\ServerRef;
use App\View\Html\ServiceRef;
use App\View\Html\Structure;
use App\View\Html\UserRef;
use App\View\Html\Wrapper;
use App\View\Pages\Interfaces\IPageAdminActionBox;

class PageAdminServiceCodes extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "service_codes";
    protected $privilege = "view_service_codes";

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t("service_codes");
    }

    protected function content(array $query, array $body)
    {
        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS *, sc.id, sc.code, s.id AS service_id, s.name AS service_name, srv.id AS server_id, srv.name AS server_name, sc.quantity, u.username, u.uid, sc.timestamp " .
                "FROM `ss_service_codes` AS sc " .
                "LEFT JOIN `ss_services` AS s ON sc.service = s.id " .
                "LEFT JOIN `ss_servers` AS srv ON sc.server = srv.id " .
                "LEFT JOIN `ss_users` AS u ON sc.uid = u.uid " .
                "LIMIT ?, ?"
        );
        $statement->execute(get_row_limit($this->currentPage->getPageNumber()));
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                $server = $row["server_id"]
                    ? new ServerRef($row["server_id"], $row["server_name"])
                    : $this->lang->t("all_servers");
                $user = $row["uid"]
                    ? new UserRef($row["uid"], $row["username"])
                    : $this->lang->t("none");
                $service = $row["service_id"]
                    ? new ServiceRef($row["service_id"], $row["service_name"])
                    : $this->lang->t("none");
                $quantity =
                    $row["quantity"] !== null ? $row["quantity"] : $this->lang->t("forever");

                return (new BodyRow())
                    ->setDbId($row["id"])
                    ->addCell(new Cell($row["code"]))
                    ->addCell(new Cell($service))
                    ->addCell(new Cell($server))
                    ->addCell(new Cell($quantity))
                    ->addCell(new Cell($user))
                    ->addCell(new Cell(convert_date($row["timestamp"])))
                    ->setDeleteAction(has_privileges("manage_service_codes"));
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("code")))
            ->addHeadCell(new HeadCell($this->lang->t("service")))
            ->addHeadCell(new HeadCell($this->lang->t("server")))
            ->addHeadCell(new HeadCell($this->lang->t("quantity")))
            ->addHeadCell(new HeadCell($this->lang->t("user")))
            ->addHeadCell(new HeadCell($this->lang->t("date_of_creation")))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $query, $rowsCount);

        $wrapper = (new Wrapper())->setTitle($this->title)->setTable($table);

        if (has_privileges("manage_service_codes")) {
            $button = (new Input())
                ->setParam("id", "service_code_button_add")
                ->setParam("type", "button")
                ->addClass("button")
                ->setParam("value", $this->lang->t("add_code"));

            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, array $query)
    {
        if (!has_privileges("manage_service_codes")) {
            throw new UnauthorizedException();
        }

        switch ($boxId) {
            case "code_add":
                $services = collect($this->heart->getServices())
                    ->map(function (Service $service) {
                        return create_dom_element("option", $service->getName(), [
                            "value" => $service->getId(),
                        ]);
                    })
                    ->join();

                $output = $this->template->render("admin/action_boxes/service_code_add", [
                    "services" => $services,
                ]);
                break;

            default:
                $output = "";
        }

        return [
            "status" => "ok",
            "template" => $output,
        ];
    }
}
