<?php

// Situação do participante: se já respondeu, se acertou, pontos e posição.

require_once __DIR__ . '/../../app/jogo.php';

$pdo = require __DIR__ . '/../../app/config/database.php';

$codigo = jogoCodigoValido($_GET['code'] ?? null);

if (!$codigo) {
    jogoResponderJson(['ok' => false, 'motivo' => 'codigo'], 400);
}

$partida = jogoCarregarPartida($pdo, $codigo);

if (!$partida) {
    jogoResponderJson(['ok' => false, 'motivo' => 'partida'], 404);
}

$participante = jogoParticipantePorCookie($pdo, (int) $partida['id']);

if (!$participante) {
    jogoResponderJson(['ok' => false, 'motivo' => 'sem_participacao'], 401);
}

// Acerto e pontos da pergunta só aparecem depois que ela é encerrada.
$revelar = in_array($partida['status'], ['resultado', 'ranking', 'finalizado'], true);

$atual = null;

if ($partida['pergunta_atual_id']) {
    $perguntaId = (int) $partida['pergunta_atual_id'];

    $statement = $pdo->prepare(
        'SELECT
            alternativas.letra,
            respostas.correta,
            respostas.pontos
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

    $resposta = $statement->fetch();

    $letraCorreta = null;

    if ($revelar) {
        $statement = $pdo->prepare(
            'SELECT letra
             FROM alternativas
             WHERE pergunta_id = :pergunta_id
               AND correta = 1
             LIMIT 1'
        );

        $statement->execute([
            'pergunta_id' => $perguntaId,
        ]);

        $letraCorreta = $statement->fetchColumn() ?: null;
    }

    $atual = [
        'pergunta_id' => $perguntaId,
        'respondida' => (bool) $resposta,
        'letra' => $resposta['letra'] ?? null,
        'correta' => $revelar && $resposta ? (bool) $resposta['correta'] : null,
        'pontos' => $revelar ? (int) ($resposta['pontos'] ?? 0) : null,
        'letra_correta' => $letraCorreta,
    ];
}

$placar = null;

if ($revelar) {
    foreach (jogoRanking($pdo, (int) $partida['id']) as $linha) {
        if ($linha['id'] === (int) $participante['id']) {
            $placar = [
                'acertos' => $linha['acertos'],
                'pontos' => $linha['pontos'],
                'posicao' => in_array($partida['status'], ['ranking', 'finalizado'], true)
                    ? $linha['posicao']
                    : null,
            ];

            break;
        }
    }
}

jogoResponderJson([
    'ok' => true,
    'nome' => $participante['nome'],
    'status' => $partida['status'],
    'atual' => $atual,
    'placar' => $placar,
]);
