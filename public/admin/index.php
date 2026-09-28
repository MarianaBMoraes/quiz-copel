<?php

require_once __DIR__ . '/../../app/auth.php';

requireAdmin();

$pdo = require __DIR__ . '/../../app/config/database.php';
$branding = require __DIR__ . '/../../app/config/branding.php';

$statement = $pdo->query(
    'SELECT
        partidas.codigo,
        partidas.status,
        partidas.criado_em,
        quizzes.titulo,
        (
            SELECT COUNT(*)
            FROM participantes
            WHERE participantes.partida_id = partidas.id
        ) AS participantes
     FROM partidas
     INNER JOIN quizzes
        ON quizzes.id = partidas.quiz_id
     ORDER BY partidas.id DESC
     LIMIT 20'
);

$games = $statement->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Painel administrativo</title>

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
                    Olá, <?= htmlspecialchars($_SESSION['admin_name']) ?>
                </h1>

                <p>
                    Partidas recentes da plataforma.
                </p>

            </div>

            <div class="admin-top-actions">

    <?php if (isSuperAdmin()): ?>

        <a
            href="/admin/administradores.php"
            class="admin-nav-link"
        >
            Administradores
        </a>

    <?php endif; ?>

    <a
        href="/admin/logout.php"
        class="admin-nav-link"
    >
        Sair
    </a>

</div>

        </header>

        <section class="admin-panel">

            <div class="admin-panel-header">

                <div>

                    <h2>
                        Partidas
                    </h2>

                    <p>
                        Selecione uma partida para abrir o painel.
                    </p>

                </div>

            </div>

            <div class="table-wrapper">

                <table class="participants-table">

                    <thead>

                        <tr>
                            <th>Quiz</th>
                            <th>Código</th>
                            <th>Participantes</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($games as $game): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($game['titulo']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        substr($game['codigo'], 0, 3)
                                        . ' '
                                        . substr($game['codigo'], 3, 3)
                                    ) ?>
                                </td>

                                <td>
                                    <?= (int) $game['participantes'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($game['status']) ?>
                                </td>

                                <td>

                                    <a
                                        href="/admin/partida.php?code=<?= urlencode($game['codigo']) ?>"
                                    >
                                        Abrir
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</body>

</html>