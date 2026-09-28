<?php

require_once __DIR__ . '/../../app/auth.php';

requireSuperAdmin();

$pdo = require __DIR__ . '/../../app/config/database.php';
$branding = require __DIR__ . '/../../app/config/branding.php';

$adminId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validateAdminCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Solicitação inválida.');
    }

    $adminId = filter_input(
        INPUT_POST,
        'admin_id',
        FILTER_VALIDATE_INT
    );
}

if (!$adminId) {
    header('Location: /admin/administradores.php');
    exit;
}

$statement = $pdo->prepare(
    'SELECT *
     FROM administradores
     WHERE id = :id
     LIMIT 1'
);

$statement->execute([
    'id' => $adminId,
]);

$admin = $statement->fetch();

if (!$admin) {
    header('Location: /admin/administradores.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');

    $email = strtolower(
        trim($_POST['email'] ?? '')
    );

    $newPassword = $_POST['new_password'] ?? '';

    $newProfile = $_POST['perfil'] ?? $admin['perfil'];

    if ((int) $adminId === (int) $_SESSION['admin_id']) {
        $newProfile = $admin['perfil'];
    }

    if (
        $name === ''
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || !in_array(
            $newProfile,
            ['admin', 'superadmin'],
            true
        )
    ) {
        $error = 'Dados inválidos.';
    }

    if (
        !$error
        && $newPassword !== ''
        && strlen($newPassword) < 10
    ) {
        $error = 'A nova senha deve possuir pelo menos 10 caracteres.';
    }

    $statement = $pdo->prepare(
        'SELECT id
         FROM administradores
         WHERE email = :email
           AND id <> :id
         LIMIT 1'
    );

    $statement->execute([
        'email' => $email,
        'id' => $adminId,
    ]);

    if (!$error && $statement->fetch()) {
        $error = 'Esse e-mail já está sendo utilizado.';
    }

    if (
        !$error
        && $admin['perfil'] === 'superadmin'
        && $newProfile === 'admin'
    ) {

        $count = $pdo
            ->query(
                "SELECT COUNT(*)
                 FROM administradores
                 WHERE perfil = 'superadmin'
                   AND ativo = 1"
            )
            ->fetchColumn();

        if ((int) $count <= 1) {
            $error = 'É necessário manter pelo menos um superadministrador ativo.';
        }
    }

    if (!$error) {

        if ($newPassword !== '') {

            $statement = $pdo->prepare(
                'UPDATE administradores
                 SET
                    nome = :nome,
                    email = :email,
                    perfil = :perfil,
                    senha_hash = :senha_hash
                 WHERE id = :id'
            );

            $statement->execute([
                'nome' => $name,
                'email' => $email,
                'perfil' => $newProfile,
                'senha_hash' => password_hash(
                    $newPassword,
                    PASSWORD_DEFAULT
                ),
                'id' => $adminId,
            ]);

        } else {

            $statement = $pdo->prepare(
                'UPDATE administradores
                 SET
                    nome = :nome,
                    email = :email,
                    perfil = :perfil
                 WHERE id = :id'
            );

            $statement->execute([
                'nome' => $name,
                'email' => $email,
                'perfil' => $newProfile,
                'id' => $adminId,
            ]);
        }

        if ((int) $adminId === (int) $_SESSION['admin_id']) {
            $_SESSION['admin_name'] = $name;
        }

        header(
            'Location: /admin/administradores.php?sucesso=atualizado'
        );

        exit;
    }
}

$csrfToken = adminCsrfToken();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Editar administrador</title>

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

        <div class="participant-card">

            <span class="join-label">
                ADMINISTRAÇÃO
            </span>

            <h1 class="participant-title">
                Editar administrador
            </h1>

            <?php if ($error): ?>

                <div class="form-error">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>

            <form
                method="post"
                class="participant-form"
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

                <div class="form-group">

                    <label>
                        Nome
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="<?= htmlspecialchars($admin['nome']) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>
                        E-mail
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="<?= htmlspecialchars($admin['email']) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>
                        Perfil
                    </label>

                    <select
                        name="perfil"
                        <?= (int) $admin['id'] === (int) $_SESSION['admin_id']
                            ? 'disabled'
                            : '' ?>
                    >

                        <option
                            value="admin"
                            <?= $admin['perfil'] === 'admin'
                                ? 'selected'
                                : '' ?>
                        >
                            Administrador
                        </option>

                        <option
                            value="superadmin"
                            <?= $admin['perfil'] === 'superadmin'
                                ? 'selected'
                                : '' ?>
                        >
                            Superadministrador
                        </option>

                    </select>

                </div>

                <div class="form-group">

                    <label>
                        Nova senha
                    </label>

                    <input
                        type="password"
                        name="new_password"
                        minlength="10"
                        placeholder="Deixe vazio para manter a atual"
                    >

                </div>

                <button
                    type="submit"
                    class="participant-button"
                >
                    Salvar alterações
                </button>

            </form>

            <a
                href="/admin/administradores.php"
                class="back-link"
            >
                Voltar
            </a>

        </div>

    </section>

</main>

</body>

</html>