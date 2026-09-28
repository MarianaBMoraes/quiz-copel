<?php

$pdo = require __DIR__ . '/../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$gameCode = preg_replace('/\D/', '', $_POST['code'] ?? '');
$name = trim($_POST['name'] ?? '');
$corporateUser = trim($_POST['corporate_user'] ?? '');

if (
    strlen($gameCode) !== 6 ||
    $name === '' ||
    $corporateUser === ''
) {
    header('Location: /?erro=dados');
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

if (!$game || $game['status'] !== 'aguardando') {
    header('Location: /?erro=partida');
    exit;
}

$statement = $pdo->prepare(
    'SELECT id
     FROM participantes
     WHERE partida_id = :partida_id
       AND usuario_copel = :usuario_copel
     LIMIT 1'
);

$statement->execute([
    'partida_id' => $game['id'],
    'usuario_copel' => $corporateUser,
]);

$existingParticipant = $statement->fetch();

if ($existingParticipant) {
    header(
        'Location: /participante.php?code='
        . urlencode($gameCode)
        . '&erro=duplicado'
    );

    exit;
}

$reconnectionToken = bin2hex(random_bytes(32));

$statement = $pdo->prepare(
    'INSERT INTO participantes (
        partida_id,
        nome,
        usuario_copel,
        token_reconexao_hash,
        status
    ) VALUES (
        :partida_id,
        :nome,
        :usuario_copel,
        :token_reconexao_hash,
        :status
    )'
);

$statement->execute([
    'partida_id' => $game['id'],
    'nome' => $name,
    'usuario_copel' => $corporateUser,
    'token_reconexao_hash' => hash('sha256', $reconnectionToken),
    'status' => 'conectado',
]);

setcookie(
    'quiz_participant',
    $reconnectionToken,
    [
        'expires' => time() + 60 * 60 * 8,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]
);

header(
    'Location: /sala-espera.php?code='
    . urlencode($gameCode)
);

exit;