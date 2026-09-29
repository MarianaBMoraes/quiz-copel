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
        status,
        pergunta_atual_id
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


if ($game['status'] !== 'leitura') {

    header(
        'Location: /admin/partida.php?code='
        . urlencode($gameCode)
        . '&erro=status'
    );

    exit;

}


$statement = $pdo->prepare(
    'SELECT
        tempo_resposta
     FROM perguntas
     WHERE id = :id
     LIMIT 1'
);


$statement->execute([
    'id' => $game['pergunta_atual_id'],
]);


$question = $statement->fetch();


if (!$question) {

    header(
        'Location: /admin/partida.php?code='
        . urlencode($gameCode)
        . '&erro=sem_perguntas'
    );

    exit;

}


$answerSeconds = (int) $question['tempo_resposta'];


$statement = $pdo->prepare(
    'UPDATE partidas
     SET
        status = :status,
        fase_iniciada_em = NOW(6),
        fase_termina_em = DATE_ADD(
            NOW(6),
            INTERVAL :tempo SECOND
        )
     WHERE id = :id
       AND status = :status_anterior'
);


$statement->execute([
    'status' => 'respondendo',
    'tempo' => $answerSeconds,
    'id' => $game['id'],
    'status_anterior' => 'leitura',
]);


header(
    'Location: /admin/partida.php?code='
    . urlencode($gameCode)
    . '&sucesso=respostas_iniciadas'
);


exit;