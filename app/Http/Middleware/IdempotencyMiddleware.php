<?php

namespace App\Http\Middleware;

use App\Services\IdempotencyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class IdempotencyMiddleware
{
    public function __construct(private IdempotencyService $service) {}

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        if (!$this->service->shouldHandle($request)) {
            return $next($request);
        }

        $keys = $this->service->keys($request);
        $payloadHash = $this->service->payloadHash($request);
        $enforcePayload = (bool) config('idempotency.enforce_same_payload', true);

        // 1) Se já temos resposta cacheada, devolve-a
        if ($cached = $this->service->getCachedResponse($keys['response'])) {
            if ($enforcePayload) {
                $savedHash = $this->service->getPayloadHash($keys['payload']);
                if ($savedHash && $savedHash !== $payloadHash) {
                    return response()->json([
                        'message' => 'Idempotency key reuse with different payload',
                    ], 409);
                }
            }
            return $this->rehydrateResponse($cached);
        }

        // 2) Lock para evitar corrida
        $lock = $this->service->acquireLock($keys['lock']);
        if (!$lock) {
            // Não conseguiu lock e não tem resposta pronta ⇒ conflito/duplicado
            return response()->json([
                'message' => 'Request is already being processed',
            ], 409);
        }

        try {
            // 3) Double-check: talvez alguém tenha cacheado a resposta enquanto esperávamos o lock
            if ($cached = $this->service->getCachedResponse($keys['response'])) {
                if ($enforcePayload) {
                    $savedHash = $this->service->getPayloadHash($keys['payload']);
                    if ($savedHash && $savedHash !== $payloadHash) {
                        return response()->json([
                            'message' => 'Idempotency key reuse with different payload',
                        ], 409);
                    }
                }
                return $this->rehydrateResponse($cached);
            }

            $this->service->markProcessing($keys['processing']);
            if ($enforcePayload) {
                $this->service->savePayloadHash($keys['payload'], $payloadHash);
            }

            /** @var SymfonyResponse $response */
            $response = $next($request);

            $toCache = $this->dehydrateResponse($response);
            $this->service->cacheResponse($keys['response'], $toCache);

            return $response;
        } finally {
            optional($lock)->release();
        }
    }

    protected function dehydrateResponse(SymfonyResponse $response): array
    {
        $status  = $response->getStatusCode();
        $content = method_exists($response, 'getContent') ? $response->getContent() : '';
        $headers = [];

        $whitelist = array_map('strtolower', config('idempotency.response_headers_whitelist', []));
        foreach ($response->headers->all() as $name => $values) {
            if (empty($whitelist) || in_array(strtolower($name), $whitelist, true)) {
                $headers[$name] = $values;
            }
        }

        return [
            'status'  => $status,
            'headers' => $headers,
            'body'    => $content,
        ];
    }

    protected function rehydrateResponse(array $cached): SymfonyResponse
    {
        $r = new Response($cached['body'] ?? '', $cached['status'] ?? 200);
        foreach (($cached['headers'] ?? []) as $name => $values) {
            foreach ((array) $values as $v) {
                $r->headers->set($name, $v, false);
            }
        }
        return $r;
    }
}
