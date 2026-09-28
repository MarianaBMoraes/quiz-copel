<?php

require_once __DIR__ . '/../../app/auth.php';

startAdminSession();

$pdo = require __DIR__ . '/../../app/config/database.php';
$branding = require __DIR__ . '/../../app/config/branding.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: /admin/');
    exit;
}

$error = null;

$returnTo = $_GET['return_to']
    ?? $_POST['return_to']
    ?? '/admin/';

if (
    !str_starts_with($returnTo, '/admin/')
    || str_starts_with($returnTo, '//')
) {
    $returnTo = '/admin/';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $statement = $pdo->prepare(
        'SELECT
            id,
            nome,
            email,
            senha_hash,
            perfil,
            ativo
         FROM administradores
         WHERE email = :email
         LIMIT 1'
    );

    $statement->execute([
        'email' => $email,
    ]);

    $admin = $statement->fetch();

    if (
        !$admin ||
        !$admin['ativo'] ||
        !password_verify($password, $admin['senha_hash'])
    ) {
        $error = 'E-mail ou senha inválidos.';
    } else {

        session_regenerate_id(true);

        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['nome'];
        $_SESSION['admin_role'] = $admin['perfil'];

        header('Location: ' . $returnTo);
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Área administrativa</title>

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
                    ADMINISTRAÇÃO
                </span>

                <h1 class="participant-title">
                    Acessar painel
                </h1>

                <p class="participant-description">
                    Entre com suas credenciais administrativas.
                </p>

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
                        name="return_to"
                        value="<?= htmlspecialchars($returnTo) ?>"
                    >

                    <div class="form-group">

                        <label for="email">
                            E-mail
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            autocomplete="username"
                        >

                    </div>

                    <div class="form-group">

                        <label for="password">
                            Senha
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                        >

                    </div>

                    <button
                        type="submit"
                        class="participant-button"
                    >
                        Entrar
                    </button>

                </form>

            </div>

        </section>

    </main>

</body>

</html>