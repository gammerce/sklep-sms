<?php
namespace App\View\Pages;

use App\Support\QueryParticle;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\Div;
use App\View\Html\HeadCell;
use App\View\Html\Structure;
use App\View\Html\Wrapper;

class PageAdminLogs extends PageAdmin
{
    const PAGE_ID = 'logs';
    protected $privilege = 'view_logs';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('logs');
    }

    protected function content(array $query, array $body)
    {
        $queryParticle = new QueryParticle();
        if (isset($query['search'])) {
            $queryParticle->extend(
                create_search_query(
                    ["`id`", "`text`", "CAST(`timestamp` as CHAR)"],
                    $query['search']
                )
            );
        }

        $where = $queryParticle->isEmpty() ? "" : "WHERE {$queryParticle}";

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `ss_logs` {$where} ORDER BY `id` DESC LIMIT ?, ?"
        );
        $statement->execute(
            array_merge(
                $queryParticle->params(),
                get_row_limit($this->currentPage->getPageNumber())
            )
        );
        $rowsCount = $this->db->query('SELECT FOUND_ROWS()')->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                $div = new Div($row['text']);
                $div->addClass('one_line');
                $cellText = new Cell();
                $cellText->addContent($div);

                $cellDate = new Cell(convert_date($row['timestamp']));
                $cellDate->setParam('headers', 'date');

                $bodyRow = (new BodyRow())
                    ->setDbId($row['id'])
                    ->addCell($cellText)
                    ->addCell($cellDate);

                if (has_privileges("manage_logs")) {
                    $bodyRow->setDeleteAction(true);
                }

                return $bodyRow;
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t('id'), "id"))
            ->addHeadCell(new HeadCell($this->lang->t('text')))
            ->addHeadCell(new HeadCell($this->lang->t('date')))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $query, $rowsCount);

        return (new Wrapper())
            ->setTitle($this->title)
            ->setSearch()
            ->setTable($table)
            ->toHtml();
    }
}
