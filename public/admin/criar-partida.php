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


$quizId = (int) ($_POST['quiz_id'] ?? 0);


if ($quizId <= 0) {
    header('Location: /admin/quizzes.php');
    exit;
}


$statement = $pdo->prepare(
    'SELECT id
     FROM quizzes
     WHERE id = :id
     LIMIT 1'
);

$statement->execute([
    'id' => $quizId,
]);

$quiz = $statement->fetch();


if (!$quiz) {
    header('Location: /admin/quizzes.php');
    exit;
}


do {

    $code = (string) random_int(100000, 999999);

    $statement = $pdo->prepare(
        'SELECT id
         FROM partidas
         WHERE codigo = :codigo
         LIMIT 1'
    );

    $statement->execute([
        'codigo' => $code,
    ]);

    $exists = $statement->fetch();

} while ($exists);



$statement = $pdo->prepare(
    'INSERT INTO partidas (
        quiz_id,
        codigo,
        status
    ) VALUES (
        :quiz_id,
        :codigo,
        :status
    )'
);


$statement->execute([
    'quiz_id' => $quizId,
    'codigo' => $code,
    'status' => 'aguardando',
]);


header(
    'Location: /admin/partida.php?code='
    . urlencode($code)
);

exit;