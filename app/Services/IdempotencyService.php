<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class IdempotencyService
{
    public function header(): string
    {
        return config('idempotency.header', 'X-Idempotency-Key');
    }

    public function shouldHandle(Request $request): bool
    {
        $methods = config('idempotency.methods', ['POST']);
        return in_array($request->getMethod(), $methods, true)
            && $request->hasHeader($this->header());
    }

    public function buildBaseKey(Request $request): string
    {
        // escopo por usuário + método + path + chave do cliente
        $userPart = $request->user()?->id ? ('u:'.$request->user()->id) : ('ip:'.$request->ip());
        $method   = strtolower($request->getMethod());
        $path     = trim($request->path(), '/');
        $idemKey  = $request->header($this->header());
        return "idem:{$userPart}:{$method}:{$path}:{$idemKey}";
    }

    public function payloadHash(Request $request): string
    {
        // inclui body + query para validar reuso da mesma key com payload igual
        $body  = $request->getContent() ?? '';
        $query = http_build_query($request->query(), '', '&', PHP_QUERY_RFC3986);
        return hash('sha256', $body.'|'.$query);
    }

    public function keys(Request $request): array
    {
        $base = $this->buildBaseKey($request);
        return [
            'processing' => "{$base}:processing",
            'response'   => "{$base}:response",
            'payload'    => "{$base}:payload",
            'lock'       => "{$base}:lock",
        ];
    }

    public function getCachedResponse(string $responseKey): ?array
    {
        $raw = Redis::get($responseKey);
        return $raw ? json_decode($raw, true) : null;
    }

    public function cacheResponse(string $responseKey, array $payload): void
    {
        $ttl = (int) config('idempotency.ttl_response', 3600);
        Redis::setex($responseKey, $ttl, json_encode($payload));
    }

    public function markProcessing(string $processingKey): void
    {
        $ttl = (int) config('idempotency.ttl_processing', 120);
        Redis::setex($processingKey, $ttl, now()->toIso8601String());
    }

    public function savePayloadHash(string $payloadKey, string $hash): void
    {
        $ttl = (int) config('idempotency.ttl_response', 3600);
        Redis::setex($payloadKey, $ttl, $hash);
    }

    public function getPayloadHash(string $payloadKey): ?string
    {
        return Redis::get($payloadKey) ?: null;
    }

    public function acquireLock(string $lockKey): ?\Illuminate\Contracts\Cache\Lock
    {
        $ttl   = (int) config('idempotency.lock_ttl', 15);
        $wait  = (int) config('idempotency.lock_wait', 5);
        $lock  = Cache::lock($lockKey, $ttl);
        return $lock->block($wait, fn () => $lock) ?: null;
    }
}
