<?php
namespace App\Install;

use App\Application;
use App\Template;

class UpdateInfo
{
    /** @var Application */
    private $app;

    /** @var Template */
    private $template;

    public function __construct(Application $app, Template $template)
    {
        $this->app = $app;
        $this->template = $template;
    }

    public function updateInfo(&$everythingOk, $filesPriv, $filesDel, $modules)
    {
        // Sprawdzamy ustawienia modułuów
        $serverModules = '';
        foreach ($modules as $module) {
            $title = $module['text'];
            $status = $module['value'] ? "ok" : "bad";

            $serverModules .= $this->template->render(
                'setup/update/module',
                compact('title', 'status')
            );

            if (!$module['value'] && $module['must-be']) {
                $everythingOk = false;
            }
        }
        if (strlen($serverModules)) {
            $text = "Moduły na serwerze";
            $data = $serverModules;
            $serverModules = $this->template->render(
                'setup/update/update_info_brick',
                compact('text', 'data')
            );
        }

        $filesPrivileges = '';
        foreach ($filesPriv as $file) {
            if (!strlen($file)) {
                continue;
            }

            if (is_writable($this->app->path($file))) {
                $status = "ok";
            } else {
                $status = "bad";
                $everythingOk = false;
            }

            $filesPrivileges .= $this->template->render(
                'setup/update/file',
                compact('file', 'status')
            );
        }
        if (strlen($filesPrivileges)) {
            $text = "Uprawnienia do zapisu";
            $data = $filesPrivileges;
            $filesPrivileges = $this->template->render(
                'setup/update/update_info_brick',
                compact('text', 'data')
            );
        }

        $filesDelete = '';
        foreach ($filesDel as $file) {
            if (!strlen($file)) {
                continue;
            }

            if (!file_exists($this->app->path($file))) {
                $status = "ok";
            } else {
                $status = "bad";
                $everythingOk = false;
            }

            $filesDelete .= $this->template->render('setup/update/file', compact('file', 'status'));
        }
        if (strlen($filesDelete)) {
            $text = "Pliki do usunięcia";
            $data = $filesDelete;
            $filesDelete = $this->template->render(
                'setup/update/update_info_brick',
                compact('text', 'data')
            );
        }

        return $this->template->render(
            'setup/update/update_info',
            compact('serverModules', 'filesPrivileges', 'filesDelete')
        );
    }
}
