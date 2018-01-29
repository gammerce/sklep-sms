<?php
namespace App\Kernels;

use App\ShopState;
use App\Template;
use Install\Full;
use Install\OldShop;
use Install\Update;
use Install\UpdateInfo;
use Symfony\Component\HttpFoundation\Request;

class InstallKernel extends Kernel
{
    public function handle(Request $request)
    {
        /** @var OldShop $oldShop */
        $oldShop = $this->app->make(OldShop::class);
        $oldShop->checkForConfigFile();

        if (!ShopState::isInstalled()) {
            return $this->full();
        }

        /** @var ShopState $shopState */
        $shopState = $this->app->make(ShopState::class);
        if (!$shopState->isUpToDate()) {
            return $this->update();
        }

        output_page("Sklep nie wymaga aktualizacji. Przejdź na stronę sklepu usuwająć z paska adresu /install");
    }

    protected function full()
    {
        /** @var Template $template */
        $template = $this->app->make(Template::class);

        /** @var Full $full */
        $full = $this->app->make(Full::class);

        list($modules, $files_priv) = $full->get();

        // #########################################
        // ##########    Wyświetl dane    ##########
        // #########################################

        $files_privilages = '';
        foreach ($files_priv as $file) {
            if ($file == "") {
                continue;
            }

            if (is_writable(SCRIPT_ROOT . '/' . $file)) {
                $privilage = "ok";
            } else {
                $privilage = "bad";
            }

            $files_privilages .= eval($template->install_full_render('file_privilages'));
        }

        $server_modules = '';
        foreach ($modules as $module) {
            if ($module['value']) {
                $status = "correct";
                $title = "Prawidłowo";
            } else {
                $status = "incorrect";
                $title = "Nieprawidłowo";
            }

            $server_modules .= eval($template->install_full_render('module'));
        }

        // Pobranie ostatecznego szablonu
        $output = eval($template->install_full_render('index'));

        // Wyświetlenie strony
        output_page($output);
    }

    protected function update()
    {
        /** @var Template $template */
        $template = $this->app->make(Template::class);

        /** @var UpdateInfo $updateInfo */
        $updateInfo = $this->app->make(UpdateInfo::class);

        /** @var Update $update */
        $update = $this->app->make(Update::class);

        list($modules, $files_priv, $files_del) = $update->get();

        $everything_ok = true;
        // Pobieramy informacje o plikach ktore sa git i te ktore sa be
        $update_info = $updateInfo->updateInfo($everything_ok, $files_priv, $files_del, $modules);
        $class = $everything_ok ? "ok" : "bad";

        // Pobranie ostatecznego szablonu
        $output = eval($template->install_update_render('index'));

        // Wyświetlenie strony
        output_page($output);
    }
}
