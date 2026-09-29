# Quiz Copel

PROMPT MESTRE FINAL
PROJETO: PLATAFORMA DE QUIZ AO VIVO
PRIMEIRA IMPLEMENTAÇÃO: COPEL DISTRIBUIÇÃO

Quero que você atue como meu desenvolvedor, arquiteto de software e professor durante a criação de uma plataforma web de quiz ao vivo.

A primeira implementação será personalizada para a Copel Distribuição, mas a arquitetura deve ser independente da marca para permitir posteriormente uma versão genérica e pública para meu portfólio.

A experiência poderá ser inspirada na dinâmica de plataformas de quiz ao vivo como o Kahoot, porém não quero copiar código, interface, identidade visual, marca ou elementos protegidos de terceiros.

Leia TODO este documento antes de começar.

==================================================
1. MEU NÍVEL DE CONHECIMENTO
==================================================

Sou iniciante em programação.

Uso:

Windows
VS Code

Preciso de orientação realmente passo a passo.

Durante todo o desenvolvimento:

1. Não presuma que eu saiba programação.

2. Trabalhe uma etapa por vez.

3. Diga exatamente qual pasta criar.

4. Diga exatamente qual arquivo criar ou abrir.

5. Quando fornecer código, diga claramente em qual arquivo ele deve ser colocado.

6. Se for necessário substituir código, diga exatamente o que apagar e o que deve permanecer.

7. Explique como salvar.

8. Explique como testar.

9. Aguarde meu retorno dizendo se funcionou antes de avançar.

10. Se ocorrer erro, primeiro me ajude a corrigir o erro.

11. Não entregue dezenas de etapas de uma vez.

12. Quando utilizarmos terminal, forneça exatamente o comando que devo copiar e colar.

13. Diga onde devo executar o comando.

14. Explique brevemente o que o comando faz.

15. Nunca exponha senhas, credenciais ou dados sensíveis no código.

16. Priorize soluções que eu consiga posteriormente entender e manter.

17. Desenvolva pensando desde o início na publicação real.

18. Não altere a arquitetura principal durante o projeto sem explicar o motivo e discutir comigo primeiro.

19. Quando houver mais de uma solução técnica, explique as opções de forma simples e recomende a mais adequada antes de implementar.

20. Sempre considere que o sistema será utilizado em situação real com aproximadamente 130 participantes simultâneos.

21. Não avance simplesmente porque uma etapa parece óbvia.

22. Depois de cada etapa importante, espere minha confirmação.

==================================================
2. OBJETIVO DO PROJETO
==================================================

Quero desenvolver uma plataforma própria de quiz ao vivo.

A primeira utilização será em um treinamento relacionado à Copel Distribuição.

O primeiro quiz terá o tema:

"Transgressões: causas, impactos e aprendizados"

A plataforma deverá permitir:

criação de quizzes;
criação de partidas;
entrada por código;
entrada por QR Code;
participantes conectados simultaneamente;
perguntas sincronizadas;
cronômetros;
respostas pelo celular;
pontuação;
ranking;
painel administrativo;
histórico;
relatórios;
exportações.

A plataforma deverá ser REUTILIZÁVEL.

Não quero um sistema limitado às primeiras 9 perguntas.

No futuro quero criar novos quizzes pelo painel administrativo sem alterar o código.

==================================================
3. CENÁRIO DE UTILIZAÇÃO
==================================================

O primeiro evento poderá ter aproximadamente:

130 participantes simultâneos.

A apresentação será realizada pelo:

Microsoft Teams.

Eu estarei no meu computador compartilhando uma tela específica da apresentação.

Os participantes utilizarão principalmente seus celulares.

Teremos três interfaces:

1. PAINEL DO ADMINISTRADOR

Privado.

Utilizado por mim para controlar a partida.

2. TELA DE APRESENTAÇÃO

Será compartilhada pelo Microsoft Teams.

Deverá mostrar:

entrada dos participantes;
código;
QR Code;
perguntas;
cronômetros;
resultados;
explicações;
rankings.

Não deverá mostrar controles administrativos.

3. TELA DO PARTICIPANTE

Acessada pelo celular.

Durante a resposta, deverá mostrar principalmente:

A
B
C
D

A pergunta completa será apresentada na tela compartilhada pelo Teams.

==================================================
4. HOSPEDAGEM DISPONÍVEL
==================================================

Já possuo:

HostGator
Plano M
Hospedagem compartilhada
cPanel

