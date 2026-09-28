<?php

$branding = require __DIR__ . '/../app/config/branding.php';

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
        <?= htmlspecialchars($branding['quiz_name']) ?>
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
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

    <main class="page">

        <section class="landing">

            <header class="header">

                <img
                    src="/assets/images/branding/logo-copel.png"
                    alt="Copel"
                    class="logo"
                >

                <a
                    href="#"
                    class="admin-link"
                >
                    Área administrativa
                </a>

            </header>

            <div class="content">

                <div class="hero-content">

                    <p class="eyebrow">
                        QUIZ AO VIVO
                    </p>

                    <h1>
                        <?= htmlspecialchars($branding['quiz_name']) ?>
                    </h1>

                    <h2>
                        <?= htmlspecialchars($branding['quiz_subtitle']) ?>
                    </h2>

                    <p class="description">
                        Participe do treinamento interativo e acompanhe os resultados em tempo real.
                    </p>

                </div>

                <div class="join-card">
                    
                 <span class="join-label">
        PARTICIPAR
    </span>

    <h3>
        Entre na partida
    </h3>

    <p>
        Digite o código exibido na tela da apresentação.
    </p>

    <?php if ($error === 'partida'): ?>

        <div class="form-error">
            Código inválido ou partida indisponível.
        </div>

    <?php endif; ?>

    <form action="/participante.php" method="get">

        <label for="game-code">
            Código da partida
        </label>

                        <input
                            type="text"
                            id="game-code"
                            name="code"
                            placeholder="000 000"
                            inputmode="numeric"
                            maxlength="7"
                            autocomplete="off"
                            required
                            data-game-code
                        >

                        <button type="submit">
                            Entrar no quiz
                        </button>

                    </form>

                    <div class="qr-info">
                        Você também poderá entrar pelo QR Code exibido durante o evento.
                    </div>

                </div>

            </div>

            <footer class="footer">

                <span>
                    Treinamento interativo
                </span>

                <span class="status">
                    Plataforma em desenvolvimento
                </span>

            </footer>

        </section>

    </main>
    
    <script src="/assets/js/app.js"></script>

</body>

</html>