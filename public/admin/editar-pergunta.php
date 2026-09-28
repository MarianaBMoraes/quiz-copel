<?php

require_once __DIR__ . '/../../app/auth.php';

requireAdmin();

$pdo = require __DIR__ . '/../../app/config/database.php';
$branding = require __DIR__ . '/../../app/config/branding.php';


/*
|--------------------------------------------------------------------------
| Identificar pergunta
|--------------------------------------------------------------------------
*/

$perguntaId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
}


if (!$perguntaId) {

    header(
        'Location: /admin/quizzes.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Buscar pergunta e quiz
|--------------------------------------------------------------------------
*/

$statement = $pdo->prepare(
    'SELECT
        perguntas.id,
        perguntas.quiz_id,
        perguntas.ordem,
        perguntas.enunciado,
        perguntas.explicacao,
        perguntas.tempo_leitura,
        perguntas.tempo_resposta,
        quizzes.titulo AS quiz_titulo,
        quizzes.subtitulo AS quiz_subtitulo
     FROM perguntas
     INNER JOIN quizzes
        ON quizzes.id = perguntas.quiz_id
     WHERE perguntas.id = :id
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


/*
|--------------------------------------------------------------------------
| Proteger perguntas que já possuem respostas
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
| Buscar alternativas
|--------------------------------------------------------------------------
*/

$statement = $pdo->prepare(
    'SELECT
        id,
        letra,
        texto,
        correta
     FROM alternativas
     WHERE pergunta_id = :pergunta_id
     ORDER BY FIELD(letra, "A", "B", "C", "D")'
);

$statement->execute([
    'pergunta_id' => $perguntaId,
]);

$alternativasBanco = $statement->fetchAll();


$alternativas = [];

$letraCorreta = null;


foreach ($alternativasBanco as $alternativa) {

    $alternativas[$alternativa['letra']] = $alternativa['texto'];

    if ((int) $alternativa['correta'] === 1) {
        $letraCorreta = $alternativa['letra'];
    }
}


/*
|--------------------------------------------------------------------------
| Garantir estrutura completa
|--------------------------------------------------------------------------
*/

foreach (['A', 'B', 'C', 'D'] as $letra) {

    if (!array_key_exists($letra, $alternativas)) {

        http_response_code(500);

        exit(
            'A pergunta possui alternativas incompletas.'
        );
    }
}


$error = null;


/*
|--------------------------------------------------------------------------
| Processar edição
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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

        $error = 'Preencha todos os campos obrigatórios corretamente.';
    }


    if (
        !$error
        && !in_array(
            $correta,
            ['A', 'B', 'C', 'D'],
            true
        )
    ) {

        $error = 'Selecione uma alternativa correta.';
    }


    /*
     * Manter os dados preenchidos caso exista erro.
     */

    if ($error) {

        $pergunta['enunciado'] = $enunciado;
        $pergunta['explicacao'] = $explicacao;
        $pergunta['tempo_leitura'] = $tempoLeitura;
        $pergunta['tempo_resposta'] = $tempoResposta;

        $alternativas = [
            'A' => $alternativaA,
            'B' => $alternativaB,
            'C' => $alternativaC,
            'D' => $alternativaD,
        ];

        $letraCorreta = $correta;
    }


    /*
    |--------------------------------------------------------------------------
    | Salvar alterações
    |--------------------------------------------------------------------------
    */

    if (!$error) {

        try {

            $pdo->beginTransaction();


            /*
             * Atualizar pergunta.
             */

            $statement = $pdo->prepare(
                'UPDATE perguntas
                 SET
                    enunciado = :enunciado,
                    explicacao = :explicacao,
                    tempo_leitura = :tempo_leitura,
                    tempo_resposta = :tempo_resposta
                 WHERE id = :id'
            );

            $statement->execute([
                'enunciado' => $enunciado,

                'explicacao' => $explicacao !== ''
                    ? $explicacao
                    : null,

                'tempo_leitura' => $tempoLeitura,
                'tempo_resposta' => $tempoResposta,

                'id' => $perguntaId,
            ]);


            /*
             * Atualizar alternativas existentes.
             */

            $novasAlternativas = [
                'A' => $alternativaA,
                'B' => $alternativaB,
                'C' => $alternativaC,
                'D' => $alternativaD,
            ];


            $statement = $pdo->prepare(
                'UPDATE alternativas
                 SET
                    texto = :texto,
                    correta = :correta
                 WHERE pergunta_id = :pergunta_id
                   AND letra = :letra'
            );


            foreach ($novasAlternativas as $letra => $texto) {

                $statement->execute([
                    'texto' => $texto,

                    'correta' => $letra === $correta
                        ? 1
                        : 0,

                    'pergunta_id' => $perguntaId,
                    'letra' => $letra,
                ]);
            }


            $pdo->commit();


            header(
                'Location: /admin/perguntas.php'
                . '?quiz_id=' . $quizId
                . '&sucesso=atualizada'
            );

            exit;


        } catch (Throwable $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = 'Não foi possível salvar as alterações.';
        }
    }
}


$csrfToken = adminCsrfToken();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Editar Pergunta | <?= htmlspecialchars($pergunta['quiz_titulo']) ?>
    </title>

    <link
        rel="stylesheet"
        href="/assets/css/style.css"
    >

    <style>
        :root {
            --primary: <?= htmlspecialchars($branding['colors']['primary']) ?>;
            --primary-dark: <?= htmlspecialchars($branding['colors']['primary_dark']) ?>;
            --background: <?= htmlspecialchars($branding['colors']['background']) ?>;
            --surface: <?= htmlspecialchars($branding['colors']['surface']) ?>;
            --text: <?= htmlspecialchars($branding['colors']['text']) ?>;
            --muted: <?= htmlspecialchars($branding['colors']['muted']) ?>;
        }
    </style>

</head>

<body class="admin-body">

<main class="admin-page">

    <header class="admin-header">

        <div>

            <span class="admin-eyebrow">
                EDITAR PERGUNTA
            </span>

            <h1>
                <?= htmlspecialchars($pergunta['quiz_titulo']) ?>
            </h1>

            <p>
                Pergunta <?= (int) $pergunta['ordem'] ?>
            </p>

        </div>

        <div class="admin-top-actions">

            <a
                href="/admin/perguntas.php?quiz_id=<?= $quizId ?>"
                class="admin-nav-link"
            >
                Voltar às perguntas
            </a>

            <a
                href="/admin/logout.php"
                class="admin-nav-link"
            >
                Sair
            </a>

        </div>

    </header>


    <?php if ($error): ?>

        <div class="admin-alert-error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <section class="admin-panel">

        <div class="admin-panel-header">

            <div>

                <h2>
                    Dados da pergunta
                </h2>

                <p>
                    Altere o conteúdo e salve quando terminar.
                </p>

            </div>

        </div>


        <form
            method="post"
            class="question-form"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrfToken) ?>"
            >

            <input
                type="hidden"
                name="pergunta_id"
                value="<?= (int) $pergunta['id'] ?>"
            >


            <div class="admin-form-field">

                <label for="enunciado">
                    Enunciado *
                </label>

                <textarea
                    id="enunciado"
                    name="enunciado"
                    rows="5"
                    required
                ><?= htmlspecialchars($pergunta['enunciado']) ?></textarea>

            </div>


            <div class="question-alternatives">

                <h3>
                    Alternativas
                </h3>

                <p class="question-help">
                    Altere os textos e marque apenas uma alternativa como correta.
                </p>


                <?php foreach (['A', 'B', 'C', 'D'] as $letra): ?>

                    <div class="alternative-field">

                        <div class="alternative-letter">
                            <?= $letra ?>
                        </div>

                        <input
                            type="text"
                            name="alternativa_<?= strtolower($letra) ?>"
                            value="<?= htmlspecialchars($alternativas[$letra]) ?>"
                            required
                        >

                        <label class="correct-option">

                            <input
                                type="radio"
                                name="correta"
                                value="<?= $letra ?>"
                                <?= $letraCorreta === $letra
                                    ? 'checked'
                                    : ''
                                ?>
                                required
                            >

                            Correta

                        </label>

                    </div>

                <?php endforeach; ?>

            </div>


            <div class="admin-form-field">

                <label for="explicacao">
                    Explicação / aprendizado
                </label>

                <textarea
                    id="explicacao"
                    name="explicacao"
                    rows="5"
                ><?= htmlspecialchars($pergunta['explicacao'] ?? '') ?></textarea>

            </div>


            <div class="question-times">

                <div class="admin-form-field">

                    <label for="tempo_leitura">
                        Tempo de leitura
                    </label>

                    <div class="time-input">

                        <input
                            type="number"
                            id="tempo_leitura"
                            name="tempo_leitura"
                            value="<?= (int) $pergunta['tempo_leitura'] ?>"
                            min="1"
                            max="300"
                            required
                        >

                        <span>
                            segundos
                        </span>

                    </div>

                </div>


                <div class="admin-form-field">

                    <label for="tempo_resposta">
                        Tempo de resposta
                    </label>

                    <div class="time-input">

                        <input
                            type="number"
                            id="tempo_resposta"
                            name="tempo_resposta"
                            value="<?= (int) $pergunta['tempo_resposta'] ?>"
                            min="1"
                            max="300"
                            required
                        >

                        <span>
                            segundos
                        </span>

                    </div>

                </div>

            </div>


            <div class="admin-form-actions">

                <button
                    type="submit"
                    class="admin-button-primary"
                >
                    Salvar alterações
                </button>

                <a
                    href="/admin/perguntas.php?quiz_id=<?= $quizId ?>"
                    class="admin-nav-link"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </section>

</main>

</body>

</html>