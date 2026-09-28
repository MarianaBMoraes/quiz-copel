<?php

require_once __DIR__ . '/../../app/auth.php';

requireAdmin();

$pdo = require __DIR__ . '/../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        'Location: /admin/novo-quiz.php'
    );

    exit;
}

if (!validateAdminCsrf($_POST['csrf_token'] ?? null)) {

    http_response_code(403);

    exit(
        'Solicitação inválida.'
    );
}

$titulo = trim(
    $_POST['titulo'] ?? ''
);

$subtitulo = trim(
    $_POST['subtitulo'] ?? ''
);

$descricao = trim(
    $_POST['descricao'] ?? ''
);

if (
    $titulo === ''
    || strlen($titulo) > 180
    || strlen($subtitulo) > 255
) {

    header(
        'Location: /admin/novo-quiz.php?erro=dados'
    );

    exit;
}

$statement = $pdo->prepare(
    'INSERT INTO quizzes (
        titulo,
        subtitulo,
        descricao,
        ativo
    ) VALUES (
        :titulo,
        :subtitulo,
        :descricao,
        1
    )'
);

$statement->execute([
    'titulo' => $titulo,

    'subtitulo' => $subtitulo !== ''
        ? $subtitulo
        : null,

    'descricao' => $descricao !== ''
        ? $descricao
        : null,
]);

header(
    'Location: /admin/quizzes.php?sucesso=criado'
);

exit;