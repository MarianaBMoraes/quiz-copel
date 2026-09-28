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

    exit(
        'Solicitação inválida.'
    );
}

$quizId = filter_input(
    INPUT_POST,
    'quiz_id',
    FILTER_VALIDATE_INT
);

if (!$quizId) {

    header(
        'Location: /admin/quizzes.php?erro=id'
    );

    exit;
}

/*
 * Confirma que o quiz realmente existe.
 */

$statement = $pdo->prepare(
    'SELECT
        id,
        titulo
     FROM quizzes
     WHERE id = :id
     LIMIT 1'
);

$statement->execute([
    'id' => $quizId,
]);

$quiz = $statement->fetch();

if (!$quiz) {

    header(
        'Location: /admin/quizzes.php?erro=nao_encontrado'
    );

    exit;
}

/*
 * Não permite excluir um quiz que já possui
 * partidas vinculadas.
 *
 * Isso preserva o histórico do sistema.
 */

$statement = $pdo->prepare(
    'SELECT COUNT(*)
     FROM partidas
     WHERE quiz_id = :quiz_id'
);

$statement->execute([
    'quiz_id' => $quizId,
]);

$totalPartidas = (int) $statement->fetchColumn();

if ($totalPartidas > 0) {

    header(
        'Location: /admin/quizzes.php?erro=possui_partidas'
    );

    exit;
}

/*
 * Se o quiz nunca foi utilizado em uma partida,
 * pode ser excluído.
 *
 * Perguntas e alternativas vinculadas serão
 * excluídas automaticamente pelo banco.
 */

try {

    $statement = $pdo->prepare(
        'DELETE FROM quizzes
         WHERE id = :id'
    );

    $statement->execute([
        'id' => $quizId,
    ]);

} catch (PDOException $exception) {

    /*
     * Proteção extra caso uma partida seja vinculada
     * ao quiz entre a verificação e a exclusão.
     */

    if ($exception->getCode() === '23000') {

        header(
            'Location: /admin/quizzes.php?erro=possui_partidas'
        );

        exit;
    }

    throw $exception;
}

header(
    'Location: /admin/quizzes.php?sucesso=excluido'
);

exit;