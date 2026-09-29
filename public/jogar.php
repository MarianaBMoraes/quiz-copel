<?php

/*
| Tela do participante no celular. A página carrega uma vez e o
| assets/js/jogar.js acompanha a partida pelo arquivo de estado.
*/

require_once __DIR__ . '/../app/jogo.php';

$branding = require __DIR__ . '/../app/config/branding.php';
$pdo = require __DIR__ . '/../app/config/database.php';

$gameCode = jogoCodigoValido($_GET['code'] ?? null);

if (!$gameCode) {
    header('Location: /');
    exit;
}

$game = jogoCarregarPartida($pdo, $gameCode);

if (!$game) {
    header('Location: /?erro=partida');
    exit;
}

$participant = jogoParticipantePorCookie($pdo, (int) $game['id']);

if (!$participant) {
    header('Location: /participante.php?code=' . urlencode($gameCode));
    exit;
}

// Garante que o arquivo de estado exista antes do primeiro fetch.
jogoPublicarEstado($pdo, $gameCode);

$formattedCode = substr($gameCode, 0, 3) . ' ' . substr($gameCode, 3, 3);

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#f3f4f6">

    <title><?= htmlspecialchars($game['titulo']) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,400..800&display=swap">
    <link rel="stylesheet" href="/assets/css/jogo.css">

    <?php require __DIR__ . '/../app/views/cores-marca.php'; ?>
</head>

<body class="tela-jogo">

    <main
        class="jogo"
        data-jogo
        data-code="<?= htmlspecialchars($gameCode) ?>"
    >

        <header class="jogo-topo">
            <img
                src="<?= htmlspecialchars($branding['logo']) ?>"
                alt="<?= htmlspecialchars($branding['logo_alt']) ?>"
            >

            <div class="jogo-topo-info">
                <strong><?= htmlspecialchars($participant['nome']) ?></strong>
                Partida <?= htmlspecialchars($formattedCode) ?>
            </div>
        </header>

        <section class="jogo-tela" data-tela="carregando">
            <p class="jogo-texto">Conectando à partida...</p>
        </section>

        <section class="jogo-tela" data-tela="aguardando" hidden>
            <span class="jogo-rotulo">Você entrou!</span>
            <h1 class="jogo-titulo"><?= htmlspecialchars($participant['nome']) ?></h1>
            <p class="jogo-texto">Aguarde o administrador iniciar o jogo. Deixe esta tela aberta.</p>
            <p class="jogo-texto"><strong data-campo="participantes">0</strong> na sala</p>
        </section>

        <section class="jogo-tela" data-tela="leitura" hidden>
            <span class="jogo-rotulo" data-campo="numero">Pergunta</span>
            <h1 class="jogo-titulo">Leia a pergunta na tela da apresentação</h1>
            <p class="jogo-texto">As alternativas liberam em</p>
            <div class="jogo-numero" data-campo="contagem">15</div>
            <div class="jogo-linha"><span></span></div>
        </section>

        <section class="jogo-tela jogo-tela-responder" data-tela="responder" hidden>
            <div class="jogo-responder-topo">
                <span data-campo="numero">Pergunta</span>
                <span><span data-campo="contagem">20</span> s</span>
            </div>
            <div class="jogo-linha"><span></span></div>

            <div class="botoes-resposta">
                <button type="button" class="botao-resposta letra-a" data-letra="A" aria-label="Responder A">A</button>
                <button type="button" class="botao-resposta letra-b" data-letra="B" aria-label="Responder B">B</button>
                <button type="button" class="botao-resposta letra-c" data-letra="C" aria-label="Responder C">C</button>
                <button type="button" class="botao-resposta letra-d" data-letra="D" aria-label="Responder D">D</button>
            </div>

            <p class="jogo-mensagem" data-campo="erro-envio" hidden></p>
        </section>

        <section class="jogo-tela" data-tela="respondida" hidden>
            <span class="jogo-rotulo">Resposta registrada</span>
            <div class="jogo-letra-escolhida" data-campo="letra">A</div>
            <p class="jogo-texto">Aguarde o resultado...</p>
        </section>

        <section class="jogo-tela" data-tela="esgotado" hidden>
            <span class="jogo-rotulo">Tempo esgotado</span>
            <h1 class="jogo-titulo">Você não respondeu esta pergunta</h1>
            <p class="jogo-texto">Aguarde o resultado na tela da apresentação.</p>
        </section>

        <section class="jogo-tela" data-tela="resultado" hidden>
            <span class="jogo-selo" data-campo="selo">Resultado</span>
            <h1 class="jogo-titulo" data-campo="resultado-titulo">Resultado</h1>
            <p class="jogo-texto" data-campo="resultado-texto"></p>
            <div class="jogo-placar">
                <div><strong data-campo="acertos">0</strong>acertos</div>
                <div><strong data-campo="pontos">0</strong>pontos</div>
            </div>
        </section>

        <section class="jogo-tela" data-tela="ranking" hidden>
            <span class="jogo-rotulo" data-campo="ranking-rotulo">Ranking parcial</span>
            <p class="jogo-texto">Sua posição</p>
            <div class="jogo-numero" data-campo="posicao">-</div>
            <p class="jogo-texto" data-campo="posicao-texto"></p>
            <div class="jogo-placar">
                <div><strong data-campo="acertos">0</strong>acertos</div>
                <div><strong data-campo="pontos">0</strong>pontos</div>
            </div>
        </section>

        <div class="jogo-conexao" data-campo="conexao" hidden>Sem conexão. Tentando de novo...</div>

    </main>

    <script src="/assets/js/jogar.js"></script>

</body>

</html>
