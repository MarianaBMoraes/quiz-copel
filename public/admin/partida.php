<?php

require_once __DIR__ . '/../../app/auth.php';

requireAdmin();

$csrfToken = adminCsrfToken();

$branding = require __DIR__ . '/../../app/config/branding.php';
$pdo = require __DIR__ . '/../../app/config/database.php';

$gameCode = preg_replace('/\D/', '', $_GET['code'] ?? '');
$success = $_GET['sucesso'] ?? null;
$error = $_GET['erro'] ?? null;

if (strlen($gameCode) !== 6) {
    header('Location: /');
    exit;
}

$statement = $pdo->prepare(
    'SELECT
        partidas.id,
        partidas.quiz_id,
        partidas.codigo,
        partidas.status,
        partidas.criado_em,
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
    http_response_code(404);
    exit('Partida não encontrada.');
}

$statement->execute([
    'codigo' => $gameCode,
]);

$game = $statement->fetch();

if (!$game) {
    http_response_code(404);
    exit('Partida não encontrada.');
}

/*
|--------------------------------------------------------------------------
| Quantidade de perguntas do quiz
|--------------------------------------------------------------------------
*/

$statement = $pdo->prepare(
    'SELECT COUNT(*)
     FROM perguntas
     WHERE quiz_id = :quiz_id'
);

$statement->execute([
    'quiz_id' => $game['quiz_id'],
]);

$totalQuestions = (int) $statement->fetchColumn();

/*
|--------------------------------------------------------------------------
| Participantes da partida
|--------------------------------------------------------------------------
*/

$statement = $pdo->prepare(
    'SELECT
        id,
        nome,
        usuario_copel,
        status,
        entrou_em,
        ultima_atividade_em
     FROM participantes
     WHERE partida_id = :partida_id
     ORDER BY entrou_em ASC'
);

$statement->execute([
    'partida_id' => $game['id'],
]);

$participants = $statement->fetchAll();

$statement = $pdo->prepare(
    'SELECT
        id,
        nome,
        usuario_copel,
        status,
        entrou_em,
        ultima_atividade_em
     FROM participantes
     WHERE partida_id = :partida_id
     ORDER BY entrou_em ASC'
);

$statement->execute([
    'partida_id' => $game['id'],
]);

$participants = $statement->fetchAll();

$totalParticipants = count($participants);

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
        Partida <?= htmlspecialchars($formattedCode) ?>
    </title>

    <link
        rel="stylesheet"
        href="/assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="/assets/css/jogo.css"
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

            <a
    href="/admin/quizzes.php"
    class="admin-back-link"
>
    ← Voltar para quizzes
</a>
                
                <span class="admin-eyebrow">
                    PAINEL ADMINISTRATIVO
                </span>

                <h1>
                    <?= htmlspecialchars($game['titulo']) ?>
                </h1>

                <p>
                    <?= htmlspecialchars($game['subtitulo'] ?? '') ?>
                </p>

            </div>

            <img
                src="/assets/images/branding/logo-copel.png"
                alt="Copel"
                class="admin-logo"
            >

        </header>

        <?php if ($success === 'participante_excluido'): ?>

    <div class="admin-alert-success">
        Participante excluído da partida com sucesso.
    </div>

<?php endif; ?>

<?php if ($success === 'partida_iniciada'): ?>

    <div class="admin-alert-success">
        Quiz iniciado com sucesso.
    </div>

<?php endif; ?>

<?php if ($error === 'sem_perguntas'): ?>

    <div class="admin-alert-error">
        Cadastre pelo menos uma pergunta antes de iniciar o quiz.
    </div>

<?php endif; ?>

<?php if ($error === 'status'): ?>

    <div class="admin-alert-error">
        A partida não está mais disponível para ser iniciada.
    </div>

<?php endif; ?>

<?php if ($error === 'partida_iniciada'): ?>

    <div class="admin-alert-error">
        Não é possível excluir participantes depois que o quiz foi iniciado.
    </div>