Foi verificado diretamente no cPanel que existem:

PHP
Gerenciador de MultiPHP
MultiPHP INI Editor
phpMyAdmin
Database Wizard
Manage My Databases
Remote Database Access
SSH
Git Version Control

Foi pesquisado:

Node

E NÃO apareceram:

Setup Node.js App
Node.js Selector
Application Manager

Portanto, neste momento, NÃO considere que Node.js possa ser executado diretamente na hospedagem.

A arquitetura base inicialmente considerada é:

Frontend:
HTML
CSS
JavaScript

Backend:
PHP

Banco:
MySQL

Gerenciamento do banco:
phpMyAdmin

Hospedagem:
HostGator Plano M

Essa arquitetura poderá ser refinada tecnicamente, mas não deverá ser alterada sem explicar e discutir comigo.

==================================================
5. COMUNICAÇÃO EM TEMPO REAL
==================================================

Existe um requisito crítico:

aproximadamente 130 participantes poderão estar conectados simultaneamente.

Precisaremos sincronizar:

entrada dos participantes;
início da partida;
início da pergunta;
fase de leitura;
fase de resposta;
cronômetros;
respostas;
quantidade de pessoas que responderam;
encerramento antecipado;
resultados;
ranking;
finalização.

Não quero uma arquitetura ruim em que aproximadamente 130 celulares consultem o MySQL a cada fração de segundo.

Antes de implementar a comunicação em tempo real, analise qual solução é adequada considerando:

PHP;
MySQL;
HostGator compartilhada;
aproximadamente 130 participantes.

Se for necessário utilizar serviço externo para comunicação em tempo real, explique ANTES:

por que precisamos;
qual serviço;
se existe custo;
limites gratuitos;
como integra com PHP/MySQL;
quais dados passam pelo serviço;
quais dados continuam no MySQL;
vantagens;
desvantagens;
dependência futura.

Não implemente serviço externo sem minha aprovação.

==================================================
6. DOMÍNIO
==================================================

Ainda não possuo domínio.

Quero desenvolver e testar primeiro.

Posteriormente poderemos adquirir um domínio.

O sistema deverá ser estruturado de maneira que adicionar ou alterar domínio posteriormente seja simples.

==================================================
7. PRIMEIRO QUIZ
==================================================

Nome:

Transgressões

Subtítulo:

Causas, impactos e aprendizados

Inicialmente teremos:

9 perguntas.

O sistema NÃO deverá ser limitado a 9 perguntas.

Cada pergunta deverá possuir:

Pergunta
Alternativa A
Alternativa B
Alternativa C
Alternativa D
Resposta correta
Explicação/aprendizado opcional

As perguntas definitivas serão fornecidas posteriormente.

Não invente conteúdo técnico como se fosse definitivo.

==================================================
8. GERENCIAMENTO DE QUIZZES
==================================================

No painel administrativo quero:

MEUS QUIZZES

O administrador deverá conseguir:

Criar quiz
Editar quiz
Excluir quiz
Adicionar perguntas
Remover perguntas
Reordenar perguntas
Editar alternativas
Definir resposta correta
Adicionar explicação/aprendizado
Salvar quiz

Se for simples implementar posteriormente:

Duplicar quiz.

Exemplo:

MEUS QUIZZES

Transgressões
Causas, impactos e aprendizados
9 perguntas

[EDITAR]

[INICIAR JOGO]

[NOVO QUIZ]

==================================================
9. CRIAÇÃO DA PARTIDA
==================================================

Quando eu escolher um quiz e clicar:

INICIAR JOGO

o sistema deverá criar uma nova partida.

Cada partida deverá possuir:

identificador interno único;
quiz associado;
data;
horário;
status;
código de entrada.

O sistema deverá gerar automaticamente um código curto.

Exemplo:

583 214

O código deverá ser:

fácil de visualizar;
fácil de digitar;
temporário;
associado àquela partida.

==================================================
10. QR CODE
==================================================

Na tela inicial da apresentação quero mostrar:

identidade visual;
nome do quiz;
QR Code;
código da partida;
quantidade de participantes conectados.

Exemplo:

COPEL DISTRIBUIÇÃO

TRANSGRESSÕES
Causas, impactos e aprendizados

[QR CODE]

Código:

583 214

87 participantes conectados

O QR Code deverá direcionar o participante para a entrada da partida.

==================================================
11. ENTRADA DO PARTICIPANTE
==================================================

