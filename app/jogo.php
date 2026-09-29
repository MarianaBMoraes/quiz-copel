<?php

/*
|--------------------------------------------------------------------------
| Regras do jogo ao vivo
|--------------------------------------------------------------------------
| Fases da partida, pontuação, ranking e o arquivo de estado que os
| celulares leem. O relógio oficial é sempre o do MySQL (NOW(6)).
|
| Fases: aguardando -> leitura -> respondendo -> resultado
|        -> (ranking) -> leitura da próxima ... -> finalizado
*/

// Aviso do PHP antes do JSON quebraria o painel e os celulares; vai só pro log.
ini_set('display_errors', '0');

// Resposta enviada no último instante pode chegar um pouco depois do fim.
const JOGO_TOLERANCIA_MS = 1000;

// Acertar vale 500. A velocidade soma até mais 500 (desempate).
const JOGO_PONTOS_ACERTO = 500;
const JOGO_PONTOS_VELOCIDADE = 500;

const JOGO_PASTA_ESTADO = __DIR__ . '/../public/estado';

function jogoCarregarPartida(PDO $pdo, string $codigo): ?array
{
    $statement = $pdo->prepare(
        'SELECT
            partidas.id,
            partidas.quiz_id,
            partidas.codigo,
            partidas.status,
            partidas.pergunta_atual_id,
            UNIX_TIMESTAMP(partidas.fase_iniciada_em) * 1000 AS inicio_ms,
            UNIX_TIMESTAMP(partidas.fase_termina_em) * 1000 AS fim_ms,
            UNIX_TIMESTAMP(NOW(6)) * 1000 AS agora_ms,
            quizzes.titulo,
            quizzes.subtitulo
         FROM partidas
         INNER JOIN quizzes
            ON quizzes.id = partidas.quiz_id
         WHERE partidas.codigo = :codigo
         LIMIT 1'
    );

    $statement->execute([
        'codigo' => $codigo,
    ]);

    $partida = $statement->fetch();

    if (!$partida) {
        return null;
    }

    foreach (['inicio_ms', 'fim_ms', 'agora_ms'] as $campo) {
        $partida[$campo] = $partida[$campo] === null
            ? null
            : (int) round((float) $partida[$campo]);
    }

    return $partida;
}

function jogoCodigoValido(?string $codigo): ?string
{
    $codigo = preg_replace('/\D/', '', (string) $codigo);

    return strlen($codigo) === 6 ? $codigo : null;
}

function jogoTotalParticipantes(PDO $pdo, int $partidaId): int
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM participantes
         WHERE partida_id = :partida_id'
    );

    $statement->execute([
        'partida_id' => $partidaId,
    ]);

    return (int) $statement->fetchColumn();
}

function jogoTotalRespondidos(PDO $pdo, int $partidaId, ?int $perguntaId): int
{
    if (!$perguntaId) {
        return 0;
    }

    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM respostas
         INNER JOIN participantes
            ON participantes.id = respostas.participante_id
         WHERE respostas.pergunta_id = :pergunta_id
           AND participantes.partida_id = :partida_id'
    );

    $statement->execute([
        'pergunta_id' => $perguntaId,
        'partida_id' => $partidaId,
    ]);

    return (int) $statement->fetchColumn();
}

