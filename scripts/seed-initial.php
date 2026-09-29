<?php

// Só roda pelo terminal, nunca pelo navegador.
if (PHP_SAPI !== 'cli') {
    exit;
}

$pdo = require __DIR__ . '/../app/config/database.php';

$title = 'Transgressões';
$subtitle = 'Causas, impactos e aprendizados';

$statement = $pdo->prepare(
    'SELECT id
     FROM quizzes
     WHERE titulo = :titulo
     LIMIT 1'
);

$statement->execute([
    'titulo' => $title,
]);

$quiz = $statement->fetch();

if ($quiz) {
    echo PHP_EOL;
    echo "O quiz já existe." . PHP_EOL;
    echo "ID: " . $quiz['id'] . PHP_EOL;
    echo PHP_EOL;
    exit;
}

$statement = $pdo->prepare(
    'INSERT INTO quizzes (
        titulo,
        subtitulo,
        ativo
    ) VALUES (
        :titulo,
        :subtitulo,
        1
    )'
);

$statement->execute([
    'titulo' => $title,
    'subtitulo' => $subtitle,
]);

echo PHP_EOL;
echo "Quiz criado com sucesso!" . PHP_EOL;
echo "ID: " . $pdo->lastInsertId() . PHP_EOL;
echo PHP_EOL;