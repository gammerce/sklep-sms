<?php
namespace Install;

use App\Translator;

class InstallManager
{
    /** @var Translator */
    private $lang;

    public function __construct(Translator $translator)
    {
        $this->lang = $translator;
    }

    public function showError()
    {
        output_page('Wystąpił błąd podczas aktualizacji. Poinformuj o swoim problemie na forum sklepu. Do wątku załącz plik errors/install.log');
    }

    public function finish()
    {
        $this->removeInProgress();
    }

    public function start()
    {
        $this->putInProgress();
    }

    private function putInProgress()
    {
        file_put_contents(SCRIPT_ROOT . "install/progress", "");
    }

    public function removeInProgress()
    {
        unlink(SCRIPT_ROOT . "install/progress");
    }
}