/*
| Pergunta atual com posição (3 de 9). A posição vem da ordem,
| então continua certa mesmo se alguma pergunta foi excluída.
*/
function jogoPergunta(PDO $pdo, int $quizId, ?int $perguntaId): ?array
{
    if (!$perguntaId) {
        return null;
    }

    $statement = $pdo->prepare(
        'SELECT
            perguntas.id,
            perguntas.ordem,
            perguntas.enunciado,
            perguntas.explicacao,
            perguntas.tempo_leitura,
            perguntas.tempo_resposta,
            (
                SELECT COUNT(*)
                FROM perguntas anteriores
                WHERE anteriores.quiz_id = perguntas.quiz_id
                  AND anteriores.ordem <= perguntas.ordem
            ) AS indice,
            (
                SELECT COUNT(*)
                FROM perguntas todas
                WHERE todas.quiz_id = perguntas.quiz_id
            ) AS total
         FROM perguntas
         WHERE perguntas.id = :id
           AND perguntas.quiz_id = :quiz_id
         LIMIT 1'
    );

    $statement->execute([
        'id' => $perguntaId,
        'quiz_id' => $quizId,
    ]);

    $pergunta = $statement->fetch();

    if (!$pergunta) {
        return null;
    }

    foreach (['id', 'ordem', 'tempo_leitura', 'tempo_resposta', 'indice', 'total'] as $campo) {
        $pergunta[$campo] = (int) $pergunta[$campo];
    }

    return $pergunta;
}

function jogoProximaPergunta(PDO $pdo, int $quizId, ?int $perguntaAtualId): ?array
{
    $ordemAtual = 0;

    if ($perguntaAtualId) {
        $statement = $pdo->prepare(
            'SELECT ordem FROM perguntas WHERE id = :id LIMIT 1'
        );

        $statement->execute([
            'id' => $perguntaAtualId,
        ]);

        $ordemAtual = (int) $statement->fetchColumn();
    }

    $statement = $pdo->prepare(
        'SELECT id, tempo_leitura
         FROM perguntas
         WHERE quiz_id = :quiz_id
           AND ordem > :ordem
         ORDER BY ordem ASC
         LIMIT 1'
    );

    $statement->execute([
        'quiz_id' => $quizId,
        'ordem' => $ordemAtual,
    ]);

    $pergunta = $statement->fetch();

    return $pergunta ?: null;
}

/*
|--------------------------------------------------------------------------
| Mudanças de fase automáticas (tempo esgotado ou todos responderam)
|--------------------------------------------------------------------------
| Pode ser chamada por qualquer requisição: o UPDATE só acontece se a
| partida ainda estiver na fase esperada, então chamar duas vezes é seguro.
*/
function jogoAvancar(PDO $pdo, array $partida): bool
{
    $agora = $partida['agora_ms'];

    if (
        $partida['status'] === 'leitura'
        && $partida['fim_ms'] !== null
        && $agora >= $partida['fim_ms']
    ) {
        $pergunta = jogoPergunta($pdo, (int) $partida['quiz_id'], (int) $partida['pergunta_atual_id']);

        if (!$pergunta) {
            return false;
        }

        // A resposta começa exatamente quando a leitura termina,
        // mesmo que esta requisição tenha chegado alguns ms depois.
        $statement = $pdo->prepare(
            'UPDATE partidas
             SET
                status = :novo_status,
                fase_iniciada_em = fase_termina_em,
                fase_termina_em = DATE_ADD(
                    fase_termina_em,
                    INTERVAL :tempo SECOND
                )
             WHERE id = :id
               AND status = :status_atual
               AND pergunta_atual_id = :pergunta_id
               AND fase_termina_em <= NOW(6)'
        );

        $statement->execute([
            'novo_status' => 'respondendo',
            'tempo' => $pergunta['tempo_resposta'],
            'id' => $partida['id'],
            'status_atual' => 'leitura',
            'pergunta_id' => $pergunta['id'],
        ]);

        return $statement->rowCount() === 1;
    }

    if ($partida['status'] === 'respondendo') {
        $tempoAcabou = $partida['fim_ms'] !== null
            && $agora >= $partida['fim_ms'] + JOGO_TOLERANCIA_MS;

        $todosResponderam = false;

        if (!$tempoAcabou) {
            $participantes = jogoTotalParticipantes($pdo, (int) $partida['id']);
            $respondidos = jogoTotalRespondidos($pdo, (int) $partida['id'], (int) $partida['pergunta_atual_id']);
            $todosResponderam = $participantes > 0 && $respondidos >= $participantes;
        }

        if ($tempoAcabou || $todosResponderam) {
            // Repete as condições no próprio UPDATE: se outra requisição já
            // passou pra próxima pergunta, esta não encerra a pergunta nova.
            $statement = $pdo->prepare(
                'UPDATE partidas
                 SET status = :novo_status
                 WHERE id = :id
                   AND status = :status_atual
                   AND pergunta_atual_id = :pergunta_id'
                . ($todosResponderam ? '' : ' AND fase_termina_em <= DATE_SUB(NOW(6), INTERVAL 1 SECOND)')
            );

            $statement->execute([
                'novo_status' => 'resultado',
                'id' => $partida['id'],
                'status_atual' => 'respondendo',
                'pergunta_id' => $partida['pergunta_atual_id'],
            ]);

            return $statement->rowCount() === 1;
        }
    }

    return false;
}

