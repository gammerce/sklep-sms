<?php
namespace Install;

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
            if ($module['value']) {
                $status = "correct";
                $title = "Prawidłowo";
            } else {
                $status = "incorrect";
                $title = "Nieprawidłowo";
            }

            $serverModules .= $this->template->installUpdateRender(
                'module',
                compact('module', 'status', 'title')
            );

            if (!$module['value'] && $module['must-be']) {
                $everythingOk = false;
            }
        }
        if (strlen($serverModules)) {
            $text = "Moduły na serwerze";
            $data = $serverModules;
            $serverModules = $this->template->installUpdateRender(
                'update_info_brick',
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

            $filesPrivileges .= $this->template->installUpdateRender(
                'file',
                compact('file', 'status')
            );
        }
        if (strlen($filesPrivileges)) {
            $text = "Uprawnienia do zapisu";
            $data = $filesPrivileges;
            $filesPrivileges = $this->template->installUpdateRender(
                'update_info_brick',
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

            $filesDelete .= $this->template->installUpdateRender(
                'file',
                compact('file', 'status')
            );
        }
        if (strlen($filesDelete)) {
            $text = "Pliki do usunięcia";
            $data = $filesDelete;
            $filesDelete = $this->template->installUpdateRender(
                'update_info_brick',
                compact('text', 'data')
            );
        }

        return $this->template->installUpdateRender(
            'update_info',
            compact('serverModules', 'filesPrivileges', 'filesDelete')
        );
    }
}
