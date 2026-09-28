<?php

require_once __DIR__ . '/../../app/auth.php';

requireAdmin();

$branding = require __DIR__ . '/../../app/config/branding.php';

$csrfToken = adminCsrfToken();

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

    <title>Novo Quiz</title>

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
                Novo Quiz
            </h1>

            <p>
                Cadastre um novo quiz na plataforma.
            </p>

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

    <?php if ($error): ?>

        <div class="admin-alert-error">

            <?php if ($error === 'dados'): ?>

                Verifique os dados informados e tente novamente.

            <?php else: ?>

                Não foi possível criar o quiz.

            <?php endif; ?>

        </div>

    <?php endif; ?>

    <section class="admin-panel">

        <div class="admin-panel-header">

            <div>

                <h2>
                    Dados do quiz
                </h2>

                <p>
                    Informe as informações principais.
                </p>

            </div>

        </div>

        <form
            action="/admin/criar-quiz.php"
            method="post"
            class="admin-quiz-form"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrfToken) ?>"
            >

            <div class="admin-form-field">

                <label for="titulo">
                    Título
                </label>

                <input
                    type="text"
                    id="titulo"
                    name="titulo"
                    maxlength="180"
                    required
                >

            </div>

            <div class="admin-form-field">

                <label for="subtitulo">
                    Subtítulo
                </label>

                <input
                    type="text"
                    id="subtitulo"
                    name="subtitulo"
                    maxlength="255"
                >

            </div>

            <div class="admin-form-field">

                <label for="descricao">
                    Descrição
                </label>

                <textarea
                    id="descricao"
                    name="descricao"
                    rows="6"
                ></textarea>

            </div>

            <div class="admin-form-actions">

                <button
                    type="submit"
                    class="admin-button-primary"
                >
                    Criar quiz
                </button>

                <a
                    href="/admin/quizzes.php"
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