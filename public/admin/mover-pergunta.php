<?php

require_once __DIR__ . '/../../app/auth.php';

requireAdmin();

$pdo = require __DIR__ . '/../../app/config/database.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        'Location: /admin/quizzes.php'
    );

    exit;
}


if (!validateAdminCsrf($_POST['csrf_token'] ?? null)) {

    http_response_code(403);

    exit('Solicitação inválida.');
}


$perguntaId = filter_input(
    INPUT_POST,
    'pergunta_id',
    FILTER_VALIDATE_INT
);

$direcao = $_POST['direcao'] ?? null;


if (
    !$perguntaId
    || !in_array(
        $direcao,
        ['cima', 'baixo'],
        true
    )
) {

    header(
        'Location: /admin/quizzes.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Buscar pergunta atual
|--------------------------------------------------------------------------
*/

$statement = $pdo->prepare(
    'SELECT
        id,
        quiz_id,
        ordem
     FROM perguntas
     WHERE id = :id
     LIMIT 1'
);

$statement->execute([
    'id' => $perguntaId,
]);

$pergunta = $statement->fetch();


if (!$pergunta) {

    header(
        'Location: /admin/quizzes.php'
    );

    exit;
}


$quizId = (int) $pergunta['quiz_id'];
$ordemAtual = (int) $pergunta['ordem'];


/*
|--------------------------------------------------------------------------
| Encontrar pergunta vizinha
|--------------------------------------------------------------------------
*/

if ($direcao === 'cima') {

    $statement = $pdo->prepare(
        'SELECT
            id,
            ordem
         FROM perguntas
         WHERE quiz_id = :quiz_id
           AND ordem < :ordem
         ORDER BY ordem DESC
         LIMIT 1'
    );

} else {

    $statement = $pdo->prepare(
        'SELECT
            id,
            ordem
         FROM perguntas
         WHERE quiz_id = :quiz_id
           AND ordem > :ordem
         ORDER BY ordem ASC
         LIMIT 1'
    );

}


$statement->execute([
    'quiz_id' => $quizId,
    'ordem' => $ordemAtual,
]);


$vizinha = $statement->fetch();


/*
|--------------------------------------------------------------------------
| Não existe movimento possível
|--------------------------------------------------------------------------
*/

if (!$vizinha) {

    header(
        'Location: /admin/perguntas.php'
        . '?quiz_id=' . $quizId
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Trocar posições
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    // posição temporária

    $statement = $pdo->prepare(
        'UPDATE perguntas
         SET ordem = 999999
         WHERE id = :id'
    );

    $statement->execute([
        'id' => $perguntaId,
    ]);


    // vizinha recebe ordem atual

    $statement = $pdo->prepare(
        'UPDATE perguntas
         SET ordem = :ordem
         WHERE id = :id'
    );

    $statement->execute([
        'ordem' => $ordemAtual,
        'id' => $vizinha['id'],
    ]);


    // pergunta recebe nova ordem

    $statement = $pdo->prepare(
        'UPDATE perguntas
         SET ordem = :ordem
         WHERE id = :id'
    );

    $statement->execute([
        'ordem' => $vizinha['ordem'],
        'id' => $perguntaId,
    ]);


    $pdo->commit();


} catch (Throwable $exception) {

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    throw $exception;
}


header(
    'Location: /admin/perguntas.php'
    . '?quiz_id=' . $quizId
);

exit;