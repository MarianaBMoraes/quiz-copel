-- Pausa do cronômetro: em que fase a partida estava e quando foi pausada.
ALTER TABLE partidas
    ADD COLUMN status_pausado ENUM('leitura', 'respondendo') NULL AFTER status,
    ADD COLUMN pausada_em DATETIME(6) NULL AFTER fase_termina_em;
