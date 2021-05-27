<?php
namespace App\Http\Middlewares;

use Closure;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Sentry\Tracing\Transaction;
use Sentry\Tracing\TransactionContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SentryTransaction implements MiddlewareContract
{
    public function handle(Request $request, $args, Closure $next): Response
    {
        $hub = SentrySdk::getCurrentHub();
        $transaction = $this->startTransaction($request, $hub);
        SentrySdk::getCurrentHub()->setSpan($transaction);
        return $next($request);
    }

    private function startTransaction(Request $request, HubInterface $hub): Transaction
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
