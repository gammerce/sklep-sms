<?php
namespace App\Http\Middlewares;

use App\Http\Responses\PlainResponse;
use App\Install\SetupManager;
use App\System\Application;
use Symfony\Component\HttpFoundation\Request;

class SetupAvailable implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        /** @var SetupManager $setupManager */
        $setupManager = $app->make(SetupManager::class);

        if ($setupManager->hasFailed()) {
            return new PlainResponse(
                'Wystąpił błąd podczas aktualizacji. Poinformuj o swoim problemie. Nie zapomnij dołączyć pliku data/logs/errors.log'
            );
        }

        if ($setupManager->isInProgress()) {
            return new PlainResponse(
                "Instalacja/Aktualizacja trwa, lub została błędnie przeprowadzona. Usuń plik data/setup_progress, aby przeprowadzić ją ponownie."
            );
        }

        return null;
    }
}
