<?php

// Chamado a cada segundo pelo painel e pela apresentação: vira a fase
// quando o tempo acaba, atualiza o arquivo de estado e devolve a tela.

require_once __DIR__ . '/../../../app/auth.php';
require_once __DIR__ . '/../../../app/jogo.php';

requireAdmin();
session_write_close();

$pdo = require __DIR__ . '/../../../app/config/database.php';

$codigo = jogoCodigoValido($_GET['code'] ?? null);

if (!$codigo) {
    jogoResponderJson(['ok' => false, 'motivo' => 'codigo'], 400);
}

$partida = jogoCarregarPartida($pdo, $codigo);

if (!$partida) {
    jogoResponderJson(['ok' => false, 'motivo' => 'partida'], 404);
}

// Duas voltas: se as telas ficaram fechadas, leitura e resposta podem ter vencido juntas.
for ($volta = 0; $volta < 2 && jogoAvancar($pdo, $partida); $volta++) {
    $partida = jogoCarregarPartida($pdo, $codigo);
}

$estado = jogoPublicarEstado($pdo, $codigo);

$completo = ($_GET['completo'] ?? '') === '1';
$status = $partida['status'];
$revelar = in_array($status, ['resultado', 'ranking', 'finalizado'], true);

$pergunta = jogoPergunta(
    $pdo,
    (int) $partida['quiz_id'],
    $partida['pergunta_atual_id'] ? (int) $partida['pergunta_atual_id'] : null
);

if ($pergunta) {
    $pergunta['alternativas'] = jogoAlternativasComContagem(
        $pdo,
        (int) $partida['id'],
        $pergunta['id'],
        $revelar
    );
}

$statement = $pdo->prepare('SELECT COUNT(*) FROM perguntas WHERE quiz_id = :quiz_id');
$statement->execute(['quiz_id' => $partida['quiz_id']]);
$totalPerguntas = (int) $statement->fetchColumn();

$ranking = null;

if ($completo && $status !== 'aguardando') {
    $ranking = jogoRanking($pdo, (int) $partida['id']);
} elseif (in_array($status, ['ranking', 'finalizado'], true)) {
    // A apresentação vai pro Teams: só os 10 primeiros, sem usuário.
    $ranking = array_map(
        fn ($linha) => [
            'posicao' => $linha['posicao'],
            'nome' => $linha['nome'],
            'acertos' => $linha['acertos'],
            'pontos' => $linha['pontos'],
        ],
        array_slice(jogoRanking($pdo, (int) $partida['id']), 0, 10)
    );
}

jogoResponderJson([
    'ok' => true,
    'agora_ms' => $partida['agora_ms'],
    'codigo' => $partida['codigo'],
    'status' => $status,
    'titulo' => $partida['titulo'],
    'subtitulo' => $partida['subtitulo'],
    'inicio_ms' => $partida['inicio_ms'],
    'fim_ms' => $partida['fim_ms'],
    'status_pausado' => $partida['status_pausado'],
    'pausada_ms' => $partida['pausada_ms'],
    'participantes' => $estado['participantes'],
    'respondidos' => $estado['respondidos'],
    'total_perguntas' => $totalPerguntas,
    'pergunta' => $pergunta,
    'ranking' => $ranking,
]);
