<?php
namespace App\View;

use App\Routing\UrlGenerator;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Html\Div;
use App\View\Html\Li;
use App\View\Html\Link;
use App\View\Html\Ul;

class PaginationService
{
    /** @var Settings */
    private $settings;

    /** @var UrlGenerator */
    private $url;

    /** @var Translator */
    private $lang;

    public function __construct(
        Settings $settings,
        UrlGenerator $url,
        TranslationManager $translationManager
    ) {
        $this->settings = $settings;
        $this->url = $url;
        $this->lang = $translationManager->user();
    }

    public function createPagination($all, $currentPage, $script, $query, $rowLimit = 0)
    {
        $rowLimit = $rowLimit ? $rowLimit : $this->settings["row_limit"];

        // Wszystkich elementow jest mniej niz wymagana ilsoc na jednej stronie
        if ($all <= $rowLimit) {
            return null;
        }

        // Pobieramy ilosc stron
        $pagesAmount = floor(max($all - 1, 0) / $rowLimit) + 1;

        // Poprawiamy obecna strone, gdyby byla bledna
        if ($currentPage > $pagesAmount) {
            $currentPage = -1;
        }

        $paginationList = new Ul();
        $paginationList->addClass("pagination-list");

        $lp = 2;
        for ($i = 1, $dots = false; $i <= $pagesAmount; ++$i) {
            if (
                $i != 1 &&
                $i != $pagesAmount &&
                ($i < $currentPage - $lp || $i > $currentPage + $lp)
            ) {
                if (!$dots) {
                    if ($i < $currentPage - $lp) {
                        $href = $this->url->to(
                            $script,
                            array_merge($query, ["page" => round((1 + $currentPage - $lp) / 2)])
                        );
                    } elseif ($i > $currentPage + $lp) {
                        $href = $this->url->to(
                            $script,
                            array_merge($query, [
                                "page" => round(($currentPage + $lp + $pagesAmount) / 2),
                            ])
                        );
                    }

                    $paginationLink = (new Link("...", $href))->addClass("pagination-link");
                    $paginationList->addContent(new Li($paginationLink));

                    $dots = true;
                }
                continue;
            }

            $href = $this->url->to($script, array_merge($query, ["page" => $i]));
            $paginationLink = (new Link($i, $href))
                ->addClass("pagination-link")
                ->when($currentPage == $i, function (Link $link) {
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
                $this->url->to($script, array_merge($query, ["page" => $currentPage - 1]))
            );
        }

        $nextButton = new Link($this->lang->t("next"));
        $nextButton->addClass("pagination-next");
        if ($currentPage + 1 > $pagesAmount) {
            $nextButton->setParam("disabled", true);
        } else {
            $nextButton->setParam(
                "href",
                $this->url->to($script, array_merge($query, ["page" => $currentPage + 1]))
            );
        }

        $pagination->addContent($previousButton);
        $pagination->addContent($nextButton);
        $pagination->addContent($paginationList);

        return $pagination;
    }
}
