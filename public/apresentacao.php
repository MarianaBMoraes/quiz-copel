<?php

/*
| Tela compartilhada no Teams. Não tem nenhum controle: quem conduz
| é o painel da partida. Exige login de administrador porque mostra
| a pergunta antes de todo mundo.
*/

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/jogo.php';

requireAdmin();

$branding = require __DIR__ . '/../app/config/branding.php';
$pdo = require __DIR__ . '/../app/config/database.php';

$gameCode = jogoCodigoValido($_GET['code'] ?? null);
$game = $gameCode ? jogoCarregarPartida($pdo, $gameCode) : null;

if (!$game) {
    http_response_code(404);
    exit('Partida não encontrada.');
}

$formattedCode = substr($gameCode, 0, 3) . ' ' . substr($gameCode, 3, 3);

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($game['titulo']) ?> | Apresentação</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,400..800&display=swap">
    <link rel="stylesheet" href="/assets/css/jogo.css">

    <?php require __DIR__ . '/../app/views/cores-marca.php'; ?>
</head>

<body class="apresentacao">

    <main
        class="palco"
        data-apresentacao
        data-code="<?= htmlspecialchars($gameCode) ?>"
    >

        <div class="palco-aviso" data-campo="aviso" hidden></div>

        <header class="palco-topo">
            <div class="palco-marca">
                <img
                    src="<?= htmlspecialchars($branding['logo']) ?>"
                    alt="<?= htmlspecialchars($branding['logo_alt']) ?>"
                >
                <span class="palco-rotulo"><?= htmlspecialchars($branding['brand_name']) ?></span>
            </div>

            <div class="palco-codigo" data-campo="codigo-topo" hidden>
                Código <strong><?= htmlspecialchars($formattedCode) ?></strong>
            </div>
        </header>

        <section class="palco-corpo">

            <div class="cena cena-espera" data-cena="aguardando" hidden>
                <div>
                    <h1 class="espera-titulo"><?= htmlspecialchars($game['titulo']) ?></h1>

                    <?php if (!empty($game['subtitulo'])): ?>
                        <p class="espera-subtitulo"><?= htmlspecialchars($game['subtitulo']) ?></p>
                    <?php endif; ?>

                    <ol class="espera-passos">
                        <li><strong>Aponte a câmera do celular</strong> para o QR Code</li>
                        <li data-campo="passo-endereco">ou acesse <span class="espera-endereco" data-campo="endereco"></span> e digite o código</li>
                    </ol>
                </div>

                <div class="espera-qr">
                    <div class="espera-qr-codigo" data-campo="qr"></div>

                    <div class="espera-codigo">
                        <span>Código da partida</span>
                        <strong><?= htmlspecialchars($formattedCode) ?></strong>
                    </div>
                </div>
            </div>

            <div class="cena cena-pergunta" data-cena="pergunta" hidden>
                <div class="pergunta-cabeca">
                    <span class="pergunta-numero" data-campo="numero"></span>
                    <span class="pergunta-respondidos" data-campo="respondidos-bloco">
                        <strong data-campo="respondidos">0</strong> de <span data-campo="participantes">0</span> responderam
                    </span>
                </div>

                <h1 class="pergunta-enunciado" data-campo="enunciado"></h1>

                <p class="pergunta-aviso" data-campo="aviso-leitura"></p>

                <ol class="alternativas" data-campo="alternativas"></ol>

                <div class="aprendizado" data-campo="aprendizado" hidden>
                    <span>Aprendizado</span>
                    <p data-campo="explicacao"></p>
                </div>
            </div>

            <div class="cena cena-ranking" data-cena="ranking" hidden>
                <h1 class="ranking-titulo" data-campo="ranking-titulo"></h1>
                <ol class="ranking-lista" data-campo="ranking-lista"></ol>
            </div>

        </section>

        <footer class="palco-rodape">
            <div data-campo="rodape-esquerda">
                <div class="rodape-contador" data-campo="contador" hidden>
                    <strong data-campo="contador-numero">0</strong>
                    <span data-campo="contador-texto">participantes na sala</span>
                </div>

                <div class="linha-energia" data-campo="linha" data-modo="parado" hidden>
                    <div class="linha-energia-carga"></div>
                    <div class="linha-energia-postes">
                        <span></span><span></span><span></span><span></span><span></span><span></span>
                        <span></span><span></span><span></span><span></span><span></span>
                    </div>
                </div>
            </div>

            <span class="palco-pausa" data-campo="pausa" hidden>Tempo pausado</span>

            <div class="palco-relogio" data-campo="relogio"></div>
        </footer>

    </main>

    <script src="/assets/js/vendor/qrcode.js"></script>
    <script src="/assets/js/apresentacao.js"></script>

</body>

</html>
