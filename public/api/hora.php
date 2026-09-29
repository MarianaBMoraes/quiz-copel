<?php

// Relógio oficial pro celular acertar o cronômetro local.

require_once __DIR__ . '/../../app/jogo.php';

$pdo = require __DIR__ . '/../../app/config/database.php';

$agora = $pdo->query('SELECT UNIX_TIMESTAMP(NOW(6)) * 1000')->fetchColumn();

jogoResponderJson([
    'agora_ms' => (int) round((float) $agora),
]);
