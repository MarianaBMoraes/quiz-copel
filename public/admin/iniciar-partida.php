<?php

require_once __DIR__ . '/../../app/auth.php';

requireAdmin();

$pdo = require __DIR__ . '/../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/');
    exit;
}

if (!validateAdminCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Solicitação inválida.');
}

$gameCode = preg_replace(
    '/\D/',
    '',
    $_POST['game_code'] ?? ''
);

if (strlen($gameCode) !== 6) {
    header('Location: /admin/');
    exit;
}

$statement = $pdo->prepare(
    'SELECT
        id,
        quiz_id,
        status
     FROM partidas
     WHERE codigo = :codigo
     LIMIT 1'
);

$statement->execute([
    'codigo' => $gameCode,
]);

$game = $statement->fetch();

if (!$game) {
    header('Location: /admin/');
    exit;
}

if ($game['status'] !== 'aguardando') {

    header(
        'Location: /admin/partida.php?code='
        . urlencode($gameCode)
        . '&erro=status'
    );

    exit;
}

$statement = $pdo->prepare(
    'SELECT
        id,
        tempo_leitura
     FROM perguntas
     WHERE quiz_id = :quiz_id
     ORDER BY ordem ASC
     LIMIT 1'
);

$statement->execute([
    'quiz_id' => $game['quiz_id'],
]);

$firstQuestion = $statement->fetch();

if (!$firstQuestion) {

    header(
        'Location: /admin/partida.php?code='
        . urlencode($gameCode)
        . '&erro=sem_perguntas'
    );

    exit;
}

$readingSeconds = (int) $firstQuestion['tempo_leitura'];

$statement = $pdo->prepare(
    'UPDATE partidas
     SET
        status = :status,
        pergunta_atual_id = :pergunta_id,
        iniciada_em = NOW(6),
        fase_iniciada_em = NOW(6),
        fase_termina_em = DATE_ADD(
            NOW(6),
            INTERVAL :tempo SECOND
        )
     WHERE id = :id
       AND status = :status_anterior'
);

$statement->execute([
    'status' => 'leitura',
    'pergunta_id' => $firstQuestion['id'],
    'tempo' => $readingSeconds,
    'id' => $game['id'],
    'status_anterior' => 'aguardando',
]);

if ($statement->rowCount() !== 1) {

    header(
        'Location: /admin/partida.php?code='
        . urlencode($gameCode)
        . '&erro=status'
    );

    exit;
}

header(
    'Location: /admin/partida.php?code='
    . urlencode($gameCode)
    . '&sucesso=partida_iniciada'
);

exit;