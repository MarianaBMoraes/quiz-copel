<?php

require_once __DIR__ . '/../../app/auth.php';

requireAdmin();

$pdo = require __DIR__ . '/../../app/config/database.php';
$branding = require __DIR__ . '/../../app/config/branding.php';

$quizId = filter_input(
    INPUT_GET,
    'quiz_id',
    FILTER_VALIDATE_INT
);

if (!$quizId) {

    header(
        'Location: /admin/quizzes.php?erro=quiz_invalido'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Buscar quiz
|--------------------------------------------------------------------------
*/

$statement = $pdo->prepare(
    'SELECT
        id,
        titulo,
        subtitulo,
        ativo
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
|--------------------------------------------------------------------------
| Buscar perguntas do quiz
|--------------------------------------------------------------------------
*/

$statement = $pdo->prepare(
    'SELECT
        perguntas.id,
        perguntas.ordem,
        perguntas.enunciado,
        perguntas.explicacao,
        perguntas.tempo_leitura,
        perguntas.tempo_resposta,
        perguntas.criado_em
     FROM perguntas
     WHERE perguntas.quiz_id = :quiz_id
     ORDER BY perguntas.ordem ASC'
);

$statement->execute([
    'quiz_id' => $quizId,
]);

$perguntas = $statement->fetchAll();

$totalPerguntas = count($perguntas);

$success = $_GET['sucesso'] ?? null;
$error = $_GET['erro'] ?? null;

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
        Perguntas | <?= htmlspecialchars($quiz['titulo']) ?>
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
                GERENCIAMENTO DE PERGUNTAS
            </span>

            <h1>
                <?= htmlspecialchars($quiz['titulo']) ?>
            </h1>

            <?php if (!empty($quiz['subtitulo'])): ?>

                <p>
                    <?= htmlspecialchars($quiz['subtitulo']) ?>
                </p>

            <?php endif; ?>

        </div>

        <div class="admin-top-actions">

            <a
                href="/admin/quizzes.php"
                class="admin-nav-link"
            >
                Voltar aos quizzes
            </a>

            <a
                href="/admin/logout.php"
                class="admin-nav-link"
            >
                Sair
            </a>

        </div>

    </header>

    <?php if ($success === 'criada'): ?>

        <div class="admin-alert-success">
            Pergunta criada com sucesso.
        </div>

    <?php elseif ($success === 'atualizada'): ?>

        <div class="admin-alert-success">
            Pergunta atualizada com sucesso.
        </div>

    <?php elseif ($success === 'excluida'): ?>

        <div class="admin-alert-success">
            Pergunta excluída com sucesso.
        </div>

    <?php endif; ?>


    <?php if ($error === 'nao_encontrada'): ?>

        <div class="admin-alert-error">
            Pergunta não encontrada.
        </div>

    <?php endif; ?>


    <section class="admin-panel">

        <div class="admin-panel-header">

            <div>

                <h2>
                    Perguntas
                </h2>

                <p>
                    <?= $totalPerguntas ?>

                    <?= $totalPerguntas === 1
                        ? 'pergunta cadastrada'
                        : 'perguntas cadastradas'
                    ?>
                </p>

            </div>

            <div class="admin-actions">

                <button
                    type="button"
                    class="admin-button-primary"
                    disabled
                    title="Será liberado na próxima etapa."
                >
                    Nova pergunta
                </button>

            </div>

        </div>


        <?php if ($perguntas): ?>

            <div class="table-wrapper">

                <table class="participants-table">

                    <thead>

                        <tr>
                            <th>Ordem</th>
                            <th>Pergunta</th>
                            <th>Leitura</th>
                            <th>Resposta</th>
                            <th>Ações</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($perguntas as $pergunta): ?>

                            <tr>

                                <td>
                                    <?= (int) $pergunta['ordem'] ?>
                                </td>

                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $pergunta['enunciado']
                                        ) ?>
                                    </strong>

                                    <?php if (!empty($pergunta['explicacao'])): ?>

                                        <br>

                                        <span class="action-unavailable">
                                            Possui explicação/aprendizado
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    <?= (int) $pergunta['tempo_leitura'] ?>s
                                </td>

                                <td>
                                    <?= (int) $pergunta['tempo_resposta'] ?>s
                                </td>

                                <td>

                                    <div class="admin-row-actions">

                                        <span class="action-unavailable">
                                            Editar
                                        </span>

                                        <span class="action-unavailable">
                                            Excluir
                                        </span>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-state">

                <strong>
                    Nenhuma pergunta cadastrada.
                </strong>

                <p>
                    Cadastre a primeira pergunta deste quiz.
                </p>

            </div>

        <?php endif; ?>

    </section>


    <div class="admin-warning">
        Ambiente de desenvolvimento.
    </div>

</main>

</body>

</html>