O participante acessará pelo celular.

Primeiro:

Digite o código da partida.

Depois deverá preencher:

Nome completo
Usuário Copel

Os dois campos serão obrigatórios na primeira implementação.

IMPORTANTE:

"Usuário Copel" significa somente o identificador corporativo.

NUNCA solicitar senha corporativa.

NUNCA armazenar senha corporativa.

O sistema deverá impedir que o mesmo usuário Copel participe duas vezes da mesma partida, se tecnicamente viável.

Após entrar:

"Você entrou!"

Nome do participante

"Aguarde o administrador iniciar o jogo."

==================================================
12. SALA DE ESPERA
==================================================

Antes do início:

A apresentação deverá mostrar a quantidade de participantes conectados.

Exemplo:

47 participantes
63 participantes
89 participantes
130 participantes

No painel administrativo quero visualizar:

Nome
Usuário Copel
Status

E:

[INICIAR QUIZ]

==================================================
13. FUNCIONAMENTO DAS PERGUNTAS
==================================================

Cada questão terá inicialmente duas fases.

FASE 1: LEITURA

Duração:

15 segundos.

Durante esse período:

a pergunta aparece na apresentação;

o cronômetro aparece;

o participante NÃO pode responder.

Exemplo:

PERGUNTA 3 DE 9

[texto da pergunta]

00:15

FASE 2: RESPOSTA

Depois dos 15 segundos:

as quatro alternativas aparecem na apresentação;

os participantes são liberados para responder;

o cronômetro passa para:

20 segundos.

Inicialmente:

15 segundos de leitura
20 segundos de resposta

Posteriormente poderemos tornar esses tempos configuráveis pelo administrador.

==================================================
14. TELA DO PARTICIPANTE
==================================================

No celular não quero repetir toda a pergunta durante a fase de resposta.

Quero uma interface simples e rápida.

Exemplo:

ESCOLHA SUA RESPOSTA

[A]          [B]

[C]          [D]

Os botões deverão ser:

grandes;
responsivos;
fáceis de tocar;
claramente identificados.

Após selecionar:

registrar a resposta imediatamente;

não permitir alteração.

Mostrar:

"Resposta registrada"

"Aguarde o resultado..."

==================================================
15. ENCERRAMENTO DA QUESTÃO
==================================================

A questão deverá terminar quando acontecer primeiro:

A. os 20 segundos terminarem;

OU

B. todos os participantes elegíveis tiverem respondido.

Exemplo:

130 participantes

130 respostas

Nesse caso, não precisamos aguardar o cronômetro terminar.

O sistema poderá encerrar automaticamente.

O administrador também deverá possuir:

[ENCERRAR QUESTÃO]

para situações excepcionais.

==================================================
16. RESULTADO DA QUESTÃO
==================================================

Depois do encerramento:

Mostrar na apresentação:

Resposta correta
Quantidade A
Quantidade B
Quantidade C
Quantidade D

Utilizar representação visual clara.

Exemplo:

A 31
B 19
C 67
D 13

Resposta correta:

C

Se existir explicação/aprendizado cadastrado, permitir mostrá-lo nessa etapa.

==================================================
17. PONTUAÇÃO
==================================================

A pontuação deverá considerar:

1. resposta correta;
2. velocidade.

Resposta errada:

0 pontos.

Resposta correta:

recebe pontuação.

A velocidade deverá servir como bonificação/desempate.

Não quero que velocidade seja mais importante que conhecimento.

Podemos trabalhar inicialmente com máximo aproximado de:

1.000 pontos por pergunta.

Antes de implementar, explique a fórmula proposta.

O tempo da pontuação começa somente quando a fase de resposta é liberada.

Os 15 segundos de leitura não entram no cálculo.

==================================================
18. RANKING
==================================================

A prioridade deverá ser:

1. maior número de respostas corretas;

2. entre participantes com o mesmo número de acertos, maior pontuação relacionada à velocidade;

3. se ainda houver empate, utilizar critério técnico consistente previamente definido.

Isso evita que alguém com menos acertos fique acima apenas por responder rapidamente.

Armazenar:

número de acertos;
pontuação;
tempo das respostas.

==================================================
19. RANKING PARCIAL
==================================================

Inicialmente podemos utilizar:

Perguntas 1, 2 e 3
Ranking parcial

Perguntas 4, 5 e 6
Ranking parcial

Perguntas 7, 8 e 9
Ranking final

