<?php

require_once __DIR__ . '/../../app/auth.php';

requireSuperAdmin();

$pdo = require __DIR__ . '/../../app/config/database.php';
$branding = require __DIR__ . '/../../app/config/branding.php';

$csrfToken = adminCsrfToken();

$success = $_GET['sucesso'] ?? null;
$error = $_GET['erro'] ?? null;

$statement = $pdo->query(
    'SELECT
        id,
        nome,
        email,
        perfil,
        ativo,
        criado_em
     FROM administradores
     ORDER BY nome ASC'
);

$admins = $statement->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Administradores</title>

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
                ADMINISTRAÇÃO
            </span>

            <h1>
                Administradores
            </h1>

            <p>
                Gerencie quem pode acessar o painel.
            </p>

        </div>

        <div class="admin-top-actions">

            <a
                href="/admin/"
                class="admin-nav-link"
            >
                Voltar ao painel
            </a>

            <a
                href="/admin/logout.php"
                class="admin-nav-link"
            >
                Sair
            </a>

        </div>

    </header>

    <?php if ($success === 'criado'): ?>

        <div class="admin-alert-success">
            Administrador criado com sucesso.
        </div>

    <?php endif; ?>

    <?php if ($success === 'atualizado'): ?>

        <div class="admin-alert-success">
            Administrador atualizado com sucesso.
        </div>

    <?php endif; ?>

    <?php if ($success === 'status'): ?>

        <div class="admin-alert-success">
            Status do administrador atualizado.
        </div>

    <?php endif; ?>

    <?php if ($success === 'excluido'): ?>

    <div class="admin-alert-success">
        Administrador excluído com sucesso.
    </div>

<?php endif; ?>

    <?php if ($error): ?>

    <div class="admin-alert-error">

        <?php if ($error === 'propria_conta'): ?>

            Você não pode excluir ou desativar sua própria conta.

        <?php elseif ($error === 'ultimo_superadmin'): ?>

            É necessário manter pelo menos um Superadministrador ativo.

        <?php elseif ($error === 'nao_encontrado'): ?>

            Administrador não encontrado.

        <?php elseif ($error === 'email'): ?>

            Já existe uma conta utilizando esse e-mail.

        <?php else: ?>

            Não foi possível concluir a operação.

        <?php endif; ?>

    </div>

<?php endif; ?>

    <section class="admin-panel">

        <div class="admin-panel-header">

            <div>

                <h2>
                    Novo administrador
                </h2>

                <p>
                    Crie uma conta individual para outro responsável.
                </p>

            </div>

        </div>

        <form
            action="/admin/criar-administrador.php"
            method="post"
            class="admin-create-form"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrfToken) ?>"
            >

            <div class="admin-form-field">

                <label for="name">
                    Nome
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    required
                >

            </div>

            <div class="admin-form-field">

                <label for="email">
                    E-mail
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                >

            </div>

            <div class="admin-form-field">

                <label for="password">
                    Senha inicial
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="10"
                    required
                >

            </div>

            <div class="admin-form-field">

                <label for="perfil">
                    Perfil
                </label>

                <select
                    id="perfil"
                    name="perfil"
                    required
                >

                    <option value="admin">
                        Administrador
                    </option>

                    <option value="superadmin">
                        Superadministrador
                    </option>

                </select>

            </div>

            <button
                type="submit"
                class="admin-button-primary"
            >
                Criar administrador
            </button>

        </form>

    </section>

    <section class="admin-panel admin-users-panel">

        <div class="admin-panel-header">

            <div>

                <h2>
                    Contas administrativas
                </h2>

                <p>
                    <?= count($admins) ?> conta<?= count($admins) === 1 ? '' : 's' ?>
                </p>

            </div>

        </div>

        <div class="table-wrapper">

            <table class="participants-table">

                <thead>

                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($admins as $admin): ?>

                        <tr>

                            <td>

                                <?= htmlspecialchars($admin['nome']) ?>

                                <?php if ((int) $admin['id'] === (int) $_SESSION['admin_id']): ?>

                                    <span class="you-badge">
                                        Você
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= htmlspecialchars($admin['email']) ?>
                            </td>

                            <td>

                                <span class="admin-role-badge">
                                    <?= $admin['perfil'] === 'superadmin'
                                        ? 'Superadmin'
                                        : 'Admin' ?>
                                </span>

                            </td>

                            <td>

                                <?php if ($admin['ativo']): ?>

                                    <span class="participant-status">
                                        Ativo
                                    </span>

                                <?php else: ?>

                                    <span class="admin-inactive-badge">
                                        Inativo
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <div class="admin-row-actions">

                                    <a
                                        href="/admin/editar-administrador.php?id=<?= (int) $admin['id'] ?>"
                                        class="admin-edit-link"
                                    >
                                        Editar
                                    </a>

                                    <form
    action="/admin/excluir-administrador.php"
    method="post"
    onsubmit="return confirm('Tem certeza que deseja excluir permanentemente este administrador?');"
>

    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars($csrfToken) ?>"
    >

    <input
        type="hidden"
        name="admin_id"
        value="<?= (int) $admin['id'] ?>"
    >

    <button
        type="submit"
        class="admin-delete-button"
    >
        Excluir
    </button>

</form>

                                    <?php if ((int) $admin['id'] !== (int) $_SESSION['admin_id']): ?>

                                        <form
                                            action="/admin/alterar-status-administrador.php"
                                            method="post"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= htmlspecialchars($csrfToken) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="admin_id"
                                                value="<?= (int) $admin['id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="ativo"
                                                value="<?= $admin['ativo'] ? '0' : '1' ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="admin-status-button"
                                            >
                                                <?= $admin['ativo']
                                                    ? 'Desativar'
                                                    : 'Ativar' ?>
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </div>

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