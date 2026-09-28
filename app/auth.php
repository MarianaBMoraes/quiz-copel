<?php

function startAdminSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']),
        'samesite' => 'Lax',
        'path' => '/',
    ]);

    session_start();
}

function requireAdmin(): void
{
    startAdminSession();

    if (empty($_SESSION['admin_id'])) {

        $returnTo = $_SERVER['REQUEST_URI'] ?? '/admin/';

        header(
            'Location: /admin/login.php?return_to='
            . urlencode($returnTo)
        );

        exit;
    }

    $pdo = require __DIR__ . '/config/database.php';

    $statement = $pdo->prepare(
        'SELECT
            id,
            nome,
            perfil,
            ativo
         FROM administradores
         WHERE id = :id
         LIMIT 1'
    );

    $statement->execute([
        'id' => $_SESSION['admin_id'],
    ]);

    $admin = $statement->fetch();

    if (!$admin || !(int) $admin['ativo']) {

        $_SESSION = [];

        session_destroy();

        header(
            'Location: /admin/login.php?erro=acesso'
        );

        exit;
    }

    $_SESSION['admin_name'] = $admin['nome'];
    $_SESSION['admin_role'] = $admin['perfil'];
}

function isSuperAdmin(): bool
{
    startAdminSession();

    return ($_SESSION['admin_role'] ?? '') === 'superadmin';
}

function requireSuperAdmin(): void
{
    requireAdmin();

    if (!isSuperAdmin()) {
        http_response_code(403);

        exit(
            'Você não possui permissão para acessar esta área.'
        );
    }
}

function adminCsrfToken(): string
{
    startAdminSession();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION['csrf_token'];
}

function validateAdminCsrf(?string $token): bool
{
    startAdminSession();

    if (
        !is_string($token)
        || empty($_SESSION['csrf_token'])
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION['csrf_token'],
        $token
    );
}