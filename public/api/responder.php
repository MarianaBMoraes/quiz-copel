<?php

// Grava a resposta. O banco garante uma resposta por pessoa por pergunta.

require_once __DIR__ . '/../../app/jogo.php';

$pdo = require __DIR__ . '/../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jogoResponderJson(['ok' => false, 'motivo' => 'metodo'], 405);
}

$dados = json_decode(file_get_contents('php://input'), true);

if (!is_array($dados)) {
    $dados = $_POST;
}

$codigo = jogoCodigoValido($dados['code'] ?? null);
$perguntaId = (int) ($dados['pergunta_id'] ?? 0);
$letra = strtoupper((string) ($dados['letra'] ?? ''));

if (!$codigo || $perguntaId <= 0 || !in_array($letra, ['A', 'B', 'C', 'D'], true)) {
    jogoResponderJson(['ok' => false, 'motivo' => 'dados'], 400);
}

$partida = jogoCarregarPartida($pdo, $codigo);

if (!$partida) {
    jogoResponderJson(['ok' => false, 'motivo' => 'partida'], 404);
}

$participante = jogoParticipantePorCookie($pdo, (int) $partida['id']);

if (!$participante) {
    jogoResponderJson(['ok' => false, 'motivo' => 'sem_participacao'], 401);
}

if ((int) $partida['pergunta_atual_id'] !== $perguntaId) {
    jogoResponderJson(['ok' => false, 'motivo' => 'fora_do_tempo'], 409);
}

// O celular libera os botões pelo relógio. Se o relógio dele está adiantado,
// avisa quanto falta pra ele reenviar sozinho. Se a leitura já acabou e
// ninguém virou a fase ainda, esta requisição mesmo vira.
if ($partida['status'] === 'leitura') {
    $faltaMs = $partida['fim_ms'] - $partida['agora_ms'];

    if ($faltaMs > 0) {
        jogoResponderJson([
            'ok' => false,
            'motivo' => 'cedo',
            'espera_ms' => min(15000, $faltaMs + 100),
        ], 409);
    }

    if (jogoAvancar($pdo, $partida)) {
        jogoPublicarEstado($pdo, $codigo);
    }

    $partida = jogoCarregarPartida($pdo, $codigo);
}

if (
    $partida['status'] !== 'respondendo'
    || $partida['agora_ms'] > $partida['fim_ms'] + JOGO_TOLERANCIA_MS
) {
    jogoResponderJson(['ok' => false, 'motivo' => 'fora_do_tempo'], 409);
}

$pergunta = jogoPergunta($pdo, (int) $partida['quiz_id'], $perguntaId);

$statement = $pdo->prepare(
    'SELECT id, correta
     FROM alternativas
     WHERE pergunta_id = :pergunta_id
       AND letra = :letra
     LIMIT 1'
);

$statement->execute([
    'pergunta_id' => $perguntaId,
    'letra' => $letra,
]);

$alternativa = $statement->fetch();

if (!$pergunta || !$alternativa) {
    jogoResponderJson(['ok' => false, 'motivo' => 'dados'], 400);
}

$limiteMs = $pergunta['tempo_resposta'] * 1000;
$tempoMs = max(0, min($limiteMs, $partida['agora_ms'] - $partida['inicio_ms']));
$correta = (bool) $alternativa['correta'];

try {
    // A janela é conferida de novo dentro do INSERT: se o admin encerrou a
    // questão entre a checagem acima e agora, a resposta não entra.
    $statement = $pdo->prepare(
        'INSERT INTO respostas (
            participante_id,
            pergunta_id,
            alternativa_id,
            respondido_em,
            tempo_ms,
            correta,
            pontos
        )
        SELECT
            :participante_id,
            :pergunta_id,
            :alternativa_id,
            NOW(6),
            :tempo_ms,
            :correta,
            :pontos
        FROM partidas
        WHERE id = :partida_id
          AND status = :status
          AND pergunta_atual_id = :pergunta_atual_id
          AND NOW(6) <= DATE_ADD(fase_termina_em, INTERVAL 1 SECOND)'
    );

    $statement->execute([
        'participante_id' => $participante['id'],
        'pergunta_id' => $perguntaId,
        'alternativa_id' => $alternativa['id'],
        'tempo_ms' => $tempoMs,
        'correta' => $correta ? 1 : 0,
        'pontos' => jogoCalcularPontos($correta, $tempoMs, $pergunta['tempo_resposta']),
        'partida_id' => $partida['id'],
        'status' => 'respondendo',
        'pergunta_atual_id' => $perguntaId,
    ]);

    if ($statement->rowCount() !== 1) {
        jogoResponderJson(['ok' => false, 'motivo' => 'fora_do_tempo'], 409);
    }
} catch (PDOException $exception) {
    // Clique duplo ou reenvio: vale a primeira resposta.
    if ($exception->getCode() !== '23000') {
        throw $exception;
    }

    $statement = $pdo->prepare(
        'SELECT alternativas.letra
         FROM respostas
         INNER JOIN alternativas
            ON alternativas.id = respostas.alternativa_id
         WHERE respostas.participante_id = :participante_id
           AND respostas.pergunta_id = :pergunta_id
         LIMIT 1'
    );

    $statement->execute([
        'participante_id' => $participante['id'],
        'pergunta_id' => $perguntaId,
    ]);

    jogoResponderJson([
        'ok' => true,
        'letra' => $statement->fetchColumn(),
        'ja_respondida' => true,
    ]);
}

jogoResponderJson([
    'ok' => true,
    'letra' => $letra,
]);
