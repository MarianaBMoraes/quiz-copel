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


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (!validateAdminCsrf($_POST['csrf_token'] ?? null)) {

    http_response_code(403);

    exit(
        'Solicitação inválida.'
    );
}


/*
|--------------------------------------------------------------------------
| Receber dados
|--------------------------------------------------------------------------
*/

$quizId = filter_input(
    INPUT_POST,
    'quiz_id',
    FILTER_VALIDATE_INT
);

$enunciado = trim(
    $_POST['enunciado'] ?? ''
);

$alternativaA = trim(
    $_POST['alternativa_a'] ?? ''
);

$alternativaB = trim(
    $_POST['alternativa_b'] ?? ''
);

$alternativaC = trim(
    $_POST['alternativa_c'] ?? ''
);

$alternativaD = trim(
    $_POST['alternativa_d'] ?? ''
);

$correta = strtoupper(
    trim($_POST['correta'] ?? '')
);

$explicacao = trim(
    $_POST['explicacao'] ?? ''
);

$tempoLeitura = filter_input(
    INPUT_POST,
    'tempo_leitura',
    FILTER_VALIDATE_INT
);

$tempoResposta = filter_input(
    INPUT_POST,
    'tempo_resposta',
    FILTER_VALIDATE_INT
);


/*
|--------------------------------------------------------------------------
| Validar quiz
|--------------------------------------------------------------------------
*/

if (!$quizId) {

    header(
        'Location: /admin/quizzes.php?erro=quiz_invalido'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Validar dados obrigatórios
|--------------------------------------------------------------------------
*/

if (
    $enunciado === ''
    || $alternativaA === ''
    || $alternativaB === ''
    || $alternativaC === ''
    || $alternativaD === ''
    || !$tempoLeitura
    || !$tempoResposta
    || $tempoLeitura < 1
    || $tempoLeitura > 300
    || $tempoResposta < 1
    || $tempoResposta > 300
) {

    header(
        'Location: /admin/nova-pergunta.php'
        . '?quiz_id=' . $quizId
        . '&erro=dados'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Validar alternativa correta
|--------------------------------------------------------------------------
*/

if (
    !in_array(
        $correta,
        ['A', 'B', 'C', 'D'],
        true
    )
) {

    header(
        'Location: /admin/nova-pergunta.php'
        . '?quiz_id=' . $quizId
        . '&erro=alternativa'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Confirmar existência do quiz
|--------------------------------------------------------------------------
*/

$statement = $pdo->prepare(
    'SELECT id
     FROM quizzes
     WHERE id = :id
     LIMIT 1'
);

$statement->execute([
    'id' => $quizId,
]);

if (!$statement->fetch()) {

    header(
        'Location: /admin/quizzes.php?erro=nao_encontrado'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Salvar pergunta e alternativas
|--------------------------------------------------------------------------
|
| Utilizamos uma transação.
|
| Se qualquer parte falhar, nada fica salvo pela metade.
|
*/

try {

    $pdo->beginTransaction();


    /*
     * Bloqueia temporariamente o quiz durante
     * a definição da próxima ordem.
     */

    $statement = $pdo->prepare(
        'SELECT id
         FROM quizzes
         WHERE id = :id
         FOR UPDATE'
    );

    $statement->execute([
        'id' => $quizId,
    ]);


    /*
     * Descobrir a próxima posição da pergunta.
     */

    $statement = $pdo->prepare(
        'SELECT COALESCE(MAX(ordem), 0) + 1
         FROM perguntas
         WHERE quiz_id = :quiz_id'
    );

    $statement->execute([
        'quiz_id' => $quizId,
    ]);

    $proximaOrdem = (int) $statement->fetchColumn();


    /*
     * Criar pergunta.
     */

    $statement = $pdo->prepare(
        'INSERT INTO perguntas (
            quiz_id,
            ordem,
            enunciado,
            explicacao,
            tempo_leitura,
            tempo_resposta
        ) VALUES (
            :quiz_id,
            :ordem,
            :enunciado,
            :explicacao,
            :tempo_leitura,
            :tempo_resposta
        )'
    );

    $statement->execute([
        'quiz_id' => $quizId,
        'ordem' => $proximaOrdem,
        'enunciado' => $enunciado,

        'explicacao' => $explicacao !== ''
            ? $explicacao
            : null,

        'tempo_leitura' => $tempoLeitura,
        'tempo_resposta' => $tempoResposta,
    ]);

    $perguntaId = (int) $pdo->lastInsertId();


    /*
     * Preparar alternativas.
     */

    $alternativas = [
        'A' => $alternativaA,
        'B' => $alternativaB,
        'C' => $alternativaC,
        'D' => $alternativaD,
    ];


    $statement = $pdo->prepare(
        'INSERT INTO alternativas (
            pergunta_id,
            letra,
            texto,
            correta
        ) VALUES (
            :pergunta_id,
            :letra,
            :texto,
            :correta
        )'
    );


    /*
     * Salvar A, B, C e D.
     */

    foreach ($alternativas as $letra => $texto) {

        $statement->execute([
            'pergunta_id' => $perguntaId,
            'letra' => $letra,
            'texto' => $texto,
            'correta' => $letra === $correta
                ? 1
                : 0,
        ]);
    }


    /*
     * Confirmar toda a operação.
     */

    $pdo->commit();


} catch (Throwable $exception) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header(
        'Location: /admin/nova-pergunta.php'
        . '?quiz_id=' . $quizId
        . '&erro=salvar'
    );

    exit;
}


header(
    'Location: /admin/perguntas.php'
    . '?quiz_id=' . $quizId
    . '&sucesso=criada'
);

exit;