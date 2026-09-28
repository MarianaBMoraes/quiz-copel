<?php

$branding = require __DIR__ . '/../app/config/branding.php';

$rawCode = $_GET['code'] ?? '';

$gameCode = preg_replace('/\D/', '', $rawCode);

if (strlen($gameCode) !== 6) {
    header('Location: /');
    exit;
}

$formattedCode =
    substr($gameCode, 0, 3)
    . ' '
    . substr($gameCode, 3, 3);

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
        Identificação | <?= htmlspecialchars($branding['quiz_name']) ?>
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

<body>

    <main class="participant-page">

        <section class="participant-container">

            <img
                src="/assets/images/branding/logo-copel.png"
                alt="Copel"
                class="participant-logo"
            >

            <div class="participant-card">

                <span class="join-label">
                    PARTIDA
                </span>

                <div class="game-code-badge">
                    <?= htmlspecialchars($formattedCode) ?>
                </div>

                <h1 class="participant-title">
                    Identifique-se
                </h1>

                <p class="participant-description">
                    Preencha seus dados para entrar na sala de espera.
                </p>

                <form
                    class="participant-form"
                    action="#"
                    method="post"
                >

                    <input
                        type="hidden"
                        name="code"
                        value="<?= htmlspecialchars($gameCode) ?>"
                    >

                    <div class="form-group">

                        <label for="full-name">
                            Nome completo
                        </label>

                        <input
                            type="text"
                            id="full-name"
                            name="name"
                            autocomplete="name"
                            required
                            placeholder="Digite seu nome completo"
                        >

                    </div>

                    <div class="form-group">

                        <label for="corporate-user">
                            Usuário Copel
                        </label>

                        <input
                            type="text"
                            id="corporate-user"
                            name="corporate_user"
                            autocomplete="off"
                            required
                            placeholder="Digite seu usuário"
                        >

                    </div>

                    <div class="security-notice">
                        Informe somente seu usuário corporativo. Nunca informe sua senha.
                    </div>

                    <button
                        type="submit"
                        class="participant-button"
                    >
                        Entrar na partida
                    </button>

                </form>

                <a
                    href="/"
                    class="back-link"
                >
                    Voltar
                </a>

            </div>

        </section>

    </main>

</body>

</html>