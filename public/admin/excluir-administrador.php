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

$adminId = filter_input(
    INPUT_POST,
    'admin_id',
    FILTER_VALIDATE_INT
);

if (!$adminId) {
    header(
        'Location: /admin/administradores.php?erro=id'
    );

    exit;
}

if ((int) $adminId === (int) $_SESSION['admin_id']) {

    header(
        'Location: /admin/administradores.php?erro=propria_conta'
    );

    exit;
}

$statement = $pdo->prepare(
    'SELECT
        id,
        perfil,
        ativo
     FROM administradores
     WHERE id = :id
     LIMIT 1'
);

$statement->execute([
    'id' => $adminId,
]);

$targetAdmin = $statement->fetch();

if (!$targetAdmin) {

    header(
        'Location: /admin/administradores.php?erro=nao_encontrado'
    );

    exit;
}

if (
    $targetAdmin['perfil'] === 'superadmin'
    && (int) $targetAdmin['ativo'] === 1
) {

    $activeSuperAdmins = $pdo
        ->query(
            "SELECT COUNT(*)
             FROM administradores
             WHERE perfil = 'superadmin'
               AND ativo = 1"
        )
        ->fetchColumn();

    if ((int) $activeSuperAdmins <= 1) {

        header(
            'Location: /admin/administradores.php?erro=ultimo_superadmin'
        );

        exit;
    }
}

$statement = $pdo->prepare(
    'DELETE FROM administradores
     WHERE id = :id'
);

$statement->execute([
    'id' => $adminId,
]);

header(
    'Location: /admin/administradores.php?sucesso=excluido'
);

exit;