<?php endif; ?>

        <section
            class="admin-panel ao-vivo"
            data-ao-vivo
            data-code="<?= htmlspecialchars($gameCode) ?>"
            data-csrf="<?= htmlspecialchars($csrfToken) ?>"
            data-total-perguntas="<?= $totalQuestions ?>"
        >

            <div class="admin-panel-header">

                <div>
                    <h2>
                        Controle ao vivo
                    </h2>

                    <p class="ao-vivo-fase">
                        Partida <strong><?= htmlspecialchars($formattedCode) ?></strong>
                        · <span data-campo="fase">carregando...</span>
                    </p>
                </div>

                <div class="admin-actions">

                    <button type="button" class="admin-button-secondary" data-copiar-link>
                        Copiar link de entrada
                    </button>

                    <a
                        class="ao-vivo-link"
                        href="/apresentacao.php?code=<?= urlencode($gameCode) ?>"
                        target="_blank"
                        rel="noopener"
                    >
                        Abrir tela de apresentação ↗
                    </a>

                </div>

            </div>

            <div class="admin-summary">

                <div class="summary-card">
                    <span>Pergunta</span>
                    <strong data-campo="pergunta">-</strong>
                </div>

                <div class="summary-card">
                    <span>Participantes</span>
                    <strong data-campo="participantes"><?= $totalParticipants ?></strong>
                </div>

                <div class="summary-card">
                    <span>Responderam</span>
                    <strong data-campo="respondidos">-</strong>
                </div>

                <div class="summary-card">
                    <span>Tempo</span>
                    <strong data-campo="tempo">-</strong>
                </div>

            </div>

            <div class="ao-vivo-controles">

                <button type="button" class="admin-button-primary" data-acao="iniciar" hidden>
                    Iniciar quiz
                </button>

                <button type="button" class="admin-button-primary" data-acao="mostrar_ranking" hidden>
                    Mostrar ranking
                </button>

                <button type="button" class="admin-button-primary" data-acao="proxima" hidden>
                    Próxima pergunta
                </button>

                <button type="button" class="admin-button-secondary" data-acao="encerrar_questao" hidden>
                    Encerrar questão
                </button>

                <span class="espaco"></span>

                <button type="button" class="admin-button-secondary admin-button-danger" data-acao="finalizar" hidden>
                    Encerrar jogo
                </button>

            </div>

            <div class="admin-alert-error ao-vivo-erro" data-campo="erro" hidden></div>

            <div class="ao-vivo-ranking" data-campo="ranking-bloco" hidden>

                <h3>
                    Classificação completa
                </h3>

                <div class="ao-vivo-exportar">
                    <a class="ao-vivo-link" href="/admin/exportar.php?code=<?= urlencode($gameCode) ?>&amp;tipo=geral">Baixar relatório geral</a>
                    <a class="ao-vivo-link" href="/admin/exportar.php?code=<?= urlencode($gameCode) ?>&amp;tipo=perguntas">Baixar análise por pergunta</a>
                    <a class="ao-vivo-link" href="/admin/exportar.php?code=<?= urlencode($gameCode) ?>&amp;tipo=respostas">Baixar respostas de cada participante</a>
                </div>

                <div class="table-wrapper">
                    <table class="participants-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nome</th>
                                <th>Usuário Copel</th>
                                <th>Acertos</th>
                                <th>Respondidas</th>
                                <th>Pontos</th>
                            </tr>
                        </thead>
                        <tbody data-campo="ranking"></tbody>
                    </table>
                </div>

            </div>

        </section>

        <section class="admin-panel">

            <div class="admin-panel-header">

                <div>

                    <h2>
                        Participantes
                    </h2>

                    <p>
                        Pessoas conectadas à partida.
                    </p>

                </div>

                <div class="admin-actions">

                    <button
                        type="button"
                        class="admin-button-secondary"
                        onclick="window.location.reload()"
                    >
                        Atualizar lista
                    </button>

                    <form
    action="/admin/excluir-partida.php"
    method="post"
    onsubmit="return confirm('Tem certeza que deseja excluir esta partida? Esta ação removerá participantes e respostas.');"
>

    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars($csrfToken) ?>"
    >

    <input
        type="hidden"
        name="game_code"
        value="<?= htmlspecialchars($gameCode) ?>"
    >

    <button
        type="submit"
        class="admin-button-secondary admin-button-danger"
    >
        Excluir partida
    </button>

</form>


                </div>

            </div>

            <?php if ($participants): ?>

                <div class="table-wrapper">

                    <table class="participants-table">

                        <thead>

                            <tr>
                                <th>#</th>
                                <th>Nome</th>
                                <th>Usuário Copel</th>
                                <th>Status</th>
                                <th>Entrada</th>
                                <th>Ações</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($participants as $index => $participant): ?>

                                <tr>

                                    <td>
                                        <?= $index + 1 ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($participant['nome']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($participant['usuario_copel']) ?>
                                    </td>

                                    <td>

                                        <span class="participant-status">
                                            <?= htmlspecialchars($participant['status']) ?>
                                        </span>

                                    </td>

                                    <td>
                                        <?= htmlspecialchars($participant['entrou_em']) ?>

                                        <td>

    <?php if ($game['status'] === 'aguardando'): ?>

        <form
            action="/admin/excluir-participante.php"
            method="post"
            class="delete-participant-form"
            onsubmit="return confirm('Tem certeza que deseja excluir este participante da partida?');"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrfToken) ?>"
            >

            <input
                type="hidden"
                name="participant_id"
                value="<?= (int) $participant['id'] ?>"
            >

            <input
                type="hidden"
                name="game_code"
                value="<?= htmlspecialchars($gameCode) ?>"
            >

            <button
                type="submit"
                class="delete-participant-button"
            >
                Excluir
            </button>

        </form>

    <?php else: ?>

        <span class="action-unavailable">
            Indisponível
        </span>

    <?php endif; ?>

</td>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty-state">

                    <strong>
                        Nenhum participante ainda.
                    </strong>

                    <p>
                        A lista será preenchida conforme as pessoas entrarem.
                    </p>

                </div>

            <?php endif; ?>

        </section>

        <div class="admin-warning">
            Ambiente de desenvolvimento. 
        </div>

    </main>

    <script src="/assets/js/admin-partida.js"></script>

</body>

</html>