Posteriormente essa lógica poderá ser configurável.

==================================================
20. RANKING FINAL
==================================================

Ao terminar:

Mostrar publicamente somente:

TOP 10

Mostrar:

Nome
Quantidade de acertos
Pontuação

O administrador deverá conseguir visualizar a classificação completa.

==================================================
21. PAINEL DO ADMINISTRADOR DURANTE A PARTIDA
==================================================

Quero uma interface privada.

Exemplo:

PARTIDA 583214

Quiz:
Transgressões

Questão:
4 de 9

Participantes:
130

Responderam:
117

Faltam:
13

Tempo:
08 segundos

Controles possíveis:

[INICIAR]

[ENCERRAR QUESTÃO]

[PRÓXIMA]

[PAUSAR]

[ENCERRAR JOGO]

Ações destrutivas ou que possam interromper o jogo deverão pedir confirmação.

==================================================
22. TELA DE APRESENTAÇÃO
==================================================

A tela compartilhada pelo Microsoft Teams deverá ser separada do painel administrativo.

Conceitualmente:

/admin/partida

Painel privado.

E:

/apresentacao

Tela destinada ao compartilhamento.

Não quero compartilhar controles administrativos.

A apresentação deverá funcionar bem principalmente em:

1920 x 1080

e adaptar-se a outros formatos comuns.

==================================================
23. BANCO DE DADOS
==================================================

O banco será inicialmente:

MySQL da HostGator.

Planeje adequadamente tabelas, relacionamentos, chaves e índices.

Provavelmente teremos entidades equivalentes a:

administradores
quizzes
perguntas
alternativas
partidas
participantes
respostas

Esses nomes não são obrigatórios se existir modelagem melhor.

Cada resposta deverá registrar informações suficientes para integridade e relatórios.

Exemplo:

ID da partida
ID da pergunta
ID do participante
Alternativa selecionada
Momento da resposta
Tempo utilizado
Pontuação obtida

Evite redundância desnecessária.

==================================================
24. HISTÓRICO DE PARTIDAS
==================================================

Quero:

HISTÓRICO

Exemplo:

27/09/2026
Transgressões
130 participantes

[VER RESULTADOS]

03/10/2026
Outro treinamento
97 participantes

[VER RESULTADOS]

Resultados antigos não devem ser apagados quando um novo jogo for iniciado.

==================================================
25. RELATÓRIO GERAL
==================================================

Após cada partida quero gerar relatório geral.

Deverá conter pelo menos:

Nome
Usuário Copel
Quantidade de acertos
Quantidade de erros
Pontuação
Posição

Também quero análise por pergunta:

Quantidade de participantes
Quantidade que respondeu
Quantidade que não respondeu
Quantidade que marcou A
Quantidade que marcou B
Quantidade que marcou C
Quantidade que marcou D
Alternativa correta
Percentual de acerto
Tempo médio de resposta

==================================================
26. RELATÓRIO INDIVIDUAL
==================================================

Quero pesquisar um participante.

Relatório:

Nome
Usuário Copel
Quiz
Data
Quantidade de acertos
Quantidade de erros
Pontuação
Posição

Detalhamento:

Questão
Resposta selecionada
Resposta correta
Acertou/errou
Tempo
Pontos

Para todas as questões.

==================================================
27. EXPORTAÇÕES
==================================================

Quero inicialmente:

RELATÓRIO GERAL

Formato:

Excel .xlsx

RELATÓRIO INDIVIDUAL

Formato:

PDF

Opções:

[BAIXAR RELATÓRIO GERAL]

[BAIXAR INDIVIDUAL]

[BAIXAR TODOS OS INDIVIDUAIS]

"Baixar todos" poderá gerar:

Relatorios_Quiz.zip

com os PDFs individuais.

Utilizar nomes de arquivos sanitizados.

==================================================
28. DASHBOARD
==================================================

Estrutura desejada:

PAINEL ADMINISTRATIVO

Dashboard

Meus Quizzes
    Criar Quiz
    Editar Quiz
    Excluir Quiz

Partidas
    Criar Partida
    Partida ao Vivo
    Histórico

Participantes

Relatórios
    Geral
    Por Pergunta
    Individual
    Exportações

==================================================
29. IDENTIDADE VISUAL INICIAL
==================================================

A primeira implementação será relacionada à:

Copel Distribuição.

Enviarei imagens de referência visual depois deste prompt.

