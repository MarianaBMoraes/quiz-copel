<?php

$pdo = require __DIR__ . '/../app/config/database.php';

$statement = $pdo->query(
    'SELECT id, titulo
     FROM quizzes
     WHERE ativo = 1
     ORDER BY id ASC
     LIMIT 1'
);

$quiz = $statement->fetch();

if (!$quiz) {
    exit("Nenhum quiz ativo encontrado." . PHP_EOL);
}

do {
    $code = (string) random_int(100000, 999999);

    $statement = $pdo->prepare(
        'SELECT id
         FROM partidas
         WHERE codigo = :codigo
         LIMIT 1'
    );

    $statement->execute([
        'codigo' => $code,
    ]);

    $exists = $statement->fetch();
} while ($exists);

$statement = $pdo->prepare(
    'INSERT INTO partidas (
        quiz_id,
        codigo,
        status
    ) VALUES (
        :quiz_id,
        :codigo,
        :status
    )'
);

$statement->execute([
    'quiz_id' => $quiz['id'],
    'codigo' => $code,
    'status' => 'aguardando',
]);

$formattedCode =
    substr($code, 0, 3)
    . ' '
    . substr($code, 3, 3);

echo PHP_EOL;
echo "Partida criada com sucesso!" . PHP_EOL;
echo "Quiz: " . $quiz['titulo'] . PHP_EOL;
echo "Código: " . $formattedCode . PHP_EOL;
echo "Status: aguardando" . PHP_EOL;
echo PHP_EOL;