function jogoMudarStatus(PDO $pdo, int $partidaId, array $statusPermitidos, string $novoStatus): bool
{
    $parametros = [
        'novo_status' => $novoStatus,
        'id' => $partidaId,
    ];

    $marcadores = [];

    foreach (array_values($statusPermitidos) as $indice => $status) {
        $marcadores[] = ':permitido_' . $indice;
        $parametros['permitido_' . $indice] = $status;
    }

    $finalizada = $novoStatus === 'finalizado'
        ? ', finalizada_em = NOW(6)'
        : '';

    $statement = $pdo->prepare(
        'UPDATE partidas
         SET status = :novo_status' . $finalizada . '
         WHERE id = :id
           AND status IN (' . implode(', ', $marcadores) . ')'
    );

    $statement->execute($parametros);

    return $statement->rowCount() === 1;
}

function jogoIniciarPergunta(PDO $pdo, int $partidaId, array $pergunta, array $statusPermitidos): bool
{
    $parametros = [
        'novo_status' => 'leitura',
        'pergunta_id' => $pergunta['id'],
        'tempo' => (int) $pergunta['tempo_leitura'],
        'id' => $partidaId,
    ];

    $marcadores = [];

    foreach (array_values($statusPermitidos) as $indice => $status) {
        $marcadores[] = ':permitido_' . $indice;
        $parametros['permitido_' . $indice] = $status;
    }

    $statement = $pdo->prepare(
        'UPDATE partidas
         SET
            status = :novo_status,
            pergunta_atual_id = :pergunta_id,
            iniciada_em = COALESCE(iniciada_em, NOW(6)),
            fase_iniciada_em = NOW(6),
            fase_termina_em = DATE_ADD(NOW(6), INTERVAL :tempo SECOND)
         WHERE id = :id
           AND status IN (' . implode(', ', $marcadores) . ')'
    );

    $statement->execute($parametros);

    return $statement->rowCount() === 1;
}

/*
|--------------------------------------------------------------------------
| Ações do administrador
|--------------------------------------------------------------------------
*/
function jogoExecutarAcao(PDO $pdo, array $partida, string $acao): bool
{
    $partidaId = (int) $partida['id'];
    $quizId = (int) $partida['quiz_id'];

    switch ($acao) {
        case 'iniciar':
            $primeira = jogoProximaPergunta($pdo, $quizId, null);

            return $primeira
                && jogoIniciarPergunta($pdo, $partidaId, $primeira, ['aguardando']);

        case 'encerrar_questao':
            return jogoMudarStatus($pdo, $partidaId, ['leitura', 'respondendo'], 'resultado');

        case 'mostrar_ranking':
            return jogoMudarStatus($pdo, $partidaId, ['resultado'], 'ranking');

        case 'proxima':
            $proxima = jogoProximaPergunta($pdo, $quizId, (int) $partida['pergunta_atual_id']);

            if (!$proxima) {
                return jogoMudarStatus($pdo, $partidaId, ['resultado', 'ranking'], 'finalizado');
            }

            return jogoIniciarPergunta($pdo, $partidaId, $proxima, ['resultado', 'ranking']);

        case 'finalizar':
            return jogoMudarStatus(
                $pdo,
                $partidaId,
                ['aguardando', 'leitura', 'respondendo', 'resultado', 'ranking', 'pausado'],
                'finalizado'
            );
    }

    return false;
}

