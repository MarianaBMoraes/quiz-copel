<?php

require_once __DIR__ . '/../app/jogo.php';

$pdo = require __DIR__ . '/../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$gameCode = preg_replace('/\D/', '', $_POST['code'] ?? '');
$name = trim(preg_replace('/\s+/u', ' ', $_POST['name'] ?? ''));
$corporateUser = trim($_POST['corporate_user'] ?? '');

if (
    strlen($gameCode) !== 6 ||
    $name === '' ||
    $corporateUser === ''
) {
    header('Location: /?erro=dados');
    exit;
}

$name = mb_substr($name, 0, 160);
$corporateUser = mb_substr($corporateUser, 0, 100);

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

// Quem chega atrasado ainda entra: só perde as perguntas que já passaram.
if (!$game || $game['status'] === 'finalizado') {
    header('Location: /?erro=partida');
    exit;
}

$statement = $pdo->prepare(
    'SELECT id, nome
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

$reconnectionToken = bin2hex(random_bytes(32));

if ($existingParticipant) {

    // Mesmo usuário e mesmo nome = a pessoa trocou de celular ou de navegador.
    // Ela volta pra mesma participação, com os pontos que já tinha.
    $sameName = mb_strtolower($existingParticipant['nome'])
        === mb_strtolower($name);

    if (!$sameName) {
        header(
            'Location: /participante.php?code='
            . urlencode($gameCode)
            . '&erro=duplicado'
        );

        exit;
    }

    $statement = $pdo->prepare(
        'UPDATE participantes
         SET
            token_reconexao_hash = :token_reconexao_hash,
            status = :status,
            ultima_atividade_em = NOW(6)
         WHERE id = :id'
    );

    $statement->execute([
        'token_reconexao_hash' => hash('sha256', $reconnectionToken),
        'status' => 'conectado',
        'id' => $existingParticipant['id'],
    ]);

} else {

    try {
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
    } catch (PDOException $exception) {
        // Dois envios ao mesmo tempo com o mesmo usuário: o banco barra o segundo.
        if ($exception->getCode() !== '23000') {
            throw $exception;
        }

        header(
            'Location: /participante.php?code='
            . urlencode($gameCode)
            . '&erro=duplicado'
        );

        exit;
    }

}

setcookie(
    'quiz_participant',
    $reconnectionToken,
    [
        'expires' => time() + 60 * 60 * 8,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]
);

jogoPublicarEstado($pdo, $gameCode);

header(
    'Location: /jogar.php?code='
    . urlencode($gameCode)
);

exit;
