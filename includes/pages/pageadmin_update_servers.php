<?php


use App\Requesting\Requester;

class PageAdminUpdateServers extends PageAdmin
{
    const PAGE_ID = 'update_servers';
    protected $privilage = 'update';

    /** @var Requester */
    protected $requester;

    public function __construct(Requester $requester)
    {
        parent::__construct();

        $this->requester = $requester;
        $this->heart->page_title = $this->title = $this->lang->translate('update_servers');
    }

    protected function content($get, $post)
    {
        $lang = $this->lang;

        $response = $this->requester->get('https://sklep-sms.pl/version.php', [
            'action' => 'get_newest',
            'type'   => 'engines',
        ]);
        $newest_versions = $response ? $response->json() : null;

        $version_bricks = $servers_versions = "";
        foreach ($this->heart->get_servers() as $server) {
            $engine = "engine_{$server['type']}";
            // Mamy najnowszą wersję
            if ($server['version'] == $newest_versions[$engine]) {
                continue;
            }

            $name = htmlspecialchars($server['name']);
            $current_version = $server['version'];
            $next_version = trim(
                $this->requester
                    ->get('https://sklep-sms.pl/version.php', [
                        'action'  => 'get_next',
                        'type'    => $engine,
                        'version' => $server['version'],
                    ])
                    ->getBody()
            );
            $newest_version = $newest_versions[$engine];

            // Nie ma kolejnej wersji
            if (!strlen($next_version)) {
                continue;
            }

            // Pobieramy informacje o danym serwerze, jego obecnej wersji i nastepnej wersji
            $version_bricks .= eval($this->template->render("admin/update_version_block"));

            // Pobieramy plik kolejnej wersji update
            $file_data['type'] = "update";
            $file_data['platform'] = $engine;
            $file_data['version'] = $next_version;
            $next_package = eval($this->template->render("admin/update_file"));

            // Pobieramy plik najnowszej wersji full
            $file_data['type'] = "full";
            $file_data['platform'] = $engine;
            $file_data['version'] = $newest_version;
            $newest_package = eval($this->template->render("admin/update_file"));

            $servers_versions .= eval($this->template->render("admin/update_server_version"));
        }

        // Brak aktualizacji
        if (!strlen($version_bricks)) {
            $output = eval($this->template->render("admin/no_update"));

            return $output;
        }

        // Pobranie wyglądu całej strony
        return eval($this->template->render("admin/update_server"));
    }
}