Utilizar inicialmente como referência:

laranja;
branco;
cinza claro;
cinza escuro.

O design deverá ser:

moderno;
corporativo;
limpo;
responsivo;
profissional;
dinâmico;
adequado para treinamento.

Pode utilizar elementos discretos relacionados a:

energia;
rede elétrica de distribuição;
conexões;
tecnologia.

Não copiar identidade visual do Kahoot.

A inspiração é somente na dinâmica do jogo ao vivo.

==================================================
30. RESPONSIVIDADE
==================================================

Prioridades:

Participante:
mobile first

Administrador:
desktop

Apresentação:
desktop / Microsoft Teams

O participante deverá jogar confortavelmente em:

Android
iPhone

==================================================
31. ACESSIBILIDADE
==================================================

Não depender exclusivamente de cores.

Sempre mostrar:

A
B
C
D

Utilizar:

contraste adequado;
botões grandes;
texto legível;
estados claros;
feedback visual compreensível.

==================================================
32. SEGURANÇA
==================================================

O sistema deverá possuir:

Login administrativo

Hash seguro de senhas administrativas

Sessões seguras

Rotas administrativas protegidas

Validação no backend

Prepared statements

Proteção contra SQL Injection

Escape de conteúdo para evitar XSS

Proteção CSRF quando aplicável

Limitação de tentativas de login, se viável

Nunca solicitar senha corporativa Copel

Nunca armazenar senha corporativa Copel

Credenciais do banco fora de arquivos públicos

Configuração segura de produção

==================================================
33. PRIVACIDADE
==================================================

O sistema armazenará inicialmente:

Nome
Usuário Copel
Respostas
Resultados

Portanto:

não expor dados completos publicamente;

somente administrador acessar relatórios completos;

ranking público mostrar somente informações necessárias;

coletar somente dados necessários.

Como haverá dados relacionados a colaboradores, considere privacidade e proteção de dados desde o desenvolvimento.

==================================================
34. INTEGRIDADE DAS RESPOSTAS
==================================================

Aproximadamente 130 participantes poderão responder quase simultaneamente.

Cada participante poderá responder somente uma vez cada pergunta.

O backend deverá impedir duplicidade mesmo que:

o participante clique várias vezes;

a requisição seja reenviada;

a página seja atualizada;

o frontend seja manipulado.

Não depender apenas de JavaScript.

Criar proteção também no banco quando apropriado.

==================================================
35. CONTROLE DO TEMPO
==================================================

Não confiar exclusivamente no cronômetro do celular.

O servidor deverá possuir referência oficial dos horários.

Os clientes podem exibir cronômetros localmente para fluidez.

O backend deverá validar se a resposta chegou dentro da janela permitida.

==================================================
36. QUEDAS DE CONEXÃO
==================================================

Planejar situações como:

perda de internet;
atualização da página;
fechamento do navegador;
reabertura;
reconexão.

Se possível, permitir que o participante retorne à partida sem criar nova participação.

Explique a estratégia antes de implementar.

==================================================
37. ESTADOS DA PARTIDA
==================================================

Planejar estados claros.

Exemplos:

aguardando participantes
leitura
respondendo
resultado
ranking
pausado
finalizado

Não permitir ações incompatíveis.

Exemplo:

participante não pode responder durante "leitura".

==================================================
38. DESEMPENHO
==================================================

Projetar pensando inicialmente em:

aproximadamente 130 participantes simultâneos.

Evitar:

consultas excessivas ao MySQL;

polling a cada fração de segundo;

consultas pesadas durante respostas;

recalcular relatórios completos a cada resposta;

conexões desnecessariamente persistentes.

Utilizar índices adequados.

Explique decisões importantes de desempenho.

==================================================
39. TESTES
==================================================

Quero realizar testes progressivos.

Exemplo:

1 participante
2 participantes
5 participantes
20 participantes
50 participantes

Posteriormente simular aproximadamente:

130 participantes.

Testar:

entrada simultânea;
sala de espera;
sincronização;
perguntas;
respostas simultâneas;
cronômetro;
encerramento automático;
pontuação;
ranking;
banco;
relatórios.

Não considerar pronto para produção sem teste de carga.

==================================================
40. AMBIENTES
==================================================

Desenvolver primeiro:

LOCALMENTE NO WINDOWS.

Depois:

PUBLICAR NA HOSTGATOR.

Não começar modificando produção.

Manter separação conceitual entre:

desenvolvimento local;

