USE quiz_copel;

CREATE TABLE administradores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_administradores_email (email)
);

CREATE TABLE quizzes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(180) NOT NULL,
    subtitulo VARCHAR(255) NULL,
    descricao TEXT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE perguntas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id BIGINT UNSIGNED NOT NULL,
    ordem INT UNSIGNED NOT NULL,
    enunciado TEXT NOT NULL,
    explicacao TEXT NULL,
    tempo_leitura SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    tempo_resposta SMALLINT UNSIGNED NOT NULL DEFAULT 20,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_perguntas_quiz
        FOREIGN KEY (quiz_id)
        REFERENCES quizzes(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_perguntas_quiz_ordem (quiz_id, ordem),
    KEY idx_perguntas_quiz (quiz_id)
);

CREATE TABLE alternativas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pergunta_id BIGINT UNSIGNED NOT NULL,
    letra ENUM('A', 'B', 'C', 'D') NOT NULL,
    texto TEXT NOT NULL,
    correta TINYINT(1) NOT NULL DEFAULT 0,

    CONSTRAINT fk_alternativas_pergunta
        FOREIGN KEY (pergunta_id)
        REFERENCES perguntas(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_alternativas_pergunta_letra (pergunta_id, letra),
    KEY idx_alternativas_pergunta (pergunta_id)
);

CREATE TABLE partidas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id BIGINT UNSIGNED NOT NULL,
    codigo CHAR(6) NOT NULL,

    status ENUM(
        'aguardando',
        'leitura',
        'respondendo',
        'resultado',
        'ranking',
        'pausado',
        'finalizado'
    ) NOT NULL DEFAULT 'aguardando',

    pergunta_atual_id BIGINT UNSIGNED NULL,

    fase_iniciada_em DATETIME(6) NULL,
    fase_termina_em DATETIME(6) NULL,

    iniciada_em DATETIME(6) NULL,
    finalizada_em DATETIME(6) NULL,

    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_partidas_quiz
        FOREIGN KEY (quiz_id)
        REFERENCES quizzes(id),

    CONSTRAINT fk_partidas_pergunta_atual
        FOREIGN KEY (pergunta_atual_id)
        REFERENCES perguntas(id)
        ON DELETE SET NULL,

    UNIQUE KEY uq_partidas_codigo (codigo),
    KEY idx_partidas_quiz (quiz_id),
    KEY idx_partidas_status (status)
);

CREATE TABLE participantes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partida_id BIGINT UNSIGNED NOT NULL,

    nome VARCHAR(160) NOT NULL,
    usuario_copel VARCHAR(100) NOT NULL,

    token_reconexao_hash CHAR(64) NULL,

    status ENUM(
        'conectado',
        'desconectado',
        'finalizado'
    ) NOT NULL DEFAULT 'conectado',

    entrou_em DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    ultima_atividade_em DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),

    CONSTRAINT fk_participantes_partida
        FOREIGN KEY (partida_id)
        REFERENCES partidas(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_participante_usuario_partida (
        partida_id,
        usuario_copel
    ),

    UNIQUE KEY uq_participante_token (
        token_reconexao_hash
    ),

    KEY idx_participantes_partida (partida_id)
);

CREATE TABLE respostas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    participante_id BIGINT UNSIGNED NOT NULL,
    pergunta_id BIGINT UNSIGNED NOT NULL,
    alternativa_id BIGINT UNSIGNED NOT NULL,

    respondido_em DATETIME(6) NOT NULL,
    tempo_ms INT UNSIGNED NOT NULL,

    correta TINYINT(1) NOT NULL,
    pontos INT UNSIGNED NOT NULL DEFAULT 0,

    CONSTRAINT fk_respostas_participante
        FOREIGN KEY (participante_id)
        REFERENCES participantes(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_respostas_pergunta
        FOREIGN KEY (pergunta_id)
        REFERENCES perguntas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_respostas_alternativa
        FOREIGN KEY (alternativa_id)
        REFERENCES alternativas(id),

    UNIQUE KEY uq_resposta_participante_pergunta (
        participante_id,
        pergunta_id
    ),

    KEY idx_respostas_pergunta (pergunta_id),
    KEY idx_respostas_participante (participante_id)
);