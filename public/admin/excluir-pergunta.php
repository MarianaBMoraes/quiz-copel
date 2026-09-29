<?php

require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/jogo.php';

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

    exit(
        'Solicitação inválida.'
    );
}


$perguntaId = filter_input(
    INPUT_POST,
    'pergunta_id',
    FILTER_VALIDATE_INT
);


if (!$perguntaId) {

    header(
        'Location: /admin/quizzes.php?erro=id'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Buscar pergunta
|--------------------------------------------------------------------------
*/

$statement = $pdo->prepare(
    'SELECT
        id,
        quiz_id
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
        'Location: /admin/quizzes.php?erro=nao_encontrada'
    );

    exit;
}


$quizId = (int) $pergunta['quiz_id'];

// Com partida em andamento, mexer nas perguntas quebraria o jogo.
if (jogoQuizEmAndamento($pdo, $quizId)) {
    header(
        'Location: /admin/perguntas.php'
        . '?quiz_id=' . $quizId
        . '&erro=partida_andamento'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Verificar respostas existentes
|--------------------------------------------------------------------------
*/

$statement = $pdo->prepare(
    'SELECT COUNT(*)
     FROM respostas
     WHERE pergunta_id = :pergunta_id'
);

$statement->execute([
    'pergunta_id' => $perguntaId,
]);


$totalRespostas = (int) $statement->fetchColumn();


if ($totalRespostas > 0) {

    header(
        'Location: /admin/perguntas.php'
        . '?quiz_id=' . $quizId
        . '&erro=possui_respostas'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Excluir pergunta
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    /*
     * Alternativas serão removidas pelo relacionamento
     * do banco.
     */

    $statement = $pdo->prepare(
        'DELETE FROM perguntas
         WHERE id = :id'
    );


    $statement->execute([
        'id' => $perguntaId,
    ]);


    $pdo->commit();


} catch (Throwable $exception) {

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    header(
        'Location: /admin/perguntas.php'
        . '?quiz_id=' . $quizId
        . '&erro=excluir'
    );

    exit;
}


header(
    'Location: /admin/perguntas.php'
    . '?quiz_id=' . $quizId
    . '&sucesso=excluida'
);

exit;