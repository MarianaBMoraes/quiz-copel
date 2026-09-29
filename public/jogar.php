<?php

$branding = require __DIR__ . '/../app/config/branding.php';
$pdo = require __DIR__ . '/../app/config/database.php';


$gameCode = preg_replace(
    '/\D/',
    '',
    $_GET['code'] ?? ''
);

$answered = ($_GET['respondido'] ?? null) === '1';


if (strlen($gameCode) !== 6) {
    header('Location: /');
    exit;
}


// Busca participante pelo cookie

$token = $_COOKIE['quiz_participant'] ?? null;


if (!$token) {
    exit('Participante não identificado.');
}


$tokenHash = hash('sha256', $token);


$statement = $pdo->prepare(
    'SELECT
        participantes.id,
        participantes.partida_id
     FROM participantes
     WHERE token_reconexao_hash = :token
     LIMIT 1'
);


$statement->execute([
    'token' => $tokenHash,
]);


$participant = $statement->fetch();


if (!$participant) {
    exit('Participante não encontrado.');
}


// Busca partida

$statement = $pdo->prepare(
    'SELECT
        partidas.status,
        partidas.pergunta_atual_id,
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
    exit('Partida não encontrada.');
}


if (!$game['pergunta_atual_id']) {
    exit('Aguardando pergunta.');
}


// Busca pergunta

$statement = $pdo->prepare(
    'SELECT
        id,
        enunciado
     FROM perguntas
     WHERE id = :id
     LIMIT 1'
);


$statement->execute([
    'id' => $game['pergunta_atual_id'],
]);


$question = $statement->fetch();


if (!$question) {
    exit('Pergunta não encontrada.');
}


// Busca alternativas

$statement = $pdo->prepare(
    'SELECT
        letra
     FROM alternativas
     WHERE pergunta_id = :pergunta_id
     ORDER BY letra'
);


$statement->execute([
    'pergunta_id' => $question['id'],
]);


$alternatives = $statement->fetchAll();

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
    Resposta
</title>


<link
    rel="stylesheet"
    href="/assets/css/style.css"
>


<style>

.answer-page {

    min-height:100vh;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:30px;

    background:#f5f6f8;

}


.answer-card {

    width:min(500px,100%);

    background:white;

    padding:35px;

    border-radius:24px;

    text-align:center;

}


.answer-title {

     font-size:24px;

    font-weight:700;

    margin-bottom:20px;

}


.answer-question {

    font-size:20px;

    font-weight:700;

    line-height:1.4;

    margin-bottom:30px;

}

.answer-card h1 {

    font-size:24px;

    letter-spacing:0;

}


.answer-buttons {

    display:grid;

    gap:15px;

}


.answer-button {

    padding:22px;

    border-radius:14px;

    border:1px solid #ddd;

    background:white;

    font-size:28px;

    font-weight:800;

}


</style>


</head>


<body>


<main class="answer-page">


<section class="answer-card">


<h1 class="answer-title">

<?= htmlspecialchars($game['titulo']) ?>

</h1>


<div class="answer-question">

<?= htmlspecialchars($question['enunciado']) ?>

</div>


<?php if ($answered): ?>

<div class="waiting-info">

    <strong>
        ✓ Resposta registrada
    </strong>

    Aguarde o resultado.

</div>

<?php else: ?>

<div class="answer-buttons">

<?php foreach ($alternatives as $alternative): ?>

<form
    action="/registrar-resposta.php"
    method="post"
>

<input
    type="hidden"
    name="code"
    value="<?= htmlspecialchars($gameCode) ?>"
>

<input
    type="hidden"
    name="alternative"
    value="<?= htmlspecialchars($alternative['letra']) ?>"
>

<button
    type="submit"
    class="answer-button"
>
    <?= htmlspecialchars($alternative['letra']) ?>
</button>

</form>

<?php endforeach; ?>

</div>

<?php endif; ?>


</section>


</main>


</body>

</html>