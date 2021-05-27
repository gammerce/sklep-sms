<?php
namespace App\Http\Middlewares;

use App\Http\Responses\PlainResponse;
use App\Install\SetupManager;
use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SetupAvailable implements MiddlewareContract
{
    private SetupManager $setupManager;

    public function __construct(SetupManager $setupManager)
    {
        $this->setupManager = $setupManager;
    }

    public function handle(Request $request, $args, Closure $next): Response
    {
        if ($this->setupManager->hasFailed()) {
            return new PlainResponse(
                "Wystąpił błąd podczas aktualizacji. Poinformuj o swoim problemie. Nie zapomnij dołączyć pliku data/logs/errors.log"
            );
        }

        if ($this->setupManager->isInProgress()) {
            return new PlainResponse(
                "Instalacja/Aktualizacja trwa, lub została błędnie przeprowadzona. Usuń plik data/setup_progress, aby przeprowadzić ją ponownie."
            );
        }

        return $next($request);
    }
}
