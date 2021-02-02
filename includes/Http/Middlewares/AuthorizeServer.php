<?php
namespace App\Http\Middlewares;

use App\Repositories\ServerRepository;
use App\System\ServerAuth;
use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeServer implements MiddlewareContract
{
    private ServerRepository $serverRepository;
    private ServerAuth $serverAuth;

    public function __construct(ServerRepository $serverRepository, ServerAuth $serverAuth)
    {
        $this->serverRepository = $serverRepository;
        $this->serverAuth = $serverAuth;
    }

    public function handle(Request $request, $args, Closure $next)
    {
        $token = $request->query->get("token");

        $server = $this->serverRepository->findByToken($token);
        if (!$server) {
            return new Response("Server unauthorized", Response::HTTP_BAD_REQUEST);
        }

        $this->serverAuth->setServer($server);

        return $next($request);
    }
}
