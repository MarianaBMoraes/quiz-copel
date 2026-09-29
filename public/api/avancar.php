<?php

// Plano B dos celulares: se a tela do administrador parou (notebook dormiu),
// a pergunta vencida é encerrada mesmo assim. Só faz viradas pelo tempo.

require_once __DIR__ . '/../../app/jogo.php';

$pdo = require __DIR__ . '/../../app/config/database.php';

$codigo = jogoCodigoValido($_GET['code'] ?? null);
$partida = $codigo ? jogoCarregarPartida($pdo, $codigo) : null;

if (!$partida) {
    jogoResponderJson(['ok' => false, 'motivo' => 'partida'], 404);
}

for ($volta = 0; $volta < 2 && jogoAvancar($pdo, $partida); $volta++) {
    $partida = jogoCarregarPartida($pdo, $codigo);
}

jogoResponderJson([
    'ok' => true,
    'estado' => jogoPublicarEstado($pdo, $codigo),
]);
