<?php

// Só roda pelo terminal, nunca pelo navegador.
if (PHP_SAPI !== 'cli') {
    exit;
}

$pdo = require __DIR__ . '/../app/config/database.php';

$result = $pdo
    ->query('SELECT DATABASE() AS database_name, VERSION() AS mysql_version')
    ->fetch();

echo PHP_EOL;
echo "Conexão realizada com sucesso!" . PHP_EOL;
echo "Banco: " . $result['database_name'] . PHP_EOL;
echo "MySQL: " . $result['mysql_version'] . PHP_EOL;
echo PHP_EOL;