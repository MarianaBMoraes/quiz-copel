<?php

require_once __DIR__ . '/../../app/auth.php';

requireAdmin();

$pdo = require __DIR__ . '/../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

if (!validateAdminCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Solicitação inválida.');
}

$participantId = filter_input(
    INPUT_POST,
    'participant_id',
    FILTER_VALIDATE_INT
);

$gameCode = preg_replace(
    '/\D/',
    '',
    $_POST['game_code'] ?? ''
);

if (
    !$participantId ||
    strlen($gameCode) !== 6
) {
    header('Location: /');
    exit;
}

$statement = $pdo->prepare(
    'SELECT id, status
     FROM partidas
     WHERE codigo = :codigo
     LIMIT 1'
);

$statement->execute([
    'codigo' => $gameCode,
]);

$game = $statement->fetch();

if (!$game) {
    header('Location: /');
    exit;
}

if ($game['status'] !== 'aguardando') {
    header(
        'Location: /admin/partida.php?code='
        . urlencode($gameCode)
        . '&erro=partida_iniciada'
    );

    exit;
}

$statement = $pdo->prepare(
    'DELETE FROM participantes
     WHERE id = :participante_id
       AND partida_id = :partida_id'
);

$statement->execute([
    'participante_id' => $participantId,
    'partida_id' => $game['id'],
]);

header(
    'Location: /admin/partida.php?code='
    . urlencode($gameCode)
    . '&sucesso=participante_excluido'
);

exit;