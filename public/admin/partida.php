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

        <section class="admin-summary">

        <div class="summary-card">

    <span>
        Perguntas
    </span>

    <strong>
        <?= $totalQuestions ?>
    </strong>

</div>

            <div class="summary-card">

                <span>
                    Código
                </span>

                <strong>
                    <?= htmlspecialchars($formattedCode) ?>
                </strong>

            </div>

            <div class="summary-card">

                <span>
                    Participantes
                </span>

                <strong>
                    <?= $totalParticipants ?>
                </strong>

            </div>

            <div class="summary-card">

                <span>
                    Status
                </span>

                <strong class="status-text">
                    <?= htmlspecialchars($game['status']) ?>
                </strong>

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

                    <?php if ($game['status'] === 'aguardando'): ?>

    <?php if ($totalQuestions > 0): ?>

        <form
            action="/admin/iniciar-partida.php"
            method="post"
            onsubmit="return confirm('Deseja iniciar o quiz agora? Depois disso, novos participantes não poderão entrar normalmente.');"
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
                class="admin-button-primary"
            >
                Iniciar quiz
            </button>

        </form>

    <?php else: ?>

        <button
            type="button"
            class="admin-button-primary"
            disabled
            title="Cadastre pelo menos uma pergunta antes de iniciar."
        >
            Iniciar quiz
        </button>

    <?php endif; ?>

<?php endif; ?>

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

</body>

</html>

