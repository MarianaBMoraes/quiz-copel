# Jogo ao vivo

Como a partida funciona depois que o administrador clica em **Iniciar quiz**.

## As três telas

| Tela | Endereço | Quem usa |
|---|---|---|
| Painel da partida | `/admin/partida.php?code=CODIGO` | Você, pra conduzir. Tem os botões. |
| Apresentação | `/apresentacao.php?code=CODIGO` | A tela que você compartilha no Teams. Não tem nenhum botão. |
| Celular | `/jogar.php?code=CODIGO` | Os participantes. Chegam aqui pelo QR Code ou pelo código. |

A apresentação abre pelo link **Abrir tela de apresentação** no painel e exige estar logado como administrador.

## As fases

```
aguardando → leitura (15 s) → respondendo (20 s) → resultado → [ranking] → leitura da próxima ... → finalizado
```

- **Leitura** e **respondendo** viram sozinhas pelo tempo. A resposta também encerra sozinha quando todos responderam.
- **Resultado**, **ranking** e **próxima pergunta** são botões do painel.
- Depois das perguntas 3 e 6 o painel destaca o botão **Mostrar ranking** (ranking parcial). Na última pergunta o botão vira **Ver ranking final**.
- **Encerrar questão** e **Encerrar jogo** pedem confirmação.

Quem vira as fases pelo tempo é o painel ou a apresentação aberta no seu computador (cada um consulta o servidor a cada segundo). **Deixe pelo menos uma das duas abertas durante o jogo.**

## Pontuação e ranking

- Resposta errada ou sem resposta: 0 ponto.
- Resposta certa: **500 pontos + até 500 pela velocidade**. Quem acerta no primeiro instante leva 1.000; quem acerta no último segundo leva perto de 500.
- O tempo conta só a partir do fim da leitura, pelo relógio do servidor (não pelo do celular).
- Ranking: **mais acertos primeiro**; empate em acertos, mais pontos; ainda empatado, menor tempo somado nos acertos; ainda empatado, quem entrou primeiro.

## Por que aguenta 130 celulares

Os celulares não consultam o PHP nem o MySQL a cada 2 segundos. Eles leem o arquivo `public/estado/CODIGO.json`, que o PHP regrava só quando algo muda (fase, número de participantes, número de respostas). Servir um arquivo pronto é o trabalho mais leve que existe pra hospedagem.

O servidor só é chamado em três momentos: ao entrar, ao responder (uma vez por pergunta) e para ver o próprio resultado (uma vez por fase, espalhado em 1,5 s).

O arquivo de estado não tem pergunta, alternativa, nome nem usuário: só a fase, os horários e as contagens.

## Regras de entrada

- Nunca pede senha. Só nome completo e usuário.
- **Quem chega atrasado entra**: só perde as perguntas que já passaram.
- Se a pessoa trocar de celular ou de navegador, é só entrar de novo com **o mesmo nome e o mesmo usuário**: ela volta pra mesma participação, com os pontos que já tinha. Com o mesmo usuário e outro nome, a entrada é bloqueada.
- Fechar e reabrir o navegador no mesmo celular volta direto pro jogo.

## Relatórios

Na tela da partida, depois de iniciar, aparecem três links (arquivos `.csv`, que abrem direto no Excel):

- **Relatório geral**: posição, nome, usuário, acertos, erros, sem resposta, pontos, tempo médio.
- **Análise por pergunta**: quantos responderam, quantos marcaram A/B/C/D, resposta correta, % de acerto, tempo médio.
- **Respostas de cada participante**: cada resposta de cada pessoa (o relatório individual).

## Arquivos do jogo

| Arquivo | O que faz |
|---|---|
| `app/jogo.php` | Regras: fases, pontuação, ranking e o arquivo de estado |
| `public/jogar.php` + `assets/js/jogar.js` | Tela do celular |
| `public/apresentacao.php` + `assets/js/apresentacao.js` | Tela do Teams |
| `public/admin/partida.php` + `assets/js/admin-partida.js` | Painel ao vivo |
| `public/api/responder.php` | Grava a resposta (o banco impede resposta duplicada) |
| `public/api/eu.php` | Resultado de quem está no celular |
| `public/api/hora.php` | Relógio do servidor pro celular acertar o cronômetro |
| `public/admin/api/tick.php` | Vira as fases pelo tempo e alimenta painel e apresentação |
| `public/admin/api/acao.php` | Botões do painel |
| `public/admin/exportar.php` | Relatórios CSV |
| `assets/css/jogo.css` | Visual das três telas |
| `scripts/seed-demo.php` | Cria um quiz de demonstração com 9 perguntas genéricas |
| `scripts/simular-partida.mjs` | Teste de carga com participantes virtuais |

## Rodar no computador

Precisa do Docker Desktop aberto. Na pasta do projeto:

```
docker compose up -d
```

O site abre em `http://localhost:8080`. O banco já sobe com as tabelas criadas. Crie o `.env` a partir do `.env.example` com `DB_HOST=db`, `DB_USER=quiz_app`, `DB_PASS=quiz-local`.

Para criar um administrador e o quiz de demonstração:

```
docker compose exec app php scripts/create-admin.php
docker compose exec app php scripts/seed-demo.php
```

## Publicar numa hospedagem compartilhada (cPanel, Hostinger)

1. Crie o banco MySQL e o usuário no painel da hospedagem.
2. No phpMyAdmin, rode `scripts/schema.sql` (apague a primeira linha `USE quiz_copel;` se o banco tiver outro nome) e depois `scripts/migrations/001_admin_roles.sql`.
3. Suba a **pasta inteira do projeto** para a pasta do domínio (menos `.git`, `docker` e `node_modules`). O `.htaccess` da raiz serve tudo de `public/` e esconde `app/`, `scripts/` e o `.env`.
4. Crie o `.env` na raiz com os dados do banco (use o `.env.example` como modelo).
5. Garanta que a pasta `public/estado` permite escrita (permissão 755).
6. Crie o administrador e rode o teste de carga contra o endereço publicado.

## Teste de carga

Simula a partida inteira com participantes virtuais (Node 20 ou mais novo):

```
node scripts/simular-partida.mjs --url https://ENDERECO --email ADMIN --senha SENHA --quiz ID_DO_QUIZ --participantes 130
```

Ele cria uma partida, faz os participantes entrarem, responderem e consultarem o resultado, conduz as 9 perguntas como o administrador e mostra no fim quantas requisições falharam e o tempo de cada uma. Todos os participantes virtuais saem do mesmo computador, que é mais difícil pro servidor do que 130 celulares diferentes.

Apague as partidas de teste pelo painel depois.

## Checklist do dia

1. Perguntas definitivas cadastradas e conferidas (resposta correta marcada em todas).
2. Uma partida de ensaio com 2 ou 3 celulares de verdade (Android e iPhone), do começo ao fim.
3. Criar a partida real, abrir o painel e clicar em **Abrir tela de apresentação**.
4. No Teams, compartilhar **a janela da apresentação** (não a tela inteira), pra ninguém ver o painel.
5. Esperar o contador de participantes estabilizar e clicar em **Iniciar quiz**.
6. Deixar o painel aberto até o fim. Ao terminar, baixar os três relatórios.

## Ficou para depois

- Relatório em `.xlsx` e PDF individual (hoje sai em `.csv`, que o Excel abre).
- Baixar todos os individuais num `.zip`.
- Botão de pausar no meio da pergunta.
- Tela de histórico de partidas.
- Tempos de leitura e resposta configuráveis pelo painel (o banco já guarda por pergunta).
