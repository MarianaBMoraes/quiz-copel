<?php

$pdo = require __DIR__ . '/../../app/config/database.php';


$gameCode = preg_replace(
    '/\D/',
    '',
    $_GET['code'] ?? ''
);


if (strlen($gameCode) !== 6) {

    http_response_code(400);

    echo json_encode([
        'erro' => 'codigo_invalido'
    ]);

    exit;
}


$statement = $pdo->prepare(
    'SELECT
        partidas.status,
        COUNT(participantes.id) AS participantes
     FROM partidas
     LEFT JOIN participantes
        ON participantes.partida_id = partidas.id
     WHERE partidas.codigo = :codigo
     GROUP BY partidas.id,
              partidas.status'
);


$statement->execute([
    'codigo' => $gameCode,
]);


$game = $statement->fetch();


if (!$game) {

    http_response_code(404);

    echo json_encode([
        'erro' => 'partida_nao_encontrada'
    ]);

    exit;
}


header('Content-Type: application/json');


echo json_encode([
    'status' => $game['status'],
    'participantes' => (int) $game['participantes']
]);