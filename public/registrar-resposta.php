<?php

$pdo = require __DIR__ . '/../app/config/database.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: /');

    exit;

}


$gameCode = preg_replace(
    '/\D/',
    '',
    $_POST['code'] ?? ''
);


$alternativeLetter = strtoupper(
    trim($_POST['alternative'] ?? '')
);


if (
    strlen($gameCode) !== 6 ||
    !in_array($alternativeLetter, ['A', 'B', 'C', 'D'])
) {

    header('Location: /');

    exit;

}


// Identifica participante

$token = $_COOKIE['quiz_participant'] ?? null;


if (!$token) {

    exit('Participante não identificado.');

}


$tokenHash = hash('sha256', $token);


$statement = $pdo->prepare(
    'SELECT
        id,
        partida_id
     FROM participantes
     WHERE token_reconexao_hash = :token
     LIMIT 1'
);


$statement->execute([
    'token' => $tokenHash,
]);


$participant = $statement->fetch();


if (!$participant) {

    exit('Participante não encontrado.');

}


// Busca partida

$statement = $pdo->prepare(
    'SELECT
        id,
        status,
        pergunta_atual_id,
        fase_iniciada_em
     FROM partidas
     WHERE codigo = :codigo
     LIMIT 1'
);


$statement->execute([
    'codigo' => $gameCode,
]);


$game = $statement->fetch();


if (
    !$game ||
    $game['status'] !== 'respondendo'
) {

    header(
        'Location: /jogar.php?code='
        . urlencode($gameCode)
    );

    exit;

}


// Verifica se já respondeu

$statement = $pdo->prepare(
    'SELECT id
     FROM respostas
     WHERE participante_id = :participante_id
       AND pergunta_id = :pergunta_id
     LIMIT 1'
);


$statement->execute([
    'participante_id' => $participant['id'],
    'pergunta_id' => $game['pergunta_atual_id'],
]);


if ($statement->fetch()) {

    header(
        'Location: /jogar.php?code='
        . urlencode($gameCode)
    );

    exit;

}


// Busca alternativa

$statement = $pdo->prepare(
    'SELECT
        id,
        correta
     FROM alternativas
     WHERE pergunta_id = :pergunta_id
       AND letra = :letra
     LIMIT 1'
);


$statement->execute([
    'pergunta_id' => $game['pergunta_atual_id'],
    'letra' => $alternativeLetter,
]);


$alternative = $statement->fetch();


if (!$alternative) {

    exit('Alternativa inválida.');

}


// Calcula tempo

$startTime = strtotime(
    $game['fase_iniciada_em']
);

$tempoMs = (int) (
    (microtime(true) - $startTime) * 1000
);


// Salva resposta

$statement = $pdo->prepare(
    'INSERT INTO respostas (
        participante_id,
        pergunta_id,
        alternativa_id,
        respondido_em,
        tempo_ms,
        correta,
        pontos
    ) VALUES (
        :participante_id,
        :pergunta_id,
        :alternativa_id,
        NOW(6),
        :tempo_ms,
        :correta,
        :pontos
    )'
);


$points = $alternative['correta']
    ? 1000
    : 0;


$statement->execute([
    'participante_id' => $participant['id'],
    'pergunta_id' => $game['pergunta_atual_id'],
    'alternativa_id' => $alternative['id'],
    'tempo_ms' => $tempoMs,
    'correta' => $alternative['correta'],
    'pontos' => $points,
]);


header(
    'Location: /jogar.php?code='
    . urlencode($gameCode)
    . '&respondido=1'
);

exit;

