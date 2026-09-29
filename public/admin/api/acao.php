<?php

// Botões do painel ao vivo: iniciar, encerrar questão, ranking, próxima, encerrar jogo.

require_once __DIR__ . '/../../../app/auth.php';
require_once __DIR__ . '/../../../app/jogo.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jogoResponderJson(['ok' => false, 'motivo' => 'metodo'], 405);
}

$dados = json_decode(file_get_contents('php://input'), true) ?: [];

if (!validateAdminCsrf($dados['csrf_token'] ?? null)) {
    jogoResponderJson(['ok' => false, 'motivo' => 'csrf'], 403);
}

session_write_close();

$pdo = require __DIR__ . '/../../../app/config/database.php';

$codigo = jogoCodigoValido($dados['code'] ?? null);
$acao = (string) ($dados['acao'] ?? '');

$partida = $codigo ? jogoCarregarPartida($pdo, $codigo) : null;

if (!$partida) {
    jogoResponderJson(['ok' => false, 'motivo' => 'partida'], 404);
}

if (!jogoExecutarAcao($pdo, $partida, $acao)) {
    jogoResponderJson(['ok' => false, 'motivo' => 'acao_invalida'], 409);
}

$estado = jogoPublicarEstado($pdo, $codigo);

jogoResponderJson([
    'ok' => true,
    'status' => $estado['status'],
]);