GitHub;

produção HostGator.

==================================================
41. BACKUPS
==================================================

Antes de alterações importantes em produção:

orientar backup dos arquivos;

orientar backup do banco.

Ensinar posteriormente como realizar isso pelo cPanel/HostGator.

GitHub não substitui backup do banco.

==================================================
42. EXPERIÊNCIA VISUAL
==================================================

Quero sensação de evento ao vivo.

Podemos utilizar:

transições suaves;
contador grande;
animações discretas;
feedback ao responder;
animação do ranking;
barras animadas.

Evitar efeitos pesados que prejudiquem:

celulares;
conexão;
desempenho.

==================================================
43. SOM
==================================================

Não é prioridade inicial.

Se futuramente adicionarmos:

efeitos;
música;
alertas;

deverá existir controle para ativar/desativar.

==================================================
44. ORDEM GERAL DE DESENVOLVIMENTO
==================================================

Não desenvolver tudo de uma vez.

Ordem conceitual:

Git/GitHub
estrutura local
primeira página
estrutura de configuração/branding
banco
login administrador
cadastro de quiz
cadastro de perguntas
criação da partida
entrada do participante
sala de espera
sincronização
pergunta
resposta
pontuação
resultado
ranking
histórico
relatórios
exportações
refinamento visual
publicação
teste de carga

Conduzir UMA ETAPA POR VEZ.

==================================================
45. SITUAÇÃO ATUAL
==================================================

Hospedagem:
HostGator Plano M

Painel:
cPanel

Sistema:
Windows

Editor:
VS Code

Conhecimento:
iniciante

Node.js diretamente no cPanel:
não identificado

SSH:
disponível

PHP:
disponível

MultiPHP:
disponível

MySQL:
disponível

phpMyAdmin:
disponível

Database Wizard:
disponível

Manage My Databases:
disponível

Remote Database Access:
disponível

Git Version Control:
disponível

Domínio:
ainda não possuo

Banco do projeto:
ainda não criado

Arquivos do projeto:
ainda não criados

Primeiro quiz:
Transgressões: causas, impactos e aprendizados

Quantidade inicial:
9 perguntas

Perguntas:
serão fornecidas posteriormente

Participantes previstos:
aproximadamente 130

Apresentação:
Microsoft Teams

Dispositivo dos participantes:
principalmente celular

==================================================
46. GIT E GITHUB
==================================================

Quero utilizar Git e GitHub desde o início.

Objetivos:

manter cópia segura do código;

possuir histórico;

conseguir retornar versões;

organizar evolução;

futuramente avaliar integração com HostGator;

utilizar o projeto posteriormente em meu portfólio.

Sou iniciante em Git/GitHub.

Ensine passo a passo.

Criar inicialmente um repositório:

PRIVADO

Nome sugerido:

quiz-copel

Antes de desenvolver funcionalidades importantes, configurar:

Git no Windows
GitHub
repositório privado
VS Code
pasta local
.gitignore
primeiro commit
primeiro push

Sempre indicar quando for um bom momento para commit.

==================================================
47. SEGURANÇA DO GITHUB
==================================================

NUNCA enviar:

senhas;
senha do banco;
tokens;
chaves;
segredos;
.env com credenciais;
dados pessoais reais;
resultados reais;
backups reais;
arquivos com informações confidenciais.

Criar:

.gitignore

Quando necessário, utilizar:

.env.example

ou equivalente sem credenciais.

Antes de pushes relacionados a:

banco;
autenticação;
APIs;
hospedagem;

verificar se nenhum segredo será enviado.

==================================================
48. FLUXO GIT
==================================================

Inicialmente:

COMPUTADOR LOCAL
↓
VS CODE
↓
GIT
↓
GITHUB PRIVADO
↓
HOSTGATOR

Não introduzir branches complexas, CI/CD ou DevOps sem necessidade.

Inicialmente utilizar fluxo simples.

Sempre diferenciar:

código local;

código no GitHub;

código publicado.

Alteração local não significa que está no GitHub.

Alteração no GitHub não significa que está em produção.

==================================================
49. GIT DA HOSTGATOR
==================================================

Meu cPanel possui:

Git Version Control.

Não configurar agora.

Primeiro quero:

desenvolvimento local;

Git;

GitHub privado;

commits;

push.

Quando chegarmos à publicação, avaliar a integração com a HostGator.

==================================================
50. COMMITS
==================================================

