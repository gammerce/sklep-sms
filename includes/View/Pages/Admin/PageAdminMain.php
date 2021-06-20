<?php
namespace App\View\Pages\Admin;

use App\Http\Services\IncomeService;
use App\Managers\ServerManager;
use App\Models\Server;
use App\Payment\General\PaymentMethod;
use App\Repositories\TransactionRepository;
use App\Requesting\Requester;
use App\Routing\UrlGenerator;
use App\Server\Platform;
use App\Support\Database;
use App\Support\Meta;
use App\Support\PriceTextService;
use App\System\Auth;
use App\Theme\Template;
use App\Support\Version;
use App\System\License;
use App\Translation\TranslationManager;
use App\User\Permission;
use App\View\Html\Div;
use App\View\Html\DOMElement;
use App\View\Html\RawHtml;
use Symfony\Component\HttpFoundation\Request;

class PageAdminMain extends PageAdmin
{
    const PAGE_ID = "home";
    const LICENSE_EXPIRE_THRESHOLD = 4 * 24 * 60 * 60;

    private Version $version;
    private License $license;
    private Requester $requester;
    private IncomeService $incomeService;
    private PriceTextService $priceTextService;
    private TransactionRepository $transactionRepository;
    private UrlGenerator $url;
    private ServerManager $serverManager;
    private Database $db;
    private Meta $meta;
    private Auth $auth;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Version $version,
        License $license,
        Requester $requester,
        IncomeService $incomeService,
        PriceTextService $priceTextService,
        TransactionRepository $transactionRepository,
        UrlGenerator $url,
        ServerManager $serverManager,
        Database $db,
        Meta $meta,
        Auth $auth
    ) {
        parent::__construct($template, $translationManager);

        $this->version = $version;
        $this->license = $license;
        $this->requester = $requester;
        $this->incomeService = $incomeService;
        $this->priceTextService = $priceTextService;
        $this->transactionRepository = $transactionRepository;
        $this->url = $url;
        $this->serverManager = $serverManager;
        $this->db = $db;
        $this->meta = $meta;
        $this->auth = $auth;
    }

    public function getTitle(Request $request): string
    {
        return $this->lang->t("main_page");
    }

    public function getContent(Request $request)
    {
        $bricks = $this->getBricks();
        $notes = $this->getNotes();
        $pageTitle = $this->template->render("admin/page_title", [
            "buttons" => "",
            "title" => $this->lang->t("main_page"),
        ]);

        return $this->template->render("admin/home", compact("bricks", "pageTitle", "notes"));
    }

    private function getNotes(): string
    {
        $notes = [];

        // No license note
        if (!$this->license->isValid()) {
            $settingsUrl = $this->url->to("/admin/settings");
            $notes[] = $this->createNote(
                $this->lang->t("license_error", $settingsUrl),
                "is-danger"
            );
        }

        $expireSeconds = strtotime($this->license->getExpires()) - time();
        if (
            !$this->license->isForever() &&
            $expireSeconds >= 0 &&
            $expireSeconds < self::LICENSE_EXPIRE_THRESHOLD
        ) {
            $notes[] = $this->createNote(
                $this->lang->t(
                    "license_soon_expire",
                    seconds_to_time(strtotime($this->license->getExpires()) - time()),
                    $this->license->getIdentifier()
                ),
                "is-danger"
            );
        }

        $newestVersion = $this->version->getNewestWeb();
        $newestAmxXVersion = $this->version->getNewestAmxmodx();
        $newestSmVersion = $this->version->getNewestSourcemod();

        if ($newestVersion && version_compare($this->meta->getVersion(), $newestVersion) < 0) {
            $updateWebLink = $this->url->to("/admin/update_web");

            $notes[] = $this->createNote(
                $this->lang->t(
                    "update_available",
                    htmlspecialchars($newestVersion),
                    $updateWebLink
                ),
                "is-success"
            );
        }

        $serversCount = 0;
        foreach ($this->serverManager->all() as $server) {
            if (!$this->isServerNewest($server, $newestAmxXVersion, $newestSmVersion)) {
                $serversCount += 1;
            }
        }

        if ($serversCount) {
            $updateServersLink = $this->url->to("/admin/update_servers");

            $notes[] = $this->createNote(
                $this->lang->t(
                    "update_available_servers",
                    $serversCount,
                    $this->serverManager->getCount(),
                    $updateServersLink
                ),
                "is-success"
            );
        }

        return implode("", $notes);
    }

    private function getBricks(): string
    {
        $user = $this->auth->user();
        $bricks = [];

        // Server
        if ($user->can(Permission::MANAGE_SERVERS())) {
            $bricks[] = $this->createBrick(
                $this->lang->t("number_of_servers", $this->serverManager->getCount()),
                $this->url->to("/admin/servers")
            );
        }

        // User
        if ($user->can(Permission::MANAGE_USERS())) {
            $bricks[] = $this->createBrick(
                $this->lang->t(
                    "number_of_users",
                    $this->db->query("SELECT COUNT(*) FROM `ss_users`")->fetchColumn()
                ),
                $this->url->to("/admin/users")
            );
        }

        if ($user->can(Permission::VIEW_INCOME())) {
            // Bought service
            $quantity = $this->db
                ->query("SELECT COUNT(*) FROM ({$this->transactionRepository->getQuery()}) AS t")
                ->fetchColumn();
            $bricks[] = $this->createBrick(
                $this->lang->t("number_of_bought_services", $quantity),
                $this->url->to("/admin/bought_services")
            );

            // SMS
            $quantity = $this->db
                ->query(
                    "SELECT COUNT(*) " .
                        "FROM ({$this->transactionRepository->getQuery()}) as t " .
                        "WHERE t.payment = 'sms' AND t.free='0'"
                )
                ->fetchColumn();
            $bricks[] = $this->createBrick(
                $this->lang->t("number_of_sent_smses", $quantity),
                $this->url->to("/admin/payments", ["method" => (string) PaymentMethod::SMS()])
            );

            // Transfer
            $quantity = $this->db
                ->query(
                    "SELECT COUNT(*) " .
                        "FROM ({$this->transactionRepository->getQuery()}) as t " .
                        "WHERE t.payment = 'transfer' AND t.free='0'"
                )
                ->fetchColumn();
            $bricks[] = $this->createBrick(
                $this->lang->t("number_of_transfers", $quantity),
                $this->url->to("/admin/payments", ["method" => (string) PaymentMethod::TRANSFER()])
            );

            // Income
            $incomeData = $this->incomeService->get(date("Y"), date("m"));
            $income = 0;
            foreach ($incomeData as $date) {
                foreach ($date as $value) {
                    $income += $value;
                }
            }
            $incomeText = $this->priceTextService->getPriceText($income);
            $bricks[] = $this->createBrick(
                $this->lang->t("note_income", $incomeText),
                $this->url->to("/admin/income")
            );

            // Whole income
            $wholeIncomeText = $this->priceTextService->getPriceText(
                $this->incomeService->getWholeIncome()
            );
            $bricks[] = $this->createBrick(
                $this->lang->t("note_whole_income", $wholeIncomeText),
                $this->url->to("/admin/income")
            );
        }

        return implode("", $bricks);
    }

    /**
     * @param Server $server
     * @param string|null $newestAmxxVersion
     * @param string|null $newestSmVersion
     * @return bool
     */
    private function isServerNewest(Server $server, $newestAmxxVersion, $newestSmVersion): bool
    {
        if (
            Platform::AMXMODX()->equals($server->getType()) &&
            $newestAmxxVersion &&
            version_compare($server->getVersion(), $newestAmxxVersion) < 0
        ) {
            return false;
        }

        if (
            Platform::SOURCEMOD()->equals($server->getType()) &&
            $newestSmVersion &&
            version_compare($server->getVersion(), $newestSmVersion) < 0
        ) {
            return false;
        }

        return true;
    }

    private function createNote($text, $class): DOMElement
    {
        return new Div(new RawHtml($text), [
            "class" => "notification {$class}",
        ]);
    }

    private function createBrick($content, $link): string
    {
        return $this->template->render("admin/brick_card", compact("content", "link"));
    }
}