/*
|--------------------------------------------------------------------------
| Pontuação e ranking
|--------------------------------------------------------------------------
*/
function jogoCalcularPontos(bool $correta, int $tempoMs, int $tempoRespostaSegundos): int
{
    if (!$correta) {
        return 0;
    }

    $limiteMs = max(1, $tempoRespostaSegundos * 1000);
    $fracaoRestante = 1 - min(1, max(0, $tempoMs / $limiteMs));

    return (int) round(JOGO_PONTOS_ACERTO + JOGO_PONTOS_VELOCIDADE * $fracaoRestante);
}

/*
| Ordem do ranking: mais acertos, depois mais pontos (velocidade),
| depois menor tempo somado nos acertos, depois quem entrou primeiro.
*/
function jogoRanking(PDO $pdo, int $partidaId): array
{
    $statement = $pdo->prepare(
        'SELECT
            participantes.id,
            participantes.nome,
            participantes.usuario_copel,
            COUNT(respostas.id) AS respondidas,
            COALESCE(SUM(respostas.correta), 0) AS acertos,
            COALESCE(SUM(respostas.pontos), 0) AS pontos,
            COALESCE(SUM(CASE WHEN respostas.correta = 1 THEN respostas.tempo_ms ELSE 0 END), 0) AS tempo_acertos_ms,
            COALESCE(AVG(respostas.tempo_ms), 0) AS tempo_medio_ms
         FROM participantes
         LEFT JOIN respostas
            ON respostas.participante_id = participantes.id
         WHERE participantes.partida_id = :partida_id
         GROUP BY
            participantes.id,
            participantes.nome,
            participantes.usuario_copel,
            participantes.entrou_em
         ORDER BY
            acertos DESC,
            pontos DESC,
            tempo_acertos_ms ASC,
            participantes.entrou_em ASC,
            participantes.id ASC'
    );

    $statement->execute([
        'partida_id' => $partidaId,
    ]);

    $ranking = [];

    foreach ($statement->fetchAll() as $indice => $linha) {
        $ranking[] = [
            'posicao' => $indice + 1,
            'id' => (int) $linha['id'],
            'nome' => $linha['nome'],
            'usuario' => $linha['usuario_copel'],
            'respondidas' => (int) $linha['respondidas'],
            'acertos' => (int) $linha['acertos'],
            'pontos' => (int) $linha['pontos'],
            'tempo_medio_ms' => (int) round((float) $linha['tempo_medio_ms']),
        ];
    }

    return $ranking;
}

function jogoAlternativasComContagem(PDO $pdo, int $partidaId, int $perguntaId, bool $revelar): array
{
    $statement = $pdo->prepare(
        'SELECT
            alternativas.letra,
            alternativas.texto,
            alternativas.correta,
            COUNT(participantes.id) AS total
         FROM alternativas
         LEFT JOIN respostas
            ON respostas.alternativa_id = alternativas.id
         LEFT JOIN participantes
            ON participantes.id = respostas.participante_id
           AND participantes.partida_id = :partida_id
         WHERE alternativas.pergunta_id = :pergunta_id
         GROUP BY
            alternativas.id,
            alternativas.letra,
            alternativas.texto,
            alternativas.correta
         ORDER BY alternativas.letra ASC'
    );

    $statement->execute([
        'partida_id' => $partidaId,
        'pergunta_id' => $perguntaId,
    ]);

    $alternativas = [];

    foreach ($statement->fetchAll() as $linha) {
        $alternativa = [
            'letra' => $linha['letra'],
            'texto' => $linha['texto'],
        ];

        if ($revelar) {
            $alternativa['correta'] = (bool) $linha['correta'];
            $alternativa['total'] = (int) $linha['total'];
        }

        $alternativas[] = $alternativa;
    }

    return $alternativas;
}

