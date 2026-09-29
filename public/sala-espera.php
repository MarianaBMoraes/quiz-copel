<?php

$branding = require __DIR__ . '/../app/config/branding.php';
$pdo = require __DIR__ . '/../app/config/database.php';

$gameCode = preg_replace('/\D/', '', $_GET['code'] ?? '');

if (strlen($gameCode) !== 6) {
    header('Location: /');
    exit;
}

$statement = $pdo->prepare(
    'SELECT
        partidas.id,
        partidas.codigo,
        partidas.status,
        quizzes.titulo
     FROM partidas
     INNER JOIN quizzes
        ON quizzes.id = partidas.quiz_id
     WHERE partidas.codigo = :codigo
     LIMIT 1'
);

$statement->execute([
    'codigo' => $gameCode,
]);

$game = $statement->fetch();

if (!$game) {
    header('Location: /');
    exit;
}

$statement = $pdo->prepare(
    'SELECT COUNT(*) AS total
     FROM participantes
     WHERE partida_id = :partida_id'
);

$statement->execute([
    'partida_id' => $game['id'],
]);

$totalParticipants = (int) $statement->fetch()['total'];

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
        Sala de espera
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
                    VOCÊ ENTROU!
                </span>

                <div class="game-code-badge">
                    <?= htmlspecialchars($formattedCode) ?>
                </div>

                <h1 class="participant-title">
                    <?= htmlspecialchars($game['titulo']) ?>
                </h1>

                <p class="participant-description">
                    Aguarde o administrador iniciar o jogo.
                </p>

                <div class="waiting-info">

                     <strong id="participant-count">
        <?= $totalParticipants ?>
    </strong>

    participantes conectados


                </div>

                <div
    id="game-started-message"
    class="waiting-info"
    style="display:none;"
>
    <strong>
        O quiz começou!
    </strong>

    Preparando primeira pergunta...
</div>

            </div>

        </section>

    </main>

    <script>

const gameCode = "<?= htmlspecialchars($gameCode) ?>";

async function updateWaitingRoom() {

    try {

        const response = await fetch(
            `/api/status-partida.php?code=${gameCode}`
        );

        const data = await response.json();


        const counter = document.getElementById(
            'participant-count'
        );


        if (counter) {
            counter.textContent = data.participantes;
        }

        if (data.status !== 'aguardando') {

    const message = document.getElementById(
        'game-started-message'
    );

    if (message) {
        message.style.display = 'block';
    }

}

    } catch (error) {

        console.error(
            'Erro ao atualizar sala:',
            error
        );

    }

}


updateWaitingRoom();


setInterval(
    updateWaitingRoom,
    3000
);

</script>

</body>

</html>