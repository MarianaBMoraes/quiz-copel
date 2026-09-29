<?php

// Só roda pelo terminal, nunca pelo navegador.
if (PHP_SAPI !== 'cli') {
    exit;
}

/*
| Cria um quiz de demonstração com 9 perguntas genéricas, pra testar o
| fluxo inteiro antes das perguntas definitivas. As perguntas reais
| são cadastradas pelo painel.
|
| Uso: php scripts/seed-demo.php
*/

$pdo = require __DIR__ . '/../app/config/database.php';

$titulo = 'Quiz de demonstração';
$subtitulo = 'Perguntas de exemplo para testar a plataforma';

$perguntas = [
    ['Qual destas cores aparece no logotipo da plataforma?', ['Verde', 'Laranja', 'Roxo', 'Marrom'], 'B', 'Pergunta de aquecimento para conferir se todo mundo está respondendo.'],
    ['Quantos segundos dura a fase de leitura de cada pergunta?', ['5', '10', '15', '30'], 'C', 'Os 15 segundos de leitura não contam para a pontuação.'],
    ['O que vale mais no ranking?', ['Responder rápido', 'Acertar mais perguntas', 'Entrar primeiro na sala', 'Usar o computador'], 'B', 'O ranking ordena primeiro por acertos. A velocidade só desempata.'],
    ['Quantas vezes é possível responder a mesma pergunta?', ['Uma', 'Duas', 'Três', 'Quantas quiser'], 'A', 'Vale a primeira resposta. O sistema bloqueia as seguintes.'],
    ['Onde aparece o texto completo da pergunta?', ['No celular', 'Na tela compartilhada', 'Por e-mail', 'Em lugar nenhum'], 'B', null],
    ['O que acontece quando todos respondem antes do fim do tempo?', ['Nada', 'A pergunta encerra sozinha', 'O jogo termina', 'O tempo dobra'], 'B', null],
    ['Qual informação NÃO é pedida para entrar na partida?', ['Nome completo', 'Usuário', 'Código da partida', 'Senha'], 'D', 'Nunca informe senha. A plataforma pede só nome e usuário.'],
    ['Quem aparece no ranking exibido na tela?', ['Todos os participantes', 'Os 10 primeiros', 'Só o primeiro', 'Ninguém'], 'B', null],
    ['Qual é a pontuação máxima por pergunta?', ['100', '500', '1.000', '10.000'], 'C', '500 pontos por acertar e até 500 pela velocidade.'],
];

$pdo->beginTransaction();

$statement = $pdo->prepare(
    'INSERT INTO quizzes (titulo, subtitulo, ativo) VALUES (:titulo, :subtitulo, 1)'
);

$statement->execute([
    'titulo' => $titulo,
    'subtitulo' => $subtitulo,
]);

$quizId = (int) $pdo->lastInsertId();

$inserirPergunta = $pdo->prepare(
    'INSERT INTO perguntas (quiz_id, ordem, enunciado, explicacao)
     VALUES (:quiz_id, :ordem, :enunciado, :explicacao)'
);

$inserirAlternativa = $pdo->prepare(
    'INSERT INTO alternativas (pergunta_id, letra, texto, correta)
     VALUES (:pergunta_id, :letra, :texto, :correta)'
);

foreach ($perguntas as $indice => [$enunciado, $alternativas, $correta, $explicacao]) {
    $inserirPergunta->execute([
        'quiz_id' => $quizId,
        'ordem' => $indice + 1,
        'enunciado' => $enunciado,
        'explicacao' => $explicacao,
    ]);

    $perguntaId = (int) $pdo->lastInsertId();

    foreach (['A', 'B', 'C', 'D'] as $posicao => $letra) {
        $inserirAlternativa->execute([
            'pergunta_id' => $perguntaId,
            'letra' => $letra,
            'texto' => $alternativas[$posicao],
            'correta' => $letra === $correta ? 1 : 0,
        ]);
    }
}

$pdo->commit();

echo PHP_EOL . 'Quiz de demonstração criado. ID: ' . $quizId . PHP_EOL . PHP_EOL;
