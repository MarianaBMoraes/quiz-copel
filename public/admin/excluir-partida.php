<?php

require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/jogo.php';

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
    header('Location: /admin/quizzes.php');
    exit;
}


$statement = $pdo->prepare(
    'SELECT id
     FROM partidas
     WHERE codigo = :codigo
     LIMIT 1'
);


$statement->execute([
    'codigo' => $gameCode,
]);


$game = $statement->fetch();


if (!$game) {
    header('Location: /admin/quizzes.php');
    exit;
}


$pdo->beginTransaction();


try {

    $statement = $pdo->prepare(
        'DELETE respostas
         FROM respostas
         INNER JOIN participantes
             ON participantes.id = respostas.participante_id
         WHERE participantes.partida_id = :partida_id'
    );


    $statement->execute([
        'partida_id' => $game['id'],
    ]);


    $statement = $pdo->prepare(
        'DELETE FROM participantes
         WHERE partida_id = :partida_id'
    );


    $statement->execute([
        'partida_id' => $game['id'],
    ]);


    $statement = $pdo->prepare(
        'DELETE FROM partidas
         WHERE id = :id'
    );


    $statement->execute([
        'id' => $game['id'],
    ]);


    $pdo->commit();

    jogoRemoverEstado($gameCode);


} catch (Throwable $error) {

    $pdo->rollBack();

    exit($error->getMessage());

}


header(
    'Location: /admin/quizzes.php'
);

exit;  