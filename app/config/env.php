<?php

$envPath = dirname(__DIR__, 2) . '/.env';

if (!is_file($envPath)) {
    throw new RuntimeException('Arquivo .env não encontrado.');
}

$env = parse_ini_file(
    $envPath,
    false,
    INI_SCANNER_RAW
);

if ($env === false) {
    throw new RuntimeException('Não foi possível carregar o arquivo .env.');
}

return $env;