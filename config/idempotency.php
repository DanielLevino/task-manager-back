<?php

return [
    // Header que o front envia
    'header' => 'X-Idempotency-Key',

    // Métodos aos quais aplica
    'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],

    // TTLs em segundos
    'ttl_processing' => 120,    // chave de "em processamento"
    'ttl_response'   => 3600,   // cache da resposta já pronta

    // Quais headers da resposta devem ser preservados no cache
    'response_headers_whitelist' => [
        'content-type',
        'cache-control',
        'etag',
    ],

    // Espera (segundos) para conquistar o lock (evita tempestade de requests)
    'lock_wait' => 5,

    // Tempo do lock (segundos)
    'lock_ttl' => 15,

    // Se true, valida que o payload atual é igual ao do primeiro request com a mesma key
    'enforce_same_payload' => true,
];