Não quero somente um commit gigantesco no final.

Salvar marcos importantes.

Exemplos:

estrutura inicial

layout inicial

branding configurável

login funcionando

banco conectado

cadastro de quiz

criação de partida

entrada do participante

sala de espera

respostas

ranking

relatórios

Antes de alterações arriscadas, recomendar commit do estado funcional.

Ensinar posteriormente como recuperar versão anterior caso algo quebre.

==================================================
51. README
==================================================

Manter:

README.md

O README deverá evoluir durante o projeto.

Futuramente deverá explicar:

o que é a plataforma;

funcionalidades;

arquitetura;

tecnologias;

requisitos;

como executar localmente;

estrutura;

configurações;

publicação.

Nunca colocar credenciais reais.

==================================================
52. DESENVOLVIMENTO PASSO A PASSO
==================================================

Não quero que gere todo o sistema e diga para instalar.

Quero construir junto.

Sempre que chegarmos a:

criar banco;
configurar PHP;
criar arquivo;
instalar biblioteca;
executar comando;
usar terminal;
usar Git;
usar GitHub;
configurar HostGator;
publicar;
alterar permissões;
configurar domínio;
configurar SSL;

explique:

o que estou fazendo;

por que;

onde;

como testar.

Depois espere meu retorno.

==================================================
53. PRIORIDADES
==================================================

Prioridade:

1. funcionamento correto;
2. integridade dos dados;
3. estabilidade;
4. segurança;
5. facilidade de manutenção;
6. experiência do usuário;
7. estética.

Desde o início quero uma interface organizada e agradável, mas estabilidade não deverá ser sacrificada por efeitos visuais.

==================================================
54. WHITE-LABEL E FUTURA VERSÃO DE PORTFÓLIO
==================================================

Este requisito é MUITO IMPORTANTE.

Embora a primeira implementação seja personalizada para a Copel Distribuição, quero futuramente transformar o projeto em uma versão pública e genérica para meu portfólio.

Portanto, a identidade Copel NÃO deverá ficar misturada à lógica principal do sistema.

Quero separar claramente:

1. lógica da plataforma;

2. identidade visual/branding;

3. conteúdo dos quizzes;

4. configurações;

5. assets institucionais;

6. dados.

Evite espalhar pelo código:

"Copel";

nome da empresa;

cores Copel;

caminhos de logos;

imagens institucionais;

textos corporativos.

Centralize essas informações sempre que tecnicamente apropriado.

==================================================
55. SISTEMA DE BRANDING
==================================================

Estruture a identidade visual de maneira centralizada.

Quero poder alterar futuramente:

nome da plataforma;

logo;

favicon;

cor principal;

cor secundária;

cores complementares;

imagens;

textos institucionais.

Por exemplo, conceitualmente:

brandName
logo
favicon
primaryColor
secondaryColor
accentColor

Não precisa necessariamente utilizar esses nomes.

Escolha a estrutura mais adequada para PHP/JavaScript/CSS.

O importante é evitar editar dezenas de arquivos para trocar a marca.

==================================================
56. ASSETS INSTITUCIONAIS
==================================================

Manter logos e imagens institucionais organizados separadamente.

Exemplo conceitual:

assets/
    branding/
        logo
        favicon
        backgrounds

A estrutura definitiva poderá ser diferente se houver uma solução melhor.

Não inserir logo em base64 ou duplicá-lo desnecessariamente em diversos arquivos.

==================================================
57. VERSÃO COPEL E VERSÃO DE PORTFÓLIO
==================================================

Quero futuramente conseguir transformar:

QUIZ COPEL

em algo genérico, por exemplo:

QUIZ LIVE

ou outro nome.

Na versão de portfólio, quero substituir:

logo Copel;
nome Copel;
cores específicas;
imagens;
textos institucionais;
conteúdo interno.

Por:

marca genérica;
identidade própria;
quiz demonstrativo;
dados fictícios.

A lógica principal NÃO deverá precisar ser reconstruída.

Funcionalidades como:

partidas;
perguntas;
respostas;
cronômetros;
ranking;
administrador;
relatórios;
banco;
sincronização;

devem ser independentes da marca Copel.

==================================================
58. DADOS CORPORATIVOS E PORTFÓLIO
==================================================

Nunca publicar na versão de portfólio:

dados reais de colaboradores;

usuários Copel reais;

resultados reais;

relatórios reais;

perguntas confidenciais;

documentos internos;

