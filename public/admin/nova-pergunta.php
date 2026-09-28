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
        subtitulo
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

    <title>
        Nova Pergunta | <?= htmlspecialchars($quiz['titulo']) ?>
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
                NOVA PERGUNTA
            </span>

            <h1>
                <?= htmlspecialchars($quiz['titulo']) ?>
            </h1>

            <p>
                Cadastre a pergunta e suas quatro alternativas.
            </p>

        </div>

        <div class="admin-top-actions">

            <a
                href="/admin/perguntas.php?quiz_id=<?= (int) $quiz['id'] ?>"
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


    <?php if ($error === 'dados'): ?>

        <div class="admin-alert-error">
            Preencha todos os campos obrigatórios corretamente.
        </div>

    <?php elseif ($error === 'alternativa'): ?>

        <div class="admin-alert-error">
            Selecione uma alternativa correta.
        </div>

    <?php elseif ($error === 'salvar'): ?>

        <div class="admin-alert-error">
            Não foi possível salvar a pergunta.
        </div>

    <?php endif; ?>


    <section class="admin-panel">

        <div class="admin-panel-header">

            <div>

                <h2>
                    Dados da pergunta
                </h2>

                <p>
                    Os tempos padrão são 15 segundos para leitura e 20 segundos para resposta.
                </p>

            </div>

        </div>


        <form
            action="/admin/criar-pergunta.php"
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
                name="quiz_id"
                value="<?= (int) $quiz['id'] ?>"
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
                ></textarea>

            </div>


            <div class="question-alternatives">

                <h3>
                    Alternativas
                </h3>

                <p class="question-help">
                    Preencha as quatro alternativas e marque qual delas é a correta.
                </p>


                <div class="alternative-field">

                    <div class="alternative-letter">
                        A
                    </div>

                    <input
                        type="text"
                        name="alternativa_a"
                        placeholder="Digite a alternativa A"
                        required
                    >

                    <label class="correct-option">

                        <input
                            type="radio"
                            name="correta"
                            value="A"
                            required
                        >

                        Correta

                    </label>

                </div>


                <div class="alternative-field">

                    <div class="alternative-letter">
                        B
                    </div>

                    <input
                        type="text"
                        name="alternativa_b"
                        placeholder="Digite a alternativa B"
                        required
                    >

                    <label class="correct-option">

                        <input
                            type="radio"
                            name="correta"
                            value="B"
                            required
                        >

                        Correta

                    </label>

                </div>


                <div class="alternative-field">

                    <div class="alternative-letter">
                        C
                    </div>

                    <input
                        type="text"
                        name="alternativa_c"
                        placeholder="Digite a alternativa C"
                        required
                    >

                    <label class="correct-option">

                        <input
                            type="radio"
                            name="correta"
                            value="C"
                            required
                        >

                        Correta

                    </label>

                </div>


                <div class="alternative-field">

                    <div class="alternative-letter">
                        D
                    </div>

                    <input
                        type="text"
                        name="alternativa_d"
                        placeholder="Digite a alternativa D"
                        required
                    >

                    <label class="correct-option">

                        <input
                            type="radio"
                            name="correta"
                            value="D"
                            required
                        >

                        Correta

                    </label>

                </div>

            </div>


            <div class="admin-form-field">

                <label for="explicacao">
                    Explicação / aprendizado
                </label>

                <textarea
                    id="explicacao"
                    name="explicacao"
                    rows="5"
                    placeholder="Opcional. Será utilizada após a resposta da questão."
                ></textarea>

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
                            value="15"
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
                            value="20"
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
                    Salvar pergunta
                </button>

                <a
                    href="/admin/perguntas.php?quiz_id=<?= (int) $quiz['id'] ?>"
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