/*
|--------------------------------------------------------------------------
| Arquivo de estado público
|--------------------------------------------------------------------------
| public/estado/<codigo>.json é um arquivo comum: o servidor entrega sem
| rodar PHP e sem tocar no MySQL, por isso aguenta os celulares
| consultando a cada 2 segundos. Só leva o necessário pro celular:
| nada de pergunta, alternativa, nome ou usuário.
*/
function jogoEstadoPublico(PDO $pdo, array $partida): array
{
    $pergunta = jogoPergunta($pdo, (int) $partida['quiz_id'], $partida['pergunta_atual_id'] ? (int) $partida['pergunta_atual_id'] : null);

    return [
        'status' => $partida['status'],
        'pergunta' => $pergunta ? [
            'id' => $pergunta['id'],
            'indice' => $pergunta['indice'],
            'total' => $pergunta['total'],
            'tempo_resposta' => $pergunta['tempo_resposta'],
        ] : null,
        'inicio_ms' => $partida['inicio_ms'],
        'fim_ms' => $partida['fim_ms'],
        'participantes' => jogoTotalParticipantes($pdo, (int) $partida['id']),
        'respondidos' => jogoTotalRespondidos($pdo, (int) $partida['id'], $partida['pergunta_atual_id'] ? (int) $partida['pergunta_atual_id'] : null),
    ];
}

function jogoPublicarEstado(PDO $pdo, string $codigo): ?array
{
    $partida = jogoCarregarPartida($pdo, $codigo);

    if (!$partida) {
        return null;
    }

    $estado = jogoEstadoPublico($pdo, $partida);
    $conteudo = json_encode($estado, JSON_UNESCAPED_UNICODE);

    if (!is_dir(JOGO_PASTA_ESTADO)) {
        mkdir(JOGO_PASTA_ESTADO, 0755, true);
    }

    $arquivo = JOGO_PASTA_ESTADO . '/' . $codigo . '.json';

    if (!is_file($arquivo) || file_get_contents($arquivo) !== $conteudo) {
        // Grava num temporário e troca de uma vez, pra ninguém ler arquivo pela metade.
        $temporario = $arquivo . '.' . bin2hex(random_bytes(4)) . '.tmp';
        file_put_contents($temporario, $conteudo);
        rename($temporario, $arquivo);
    }

    return $estado;
}

/*
| Partida em andamento com este quiz: enquanto houver, editar, excluir
| ou reordenar perguntas quebraria o jogo e apagaria respostas.
*/
function jogoQuizEmAndamento(PDO $pdo, int $quizId): bool
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM partidas
         WHERE quiz_id = :quiz_id
           AND status NOT IN (:aguardando, :finalizado)'
    );

    $statement->execute([
        'quiz_id' => $quizId,
        'aguardando' => 'aguardando',
        'finalizado' => 'finalizado',
    ]);

    return (int) $statement->fetchColumn() > 0;
}

function jogoRemoverEstado(string $codigo): void
{
    $arquivo = JOGO_PASTA_ESTADO . '/' . $codigo . '.json';

    if (is_file($arquivo)) {
        unlink($arquivo);
    }
}

function jogoParticipantePorCookie(PDO $pdo, int $partidaId): ?array
{
    $token = $_COOKIE['quiz_participant'] ?? '';

    if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }

    $statement = $pdo->prepare(
        'SELECT id, nome, usuario_copel
         FROM participantes
         WHERE token_reconexao_hash = :hash
           AND partida_id = :partida_id
         LIMIT 1'
    );

    $statement->execute([
        'hash' => hash('sha256', $token),
        'partida_id' => $partidaId,
    ]);

    $participante = $statement->fetch();

    return $participante ?: null;
}

function jogoResponderJson(array $dados, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}
