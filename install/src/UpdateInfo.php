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

    public function updateInfo(&$everything_ok, $files_priv, $files_del, $modules)
    {
        // Sprawdzamy ustawienia modułuów
        $server_modules = '';
        foreach ($modules as $module) {
            if ($module['value']) {
                $status = "correct";
                $title = "Prawidłowo";
            } else {
                $status = "incorrect";
                $title = "Nieprawidłowo";
            }

            $server_modules .= eval($this->template->install_update_render('module'));

            if (!$module['value'] && $module['must-be']) {
                $everything_ok = false;
            }
        }
        if (strlen($server_modules)) {
            $text = "Moduły na serwerze";
            $data = $server_modules;
            $server_modules = eval($this->template->install_update_render('update_info_brick'));
        }

        $files_privilages = '';
        foreach ($files_priv as $file) {
            if (!strlen($file)) {
                continue;
            }

            if (is_writable($this->app->path($file))) {
                $status = "ok";
            } else {
                $status = "bad";
                $everything_ok = false;
            }

            $files_privilages .= eval($this->template->install_update_render('file'));
        }
        if (strlen($files_privilages)) {
            $text = "Uprawnienia do zapisu";
            $data = $files_privilages;
            $files_privilages = eval($this->template->install_update_render('update_info_brick'));
        }

        $files_delete = '';
        foreach ($files_del as $file) {
            if (!strlen($file)) {
                continue;
            }

            if (!file_exists($this->app->path($file))) {
                $status = "ok";
            } else {
                $status = "bad";
                $everything_ok = false;
            }

            $files_delete .= eval($this->template->install_update_render('file'));
        }
        if (strlen($files_delete)) {
            $text = "Pliki do usunięcia";
            $data = $files_delete;
            $files_delete = eval($this->template->install_update_render('update_info_brick'));
        }

        return eval($this->template->install_update_render('update_info'));
    }
}