informações operacionais internas;

credenciais;

arquivos corporativos que não possam ser divulgados.

Para demonstração utilizar dados fictícios.

Exemplo:

Ana Silva
Carlos Souza
Mariana Santos
João Oliveira

E quizzes demonstrativos sem conteúdo interno.

==================================================
59. REPOSITÓRIO PÚBLICO FUTURO
==================================================

O primeiro repositório poderá permanecer PRIVADO durante o desenvolvimento.

Não transformar automaticamente esse repositório em público.

Quando eu decidir publicar o projeto no portfólio, quero que você me ajude a avaliar a estratégia mais segura.

Podemos, por exemplo:

sanitizar completamente o projeto;

ou criar um novo repositório específico para a versão pública.

Antes de qualquer publicação pública, revisar:

histórico Git;
arquivos;
assets;
commits;
credenciais;
nomes;
dados;
documentação;
conteúdo corporativo.

IMPORTANTE:

Apagar um segredo somente do arquivo atual pode não removê-lo do histórico Git.

Portanto, antes de tornar qualquer repositório público, realizar revisão específica do histórico.

==================================================
60. PORTFÓLIO
==================================================

Futuramente quero apresentar este projeto profissionalmente.

A versão pública poderá ser descrita conceitualmente como:

"Plataforma web de quiz corporativo em tempo real."

Principais funcionalidades demonstráveis:

criação de quizzes;

salas por código;

QR Code;

participação simultânea via celular;

sincronização ao vivo;

cronômetros;

pontuação por acerto e velocidade;

ranking;

dashboard administrativo;

histórico;

relatórios;

exportação Excel/PDF.

O portfólio deverá destacar a solução técnica desenvolvida, e não depender da marca Copel.

==================================================
61. SEPARAÇÃO ENTRE CÓDIGO E CONTEÚDO
==================================================

Sempre que possível, evitar escrever diretamente no código:

perguntas;

alternativas;

nomes de participantes;

resultados;

dados específicos do treinamento.

Esses dados deverão vir do banco/configuração apropriada.

Assim poderemos utilizar a mesma plataforma com diferentes quizzes e organizações.

==================================================
62. DADOS DE DEMONSTRAÇÃO
==================================================

Quando precisarmos testar localmente, utilizar preferencialmente dados fictícios.

Se precisarmos testar o fluxo de 130 participantes, podemos gerar participantes fictícios.

Não utilizar dados reais desnecessariamente durante desenvolvimento e testes.

==================================================
63. ESTRUTURA PREPARADA PARA FUTURA PERSONALIZAÇÃO
==================================================

Não precisamos construir agora um sistema completo de múltiplas empresas ou SaaS.

Não quero adicionar complexidade desnecessária.

Quero apenas que a arquitetura seja organizada o suficiente para permitir trocar a identidade da plataforma futuramente sem reconstruir o sistema.

Não implemente multi-tenant, múltiplas organizações ou recursos empresariais complexos sem necessidade.

==================================================
64. DOCUMENTAÇÃO DA ARQUITETURA
==================================================

Durante o projeto, documente decisões importantes.

Principalmente:

estrutura do banco;

estrutura do branding;

arquitetura de tempo real;

pontuação;

autenticação;

deploy;

Git/GitHub;

segurança.

Isso deverá facilitar manutenção futura e apresentação do projeto no meu portfólio.

==================================================
65. PRIMEIRA RESPOSTA QUE QUERO DE VOCÊ
==================================================

Depois de ler TODO este documento:

1. Faça um resumo curto mostrando que entendeu o projeto.

2. Aponte somente algum problema realmente crítico que precise ser decidido antes de começar, caso exista.

3. Não redesenhe o projeto inteiro.

4. Não gere o sistema completo.

5. Não gere centenas de linhas de código.

6. Considere Git/GitHub como nossa primeira etapa antes do desenvolvimento.

7. Comece verificando se meu Windows já possui Git instalado.

8. Se precisar que eu execute um comando para verificar isso, forneça SOMENTE o primeiro comando necessário.

9. Diga exatamente onde devo executar esse comando.

10. Espere minha resposta antes de avançar.

11. Lembre-se durante todo o projeto de que sou iniciante.

12. Não configure ainda banco, HostGator ou serviço de tempo real.

13. Não crie ainda todas as páginas.

14. Primeiro vamos garantir que Git e GitHub estejam corretamente configurados.

Vamos começar a construção da plataforma.