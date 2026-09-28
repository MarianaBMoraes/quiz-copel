<?php

require_once __DIR__ . '/../../app/auth.php';

requireSuperAdmin();

$pdo = require __DIR__ . '/../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/administradores.php');
    exit;
}

if (!validateAdminCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Solicitação inválida.');
}

$name = trim($_POST['name'] ?? '');
$email = strtolower(
    trim($_POST['email'] ?? '')
);

$password = $_POST['password'] ?? '';
$profile = $_POST['perfil'] ?? '';

$allowedProfiles = [
    'admin',
    'superadmin',
];

if (
    $name === ''
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || strlen($password) < 10
    || !in_array($profile, $allowedProfiles, true)
) {
    header(
        'Location: /admin/administradores.php?erro=dados'
    );

    exit;
}

$statement = $pdo->prepare(
    'SELECT id
     FROM administradores
     WHERE email = :email
     LIMIT 1'
);

$statement->execute([
    'email' => $email,
]);

if ($statement->fetch()) {

    header(
        'Location: /admin/administradores.php?erro=email'
    );

    exit;
}

$statement = $pdo->prepare(
    'INSERT INTO administradores (
        nome,
        email,
        senha_hash,
        perfil,
        ativo
    ) VALUES (
        :nome,
        :email,
        :senha_hash,
        :perfil,
        1
    )'
);

$statement->execute([
    'nome' => $name,
    'email' => $email,
    'senha_hash' => password_hash(
        $password,
        PASSWORD_DEFAULT
    ),
    'perfil' => $profile,
]);

header(
    'Location: /admin/administradores.php?sucesso=criado'
);

exit;