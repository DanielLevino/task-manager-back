<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HolidayService
{
    private string $baseUrl;
    private ?string $apiKey;
    private int $ttl; // segundos

    public function __construct()
    {
        // Endpoint JSON correto da Invertexto
        $this->baseUrl = 'https://api.invertexto.com/v1/holidays';
        $this->apiKey  = config('services.invertexto.key');             // INVERTEXTO_API_KEY
        $this->ttl     = (int) (config('services.invertexto.ttl') ?? 43200); // HOLIDAY_CACHE_TTL (12h padrão)
    }

    /**
     * Retorna a lista de feriados normalizada para um ano/UF, usando cache.
     * Formato: [['date' => 'YYYY-MM-DD', 'name' => '...'], ...]
     */
    public function list(int $year, string $uf): array
    {
        $uf  = strtoupper($uf);
        $key = "holidays:{$year}:{$uf}";

        // Se já estiver no cache, retorna
        if (Cache::has($key)) {
            return Cache::get($key) ?: [];
        }

        // Busca da API (não cacheia falhas)
        $data = $this->fetchFromApi($year, $uf);

        if (!empty($data)) {
            Cache::put($key, $data, $this->ttl);
        }

        return $data;
    }

    /**
     * Verifica se uma data ISO (YYYY-MM-DD) é feriado para a UF informada.
     */
    public function check(string $dateISO, string $uf): array
    {
        $dateISO = substr($dateISO, 0, 10);
        $year    = (int) substr($dateISO, 0, 4);

        try {
            $list = $this->list($year, $uf);
        } catch (\Throwable $e) {
            Log::error('Holiday check error', ['msg' => $e->getMessage()]);
            return [
                'is_holiday' => false,
                'name'       => null,
                'date'       => $dateISO,
                'uf'         => strtoupper($uf),
                'year'       => $year,
                'error'      => true,
            ];
        }

        $hit = collect($list)->firstWhere('date', $dateISO);

        return [
            'is_holiday' => (bool) $hit,
            'name'       => $hit['name'] ?? null,
            'date'       => $dateISO,
            'uf'         => strtoupper($uf),
            'year'       => $year,
        ];
    }

    /**
     * Atalho booleano.
     */
    public function isHoliday(string $dateISO, string $uf): bool
    {
        return $this->check($dateISO, $uf)['is_holiday'] === true;
    }

    /**
     * Pré-carrega e grava em cache os feriados de um ano/UF (útil para warmup).
     */
    public function warm(int $year, string $uf): void
    {
        $this->list($year, $uf);
    }

    /**
     * Chamada real à API + normalização.
     * Retorna [] em caso de erro para evitar cachear lixo.
     */
    private function fetchFromApi(int $year, string $uf): array
    {
        try {
            $resp = Http::timeout(10)->get("{$this->baseUrl}/{$year}", [
                // Pode usar também ->withToken($this->apiKey) se preferir
                'token' => $this->apiKey,
                'state' => $uf, // <- UF no parâmetro correto
            ]);

            if (!$resp->ok()) {
                Log::warning('Holiday API non-200', [
                    'status' => $resp->status(),
                    'body'   => substr((string) $resp->body(), 0, 500),
                    'year'   => $year,
                    'uf'     => $uf,
                ]);
                return [];
            }

            $json = $resp->json();
            if (!is_array($json)) {
                Log::warning('Holiday API unexpected payload', [
                    'payload_preview' => substr((string) $resp->body(), 0, 500),
                    'year'            => $year,
                    'uf'              => $uf,
                ]);
                return [];
            }

            // Normaliza para: 'YYYY-MM-DD'
            $out = [];
            foreach ($json as $item) {
                $date = $item['date'] ?? $item['data'] ?? null;
                $name = $item['name'] ?? $item['nome'] ?? '';

                if (!$date) {
                    continue;
                }

                // Trunca datetime -> date
                $date = substr($date, 0, 10);

                // Se vier 'DD/MM/YYYY', converte para ISO
                if (strpos($date, '/') !== false) {
                    [$d, $m, $y] = array_pad(explode('/', $date), 3, null);
                    if (ctype_digit((string)$d) && ctype_digit((string)$m) && ctype_digit((string)$y)) {
                        if (checkdate((int) $m, (int) $d, (int) $y)) {
                            $date = sprintf('%04d-%02d-%02d', $y, $m, $d);
                        }
                    }
                }

                $out[] = [
                    'date' => $date,
                    'name' => (string) $name,
                ];
            }

            return $out;
        } catch (\Throwable $e) {
            Log::error('Holiday API exception', [
                'error' => $e->getMessage(),
                'year'  => $year,
                'uf'    => $uf,
            ]);
            return [];
        }
    }
}
