<?php

/*
| Relatórios da partida em CSV (abre direto no Excel).
| tipo=geral      uma linha por participante, na ordem do ranking
| tipo=perguntas  uma linha por pergunta, com a distribuição A/B/C/D
| tipo=respostas  cada resposta de cada participante (relatório individual)
*/

require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/jogo.php';

requireAdmin();
session_write_close();

$pdo = require __DIR__ . '/../../app/config/database.php';

date_default_timezone_set('America/Sao_Paulo');

$codigo = jogoCodigoValido($_GET['code'] ?? null);
$tipo = $_GET['tipo'] ?? 'geral';
$partida = $codigo ? jogoCarregarPartida($pdo, $codigo) : null;

if (!$partida || !in_array($tipo, ['geral', 'perguntas', 'respostas'], true)) {
    http_response_code(404);
    exit('Relatório não encontrado.');
}

$partidaId = (int) $partida['id'];

// Só entram as perguntas que já foram feitas nesta partida.
$atual = jogoPergunta($pdo, (int) $partida['quiz_id'], $partida['pergunta_atual_id'] ? (int) $partida['pergunta_atual_id'] : null);
$ordemLimite = $atual ? $atual['ordem'] : 0;

$statement = $pdo->prepare(
    'SELECT id, ordem, enunciado
     FROM perguntas
     WHERE quiz_id = :quiz_id
       AND ordem <= :ordem
     ORDER BY ordem ASC'
);

$statement->execute([
    'quiz_id' => $partida['quiz_id'],
    'ordem' => $ordemLimite,
]);

$perguntas = $statement->fetchAll();
$totalFeitas = count($perguntas);

$numeroDaPergunta = [];

foreach ($perguntas as $indice => $pergunta) {
    $numeroDaPergunta[(int) $pergunta['id']] = $indice + 1;
}

function csvCelula($valor): string
{
    $texto = (string) $valor;

    // Evita que o Excel interprete nome ou texto como fórmula.
    if ($texto !== '' && in_array($texto[0], ['=', '+', '-', '@'], true) && !is_numeric($texto)) {
        $texto = "'" . $texto;
    }

    return '"' . str_replace('"', '""', $texto) . '"';
}

function csvLinha(array $valores): string
{
    return implode(';', array_map('csvCelula', $valores)) . "\r\n";
}

function segundos(?float $milissegundos): string
{
    return $milissegundos === null ? '' : number_format($milissegundos / 1000, 1, ',', '');
}

$linhas = [];

if ($tipo === 'geral') {
    $linhas[] = ['Posição', 'Nome', 'Usuário Copel', 'Acertos', 'Erros', 'Sem resposta', 'Pontos', 'Tempo médio de resposta (s)'];

    foreach (jogoRanking($pdo, $partidaId) as $linha) {
        $linhas[] = [
            $linha['posicao'],
            $linha['nome'],
            $linha['usuario'],
            $linha['acertos'],
            $linha['respondidas'] - $linha['acertos'],
            max(0, $totalFeitas - $linha['respondidas']),
            $linha['pontos'],
            $linha['respondidas'] ? segundos($linha['tempo_medio_ms']) : '',
        ];
    }
}

if ($tipo === 'perguntas') {
    $participantes = jogoTotalParticipantes($pdo, $partidaId);

    $linhas[] = ['Nº', 'Pergunta', 'Resposta correta', 'Participantes', 'Responderam', 'Não responderam', 'A', 'B', 'C', 'D', 'Acertaram', '% de acerto', 'Tempo médio de resposta (s)'];

    $tempoMedio = $pdo->prepare(
        'SELECT AVG(respostas.tempo_ms)
         FROM respostas
         INNER JOIN participantes
            ON participantes.id = respostas.participante_id
         WHERE respostas.pergunta_id = :pergunta_id
           AND participantes.partida_id = :partida_id'
    );

    foreach ($perguntas as $pergunta) {
        $alternativas = jogoAlternativasComContagem($pdo, $partidaId, (int) $pergunta['id'], true);

        $porLetra = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
        $correta = '';
        $acertaram = 0;

        foreach ($alternativas as $alternativa) {
            $porLetra[$alternativa['letra']] = $alternativa['total'];

            if ($alternativa['correta']) {
                $correta = $alternativa['letra'];
                $acertaram = $alternativa['total'];
            }
        }

        $responderam = array_sum($porLetra);

        $tempoMedio->execute([
            'pergunta_id' => $pergunta['id'],
            'partida_id' => $partidaId,
        ]);

        $media = $tempoMedio->fetchColumn();

        $linhas[] = [
            $numeroDaPergunta[(int) $pergunta['id']],
            $pergunta['enunciado'],
            $correta,
            $participantes,
            $responderam,
            max(0, $participantes - $responderam),
            $porLetra['A'],
            $porLetra['B'],
            $porLetra['C'],
            $porLetra['D'],
            $acertaram,
            $participantes ? number_format($acertaram / $participantes * 100, 1, ',', '') . '%' : '',
            $media === null ? '' : segundos((float) $media),
        ];
    }
}

if ($tipo === 'respostas') {
    $linhas[] = ['Nome', 'Usuário Copel', 'Nº', 'Pergunta', 'Marcou', 'Resposta correta', 'Resultado', 'Tempo (s)', 'Pontos'];

    $statement = $pdo->prepare(
        'SELECT
            participantes.nome,
            participantes.usuario_copel,
            perguntas.id AS pergunta_id,
            perguntas.enunciado,
            marcada.letra AS marcou,
            certa.letra AS correta_letra,
            respostas.correta,
            respostas.tempo_ms,
            respostas.pontos
         FROM participantes
         INNER JOIN perguntas
            ON perguntas.quiz_id = :quiz_id
           AND perguntas.ordem <= :ordem
         LEFT JOIN respostas
            ON respostas.participante_id = participantes.id
           AND respostas.pergunta_id = perguntas.id
         LEFT JOIN alternativas marcada
            ON marcada.id = respostas.alternativa_id
         LEFT JOIN alternativas certa
            ON certa.pergunta_id = perguntas.id
           AND certa.correta = 1
         WHERE participantes.partida_id = :partida_id
         ORDER BY participantes.nome ASC, participantes.id ASC, perguntas.ordem ASC'
    );

    $statement->execute([
        'quiz_id' => $partida['quiz_id'],
        'ordem' => $ordemLimite,
        'partida_id' => $partidaId,
    ]);

    foreach ($statement->fetchAll() as $linha) {
        $respondeu = $linha['marcou'] !== null;

        $linhas[] = [
            $linha['nome'],
            $linha['usuario_copel'],
            $numeroDaPergunta[(int) $linha['pergunta_id']],
            $linha['enunciado'],
            $respondeu ? $linha['marcou'] : '',
            $linha['correta_letra'],
            $respondeu ? ($linha['correta'] ? 'Acertou' : 'Errou') : 'Sem resposta',
            $respondeu ? segundos((float) $linha['tempo_ms']) : '',
            $respondeu ? $linha['pontos'] : 0,
        ];
    }
}

$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $partida['titulo']) ?: 'quiz'), '-'));
$nomeArquivo = sprintf('%s-%s-%s-%s.csv', $tipo === 'geral' ? 'relatorio-geral' : $tipo, $slug ?: 'quiz', $codigo, date('Y-m-d'));

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
header('Cache-Control: no-store');

// BOM: faz o Excel abrir os acentos certos.
echo "\xEF\xBB\xBF";

foreach ($linhas as $linha) {
    echo csvLinha($linha);
}
