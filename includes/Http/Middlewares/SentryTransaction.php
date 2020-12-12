<?php
namespace App\Http\Middlewares;

use Closure;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Sentry\Tracing\TransactionContext;
use Symfony\Component\HttpFoundation\Request;

class SentryTransaction implements MiddlewareContract
{
    public function handle(Request $request, $args, Closure $next)
    {
        $hub = SentrySdk::getCurrentHub();
        $transaction = $this->startTransaction($request, $hub);

        $response = $next($request);

        SentrySdk::getCurrentHub()->setSpan($transaction);

        $transaction->setHttpStatus($response->getStatusCode());
        $transaction->finish();

        return $response;
    }

    private function startTransaction(Request $request, HubInterface $hub)
    {
        $context = new TransactionContext();
        $context->setOp("http.server");
        $context->setName($request->getPathInfo());
        $context->setData([
            "url" => $request->getPathInfo(),
            "method" => $request->getMethod(),
        ]);
        $context->setStartTimestamp($request->server->get("REQUEST_TIME_FLOAT", microtime(true)));

        return $hub->startTransaction($context);
    }
}
