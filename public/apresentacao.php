<?php

$branding = require __DIR__ . '/../app/config/branding.php';
$pdo = require __DIR__ . '/../app/config/database.php';


$gameCode = preg_replace(
    '/\D/',
    '',
    $_GET['code'] ?? ''
);


if (strlen($gameCode) !== 6) {
    header('Location: /');
    exit;
}


$statement = $pdo->prepare(
    'SELECT
        partidas.id,
        partidas.codigo,
        partidas.status,
        quizzes.titulo,
        quizzes.subtitulo
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
    exit('Partida não encontrada.');
}


$statement = $pdo->prepare(
    'SELECT COUNT(*)
     FROM participantes
     WHERE partida_id = :partida_id'
);


$statement->execute([
    'partida_id' => $game['id'],
]);


$totalParticipants = (int) $statement->fetchColumn();


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
    Apresentação
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


.presentation-page {

    min-height:100vh;

    display:flex;

    align-items:center;

    justify-content:center;

    background:#f5f6f8;

    padding:40px;

}


.presentation-card {

    width:min(1200px,100%);

    background:white;

    border-radius:30px;

    padding:50px;

    text-align:center;

}


.presentation-title {

    font-size:64px;

    margin-bottom:20px;

}


.presentation-code {

    display:inline-block;

    background:#fff3e8;

    color:var(--primary-dark);

    padding:15px 30px;

    border-radius:15px;

    font-size:42px;

    font-weight:800;

    letter-spacing:5px;

}


.presentation-info {

    margin-top:40px;

    font-size:28px;

    color:var(--muted);

}


.presentation-status {

    margin-top:40px;

    font-size:36px;

    font-weight:700;

    color:var(--primary);

}

.question-box {

    margin-top: 50px;

}


.question-text {

    font-size: 42px;

    font-weight: 700;

    margin-bottom: 35px;

}


.question-options {

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:20px;

}


.question-option {

    padding:25px;

    border-radius:16px;

    background:#f5f6f8;

    font-size:30px;

    font-weight:700;

}


</style>


</head>


<body>


<main class="presentation-page">


<section class="presentation-card">


<h1 class="presentation-title">

<?= htmlspecialchars($game['titulo']) ?>

</h1>


<div class="presentation-code">

<?= htmlspecialchars($formattedCode) ?>

</div>


<div class="presentation-info">

Participantes:

<strong>
<?= $totalParticipants ?>
</strong>

</div>


<div class="presentation-status">

<?= htmlspecialchars($game['status']) ?>

</div>

<div id="question-area">

</div>


</section>


</main>

<script>

const gameCode = "<?= htmlspecialchars($gameCode) ?>";


async function updatePresentation() {


    try {


        const response = await fetch(
            `/api/pergunta-atual.php?code=${gameCode}`
        );


        const data = await response.json();


        const area = document.getElementById(
            'question-area'
        );


        if (!area) {
            return;
        }


        if (!data.pergunta) {

            area.innerHTML = '';

            return;

        }


        const question = data.pergunta;


        area.innerHTML = `

            <div class="question-box">

                <div class="question-text">

                    ${question.enunciado}

                </div>


                <div class="question-options">

                    <div class="question-option">
                        A) ${question.alternativas.A}
                    </div>

                    <div class="question-option">
                        B) ${question.alternativas.B}
                    </div>

                    <div class="question-option">
                        C) ${question.alternativas.C}
                    </div>

                    <div class="question-option">
                        D) ${question.alternativas.D}
                    </div>

                </div>

            </div>

        `;


    } catch(error) {

        console.error(error);

    }

}


updatePresentation();


setInterval(
    updatePresentation,
    3000
);


</script>

</body>

</html>