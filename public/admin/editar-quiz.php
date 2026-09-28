<?php

require_once __DIR__ . '/../../app/auth.php';

requireAdmin();

$pdo = require __DIR__ . '/../../app/config/database.php';
$branding = require __DIR__ . '/../../app/config/branding.php';

$quizId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validateAdminCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Solicitação inválida.');
    }

    $quizId = filter_input(
        INPUT_POST,
        'quiz_id',
        FILTER_VALIDATE_INT
    );
}

if (!$quizId) {

    header(
        'Location: /admin/quizzes.php'
    );

    exit;
}

$statement = $pdo->prepare(
    'SELECT *
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

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo = trim(
        $_POST['titulo'] ?? ''
    );

    $subtitulo = trim(
        $_POST['subtitulo'] ?? ''
    );

    $descricao = trim(
        $_POST['descricao'] ?? ''
    );

    $ativo = isset($_POST['ativo'])
        ? 1
        : 0;

    if (
        $titulo === ''
        || strlen($titulo) > 180
        || strlen($subtitulo) > 255
    ) {

        $error = 'Verifique os dados informados.';
    }

    if (!$error) {

        $statement = $pdo->prepare(
            'UPDATE quizzes
             SET
                titulo = :titulo,
                subtitulo = :subtitulo,
                descricao = :descricao,
                ativo = :ativo
             WHERE id = :id'
        );

        $statement->execute([
            'titulo' => $titulo,

            'subtitulo' => $subtitulo !== ''
                ? $subtitulo
                : null,

            'descricao' => $descricao !== ''
                ? $descricao
                : null,

            'ativo' => $ativo,

            'id' => $quizId,
        ]);

        header(
            'Location: /admin/quizzes.php?sucesso=atualizado'
        );

        exit;
    }

    $quiz['titulo'] = $titulo;
    $quiz['subtitulo'] = $subtitulo;
    $quiz['descricao'] = $descricao;
    $quiz['ativo'] = $ativo;
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

    <title>Editar Quiz</title>

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
                Editar Quiz
            </h1>

            <p>
                Altere as informações do quiz.
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
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <section class="admin-panel">

        <div class="admin-panel-header">

            <div>

                <h2>
                    Dados do quiz
                </h2>

                <p>
                    Edite as informações abaixo.
                </p>

            </div>

        </div>

        <form
            method="post"
            class="admin-quiz-form"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrfToken) ?>"
            >

            <input
                type="hidden"
                name="quiz_id"
                value="<?= (int) $quiz['id'] ?>"
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
                    value="<?= htmlspecialchars($quiz['titulo']) ?>"
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
                    value="<?= htmlspecialchars($quiz['subtitulo'] ?? '') ?>"
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
                ><?= htmlspecialchars($quiz['descricao'] ?? '') ?></textarea>

            </div>

            <div class="admin-form-field">

                <label>

                    <input
                        type="checkbox"
                        name="ativo"
                        value="1"
                        <?= (int) $quiz['ativo'] === 1
                            ? 'checked'
                            : ''
                        ?>
                    >

                    Quiz ativo

                </label>

            </div>

            <div class="admin-form-actions">

                <button
                    type="submit"
                    class="admin-button-primary"
                >
                    Salvar alterações
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