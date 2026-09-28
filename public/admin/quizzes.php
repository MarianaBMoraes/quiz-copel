<?php

require_once __DIR__ . '/../../app/auth.php';

requireAdmin();

$pdo = require __DIR__ . '/../../app/config/database.php';
$branding = require __DIR__ . '/../../app/config/branding.php';
$success = $_GET['sucesso'] ?? null;

$statement = $pdo->query(
    'SELECT
        quizzes.id,
        quizzes.titulo,
        quizzes.subtitulo,
        quizzes.descricao,
        quizzes.ativo,
        quizzes.criado_em,
        COUNT(perguntas.id) AS total_perguntas
     FROM quizzes
     LEFT JOIN perguntas
        ON perguntas.quiz_id = quizzes.id
     GROUP BY
        quizzes.id,
        quizzes.titulo,
        quizzes.subtitulo,
        quizzes.descricao,
        quizzes.ativo,
        quizzes.criado_em
     ORDER BY quizzes.id DESC'
);

$quizzes = $statement->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Meus Quizzes</title>

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
                GERENCIAMENTO
            </span>

            <h1>
                Meus Quizzes
            </h1>

            <p>
                Crie e gerencie os quizzes da plataforma.
            </p>

        </div>

        <div class="admin-top-actions">

            <a
                href="/admin/"
                class="admin-nav-link"
            >
                Voltar ao painel
            </a>

            <a
                href="/admin/logout.php"
                class="admin-nav-link"
            >
                Sair
            </a>

        </div>

    </header>

    <?php if ($success === 'criado'): ?>

    <div class="admin-alert-success">
        Quiz criado com sucesso.
    </div>

<?php elseif ($success === 'atualizado'): ?>

    <div class="admin-alert-success">
        Quiz atualizado com sucesso.
    </div>

<?php endif; ?>

    <section class="admin-panel">

        <div class="admin-panel-header">

            <div>

                <h2>
                    Quizzes
                </h2>

                <p>
                    <?= count($quizzes) ?>
                    quiz<?= count($quizzes) === 1 ? '' : 'zes' ?>
                    cadastrado<?= count($quizzes) === 1 ? '' : 's' ?>.
                </p>

            </div>

            <div class="admin-actions">

                <a
    href="/admin/novo-quiz.php"
    class="admin-button-primary admin-button-link"
>
    Novo quiz
</a>

            </div>

        </div>

        <?php if ($quizzes): ?>

            <div class="table-wrapper">

                <table class="participants-table">

                    <thead>

                        <tr>
                            <th>Quiz</th>
                            <th>Perguntas</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($quizzes as $quiz): ?>

                            <tr>

                                <td>

                                    <strong>
                                        <?= htmlspecialchars($quiz['titulo']) ?>
                                    </strong>

                                    <?php if (!empty($quiz['subtitulo'])): ?>

                                        <br>

                                        <span>
                                            <?= htmlspecialchars($quiz['subtitulo']) ?>
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    <?= (int) $quiz['total_perguntas'] ?>
                                </td>

                                <td>

                                    <?php if ((int) $quiz['ativo'] === 1): ?>

                                        <span class="participant-status">
                                            Ativo
                                        </span>

                                    <?php else: ?>

                                        <span class="admin-inactive-badge">
                                            Inativo
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <div class="admin-row-actions">

                                        <a
    href="/admin/editar-quiz.php?id=<?= (int) $quiz['id'] ?>"
    class="admin-nav-link"
>
    Editar
</a>

                                        <span class="action-unavailable">
                                            Perguntas
                                        </span>

                                        <span class="action-unavailable">
                                            Iniciar jogo
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
                    Nenhum quiz cadastrado.
                </strong>

                <p>
                    Crie seu primeiro quiz para começar.
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