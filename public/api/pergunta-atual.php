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
        partidas.pergunta_atual_id
     FROM partidas
     WHERE partidas.codigo = :codigo
     LIMIT 1'
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


$response = [
    'status' => $game['status'],
];


if (!$game['pergunta_atual_id']) {

    echo json_encode($response);

    exit;
}


$statement = $pdo->prepare(
    'SELECT
        id,
        enunciado,
        tempo_leitura,
        tempo_resposta,
        explicacao
     FROM perguntas
     WHERE id = :id
     LIMIT 1'
);


$statement->execute([
    'id' => $game['pergunta_atual_id'],
]);


$question = $statement->fetch();


if (!$question) {

    echo json_encode($response);

    exit;
}


$statement = $pdo->prepare(
    'SELECT
        letra,
        texto
     FROM alternativas
     WHERE pergunta_id = :pergunta_id
     ORDER BY letra ASC'
);


$statement->execute([
    'pergunta_id' => $question['id'],
]);


$alternatives = [];

foreach ($statement->fetchAll() as $alternative) {

    $alternatives[$alternative['letra']] =
        $alternative['texto'];

}


$response['pergunta'] = [
    'id' => (int) $question['id'],
    'enunciado' => $question['enunciado'],
    'tempo_leitura' => (int) $question['tempo_leitura'],
    'tempo_resposta' => (int) $question['tempo_resposta'],
    'explicacao' => $question['explicacao'],
    'alternativas' => $alternatives,
];


echo json_encode($response);