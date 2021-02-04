<?php
namespace App\View\Pagination;

use App\Routing\UrlGenerator;
use App\System\Settings;
use App\Translation\Translator;
use App\View\Html\Div;
use App\View\Html\Li;
use App\View\Html\Link;
use App\View\Html\Ul;
use Symfony\Component\HttpFoundation\Request;

class Pagination
{
    private Settings $settings;
    private UrlGenerator $url;
    private Translator $lang;
    private Request $request;

    public function __construct(
        UrlGenerator $url,
        Settings $settings,
        Translator $lang,
        Request $request
    ) {
        $this->settings = $settings;
        $this->url = $url;
        $this->lang = $lang;
        $this->request = $request;
    }

    /**
     * @param int|null $rowLimit
     * @return array [int, int]
     */
    public function getSqlLimit($rowLimit = null)
    {
        $rowLimit = as_int($rowLimit ?: $this->settings["row_limit"]);
        $page = $this->getCurrentPage();
        return [($page - 1) * $rowLimit, $rowLimit];
    }

    public function getCurrentPage(): int
    {
        $pageNumber = (int) $this->request->get("page", 1);
        return max($pageNumber, 1);
    }

    /**
     * @param int $totalCount
     * @param string $path
     * @param int|null $rowLimit
     * @return Div|null
     */
    public function createComponent($totalCount, $path, $rowLimit = null)
    {
        $rowLimit = $rowLimit ?: $this->settings["row_limit"];
        $query = $this->request->query->all();
        $currentPage = $this->getCurrentPage();

        // Do not return pagination if all elements fit into one page
        if ($totalCount <= $rowLimit) {
            return null;
        }

        // How many pages are available
        $pagesCount = floor(max($totalCount - 1, 0) / $rowLimit) + 1;

        // In case current page is incorrect
        if ($currentPage > $pagesCount) {
            $currentPage = -1;
        }

        $paginationList = new Ul();
        $paginationList->addClass("pagination-list");

        $lp = 2;
        for ($i = 1, $dots = false; $i <= $pagesCount; ++$i) {
            if (
                $i != 1 &&
                $i != $pagesCount &&
                ($i < $currentPage - $lp || $i > $currentPage + $lp)
            ) {
                if (!$dots) {
                    if ($i < $currentPage - $lp) {
                        $href = $this->url->to(
                            $path,
                            array_merge($query, ["page" => round((1 + $currentPage - $lp) / 2)])
                        );
                    } elseif ($i > $currentPage + $lp) {
                        $href = $this->url->to(
                            $path,
                            array_merge($query, [
                                "page" => round(($currentPage + $lp + $pagesCount) / 2),
                            ])
                        );
                    }

                    $paginationLink = (new Link("...", $href))->addClass("pagination-link");
                    $paginationList->addContent(new Li($paginationLink));

                    $dots = true;
                }
                continue;
            }

            $href = $this->url->to($path, array_merge($query, ["page" => $i]));
            $paginationLink = (new Link($i, $href))
                ->addClass("pagination-link")
                ->when($currentPage === $i, function (Link $link) {
                    $link->addClass("is-current");
                });
            $paginationList->addContent(new Li($paginationLink));

            $dots = false;
        }

        $pagination = new Div();
        $pagination->addClass("pagination is-centered");

        $previousButton = new Link($this->lang->t("previous"));
        $previousButton->addClass("pagination-previous");
        if ($currentPage - 1 < 1) {
            $previousButton->setParam("disabled", true);
        } else {
            $previousButton->setParam(
                "href",
                $this->url->to($path, array_merge($query, ["page" => $currentPage - 1]))
            );
        }

        $nextButton = new Link($this->lang->t("next"));
        $nextButton->addClass("pagination-next");
        if ($currentPage + 1 > $pagesCount) {
            $nextButton->setParam("disabled", true);
        } else {
            $nextButton->setParam(
                "href",
                $this->url->to($path, array_merge($query, ["page" => $currentPage + 1]))
            );
        }

        $pagination->addContent($previousButton);
        $pagination->addContent($nextButton);
        $pagination->addContent($paginationList);

        return $pagination;
    }
}
