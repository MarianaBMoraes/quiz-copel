/*
| Tela do participante. Acompanha a partida lendo /estado/<codigo>.json
| (arquivo comum, sem PHP) e só chama o servidor pra responder e pra
| saber o próprio resultado. Os botões liberam pelo relógio do servidor,
| sem esperar a próxima consulta.
*/
(() => {
    const raiz = document.querySelector('[data-jogo]');

    if (!raiz) {
        return;
    }

    const codigo = raiz.dataset.code;
    const INTERVALO_MS = 2000;

    let diferencaRelogio = 0;
    let estado = null;
    let eu = null;
    let chaveEu = null;
    let falhas = 0;
    let buscando = false;
    let temporizador = null;
    let enviando = false;
    let telaAtual = null;

    const respondidas = {};
    const encerradas = {};
    const planoB = {};

    const agora = () => Date.now() + diferencaRelogio;
    const esperar = (ms) => new Promise((resolver) => setTimeout(resolver, ms));

    // Toda chamada desiste em 5 s: rede que trava não pode congelar a tela.
    async function pedir(endereco, opcoes = {}, limiteMs = 5000) {
        const controle = new AbortController();
        const temporizadorLimite = setTimeout(() => controle.abort(), limiteMs);

        try {
            return await fetch(endereco, { cache: 'no-store', ...opcoes, signal: controle.signal });
        } finally {
            clearTimeout(temporizadorLimite);
        }
    }

    function preencher(nome, texto) {
        raiz.querySelectorAll(`[data-campo="${nome}"]`).forEach((elemento) => {
            elemento.textContent = texto;
        });
    }

    function mostrarTela(nome) {
        if (telaAtual === nome) {
            return;
        }

        telaAtual = nome;

        raiz.querySelectorAll('[data-tela]').forEach((elemento) => {
            elemento.hidden = elemento.dataset.tela !== nome;
        });
    }

    function definirCarga(valor) {
        const carga = Math.max(0, Math.min(1, valor));

        raiz.querySelectorAll('.jogo-linha').forEach((elemento) => {
            elemento.style.setProperty('--carga', carga.toFixed(3));
        });
    }

    function mostrarConexao(ok) {
        raiz.querySelector('[data-campo="conexao"]').hidden = ok;
    }

    // Três medições; vale a de ida e volta mais rápida, que erra menos.
    async function sincronizarRelogio() {
        let melhor = null;

        for (let medicao = 0; medicao < 3; medicao += 1) {
            try {
                const inicio = Date.now();
                const resposta = await pedir('/api/hora.php');
                const dados = await resposta.json();
                const fim = Date.now();

                if (!melhor || fim - inicio < melhor.ida) {
                    melhor = { ida: fim - inicio, diferenca: dados.agora_ms - (inicio + fim) / 2 };
                }
            } catch (erro) {
                // Tenta a próxima medição.
            }
        }

        if (melhor) {
            diferencaRelogio = melhor.diferenca;
        } else {
            setTimeout(sincronizarRelogio, 5000);
        }
    }

    async function buscarEstado() {
        if (buscando) {
            return;
        }

        buscando = true;
        clearTimeout(temporizador);

        try {
            const resposta = await pedir(`/estado/${codigo}.json?t=${Date.now()}`);

            if (!resposta.ok) {
                throw new Error(String(resposta.status));
            }

            receberEstado(await resposta.json());
            falhas = 0;
            mostrarConexao(true);
        } catch (erro) {
            falhas += 1;

            if (falhas >= 3) {
                mostrarConexao(false);
            }
        } finally {
            buscando = false;
            temporizador = setTimeout(buscarEstado, INTERVALO_MS + Math.random() * 500);
        }
    }

    function receberEstado(novo) {
        estado = novo;

        const chave = `${novo.status}:${novo.pergunta ? novo.pergunta.id : ''}`;
        const revelado = ['resultado', 'ranking', 'finalizado'].includes(novo.status);

        if (revelado && chave !== chaveEu) {
            chaveEu = chave;

            // Espalha as consultas pra todos os celulares não baterem no mesmo segundo.
            setTimeout(buscarEu, Math.random() * 1500);
        }

        desenhar();
    }

    async function buscarEu() {
        try {
            const resposta = await pedir(`/api/eu.php?code=${codigo}`);

            if (resposta.status === 401) {
                window.location.href = `/participante.php?code=${codigo}`;
                return;
            }

            const dados = await resposta.json();

            if (!dados.ok) {
                return;
            }

            eu = dados;

            if (dados.atual && dados.atual.respondida) {
                respondidas[dados.atual.pergunta_id] = dados.atual.letra;
            }

            desenhar();
        } catch (erro) {
            // Tenta de novo quando o próximo estado chegar.
            chaveEu = null;
        }
    }

    /*
    | Janela de resposta em horário do servidor. Na leitura ela já é
    | conhecida (começa quando a leitura acaba), então o celular vira
    | sozinho no instante certo.
    */
    function janelaResposta() {
        if (!estado || !estado.pergunta) {
            return null;
        }

        if (estado.status === 'leitura' && estado.fim_ms) {
            return {
                inicio: estado.fim_ms,
                fim: estado.fim_ms + estado.pergunta.tempo_resposta * 1000,
            };
        }

        if (estado.status === 'respondendo') {
            return { inicio: estado.inicio_ms, fim: estado.fim_ms };
        }

        return null;
    }

    function faseLocal() {
        if (['leitura', 'respondendo'].includes(estado.status) && !estado.pergunta) {
            return 'aguardando';
        }

        const janela = janelaResposta();

        if (!janela) {
            return estado.status;
        }

        const instante = agora();

        if (estado.status === 'leitura' && instante < janela.inicio) {
            return 'leitura';
        }

        return instante < janela.fim ? 'respondendo' : 'esgotado';
    }

    function desenhar() {
        if (!estado) {
            return;
        }

        const pergunta = estado.pergunta;

        if (pergunta) {
            preencher('numero', `Pergunta ${pergunta.indice} de ${pergunta.total}`);
        }

        preencher('participantes', String(estado.participantes));

        const fase = faseLocal();

        if (fase === 'aguardando' || fase === 'pausado') {
            mostrarTela('aguardando');
        } else if (fase === 'leitura') {
            mostrarTela('leitura');
        } else if (fase === 'respondendo' || fase === 'esgotado') {
            if (respondidas[pergunta.id]) {
                mostrarRespondida(respondidas[pergunta.id]);
            } else if (fase === 'esgotado' || encerradas[pergunta.id]) {
                mostrarTela('esgotado');
            } else {
                mostrarTela('responder');
                habilitarBotoes(!enviando);
            }
        } else if (fase === 'resultado') {
            mostrarResultado();
        } else if (fase === 'ranking' || fase === 'finalizado') {
            mostrarRanking(fase);
        }

        atualizarRelogio();
        verificarPlanoB();
    }

    /*
    | Se a pergunta já venceu faz um tempo e o estado não mudou, a tela do
    | administrador provavelmente parou. Cada celular espera um tempo
    | diferente e pede pro servidor encerrar a pergunta vencida.
    */
    function verificarPlanoB() {
        const janela = janelaResposta();

        if (!janela || !estado.pergunta) {
            return;
        }

        const id = estado.pergunta.id;
        const plano = planoB[id] || (planoB[id] = { atraso: 4000 + Math.random() * 6000, ultimo: 0 });
        const instante = agora();

        if (instante > janela.fim + plano.atraso && Date.now() - plano.ultimo > 8000) {
            plano.ultimo = Date.now();
            pedir(`/api/avancar.php?code=${codigo}`).then(() => buscarEstado()).catch(() => {});
        }
    }

    function atualizarRelogio() {
        const janela = janelaResposta();

        if (!janela) {
            return;
        }

        const instante = agora();

        if (telaAtual === 'leitura') {
            const falta = Math.max(0, janela.inicio - instante);
            const duracao = Math.max(1, janela.inicio - estado.inicio_ms);

            preencher('contagem', String(Math.ceil(falta / 1000)));
            definirCarga(1 - falta / duracao);
        } else if (telaAtual === 'responder') {
            const falta = Math.max(0, janela.fim - instante);
            const duracao = Math.max(1, janela.fim - janela.inicio);

            preencher('contagem', String(Math.ceil(falta / 1000)));
            definirCarga(falta / duracao);
        }
    }

    function habilitarBotoes(habilitar) {
        raiz.querySelectorAll('[data-letra]').forEach((botao) => {
            botao.disabled = !habilitar;
        });
    }

    function mostrarRespondida(letra) {
        const quadro = raiz.querySelector('.jogo-letra-escolhida');

        quadro.textContent = letra;
        quadro.className = `jogo-letra-escolhida letra-${letra.toLowerCase()}`;

        mostrarTela('respondida');
    }

    function mostrarErroEnvio(mensagem) {
        const elemento = raiz.querySelector('[data-campo="erro-envio"]');

        elemento.textContent = mensagem || '';
        elemento.hidden = !mensagem;
    }

    function euDaPerguntaAtual() {
        return eu
            && ['resultado', 'ranking', 'finalizado'].includes(eu.status)
            && eu.atual
            && estado.pergunta
            && eu.atual.pergunta_id === estado.pergunta.id;
    }

    function preencherPlacar() {
        const placar = eu && eu.placar;

        preencher('acertos', placar ? String(placar.acertos) : '0');
        preencher('pontos', placar ? placar.pontos.toLocaleString('pt-BR') : '0');
    }

    function mostrarResultado() {
        mostrarTela('resultado');

        const selo = raiz.querySelector('[data-campo="selo"]');

        if (!euDaPerguntaAtual()) {
            selo.className = 'jogo-selo neutro';
            selo.textContent = 'Resultado';
            preencher('resultado-titulo', 'Confira na tela');
            preencher('resultado-texto', 'Buscando o seu resultado...');
            return;
        }

        const atual = eu.atual;
        const certa = atual.letra_correta ? `A resposta certa era ${atual.letra_correta}.` : '';

        if (atual.respondida && atual.correta) {
            selo.className = 'jogo-selo certo';
            selo.textContent = 'Você acertou';
            preencher('resultado-titulo', `+${atual.pontos.toLocaleString('pt-BR')} pontos`);
            preencher('resultado-texto', `Você marcou ${atual.letra}.`);
        } else if (atual.respondida) {
            selo.className = 'jogo-selo errado';
            selo.textContent = 'Não foi dessa vez';
            preencher('resultado-titulo', `Você marcou ${atual.letra}`);
            preencher('resultado-texto', certa);
        } else {
            selo.className = 'jogo-selo neutro';
            selo.textContent = 'Sem resposta';
            preencher('resultado-titulo', 'Você não respondeu');
            preencher('resultado-texto', certa);
        }

        preencherPlacar();
    }

    function mostrarRanking(fase) {
        mostrarTela('ranking');

        preencher('ranking-rotulo', fase === 'finalizado' ? 'Fim do quiz. Obrigado por participar!' : 'Ranking parcial');

        const placar = eu && eu.status === fase ? eu.placar : null;

        preencher('posicao', placar && placar.posicao ? `${placar.posicao}º` : '-');
        preencher('posicao-texto', `de ${estado.participantes} participantes`);
        preencherPlacar();
    }

    async function responder(letra, botao) {
        if (enviando || !estado || !estado.pergunta) {
            return;
        }

        const perguntaId = estado.pergunta.id;

        if (respondidas[perguntaId]) {
            return;
        }

        enviando = true;
        botao.classList.add('escolhida');
        habilitarBotoes(false);
        mostrarErroEnvio('');

        if (navigator.vibrate) {
            navigator.vibrate(30);
        }

        let avisosCedo = 0;

        for (let tentativa = 1; tentativa <= 3; tentativa += 1) {
            try {
                const resposta = await pedir('/api/responder.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code: codigo, pergunta_id: perguntaId, letra }),
                });

                const dados = await resposta.json();

                if (dados.motivo === 'cedo' && avisosCedo < 3) {
                    // O relógio do celular estava adiantado: guarda a resposta e envia quando abrir.
                    avisosCedo += 1;
                    diferencaRelogio -= dados.espera_ms;
                    await esperar(dados.espera_ms);
                    tentativa -= 1;
                    continue;
                }

                if (dados.ok) {
                    respondidas[perguntaId] = dados.letra;
                } else if (dados.motivo === 'fora_do_tempo') {
                    encerradas[perguntaId] = true;
                } else if (dados.motivo === 'sem_participacao') {
                    window.location.href = `/participante.php?code=${codigo}`;
                    return;
                } else {
                    mostrarErroEnvio('Não foi possível registrar. Toque de novo na sua resposta.');
                }

                break;
            } catch (erro) {
                // Reenviar é seguro: o servidor guarda só a primeira resposta.
                if (tentativa === 3) {
                    mostrarErroEnvio('Sem conexão. Toque de novo na sua resposta.');
                } else {
                    await esperar(600 * tentativa);
                }
            }
        }

        enviando = false;
        botao.classList.remove('escolhida');
        desenhar();
    }

    raiz.querySelectorAll('[data-letra]').forEach((botao) => {
        botao.addEventListener('click', () => responder(botao.dataset.letra, botao));
    });

    // Celular que bloqueou a tela volta atualizado na hora.
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            sincronizarRelogio();
            buscarEstado();
        }
    });

    sincronizarRelogio().then(() => {
        buscarEstado();
        buscarEu();
    });

    setInterval(sincronizarRelogio, 60000);

    setInterval(() => {
        if (estado) {
            desenhar();
        }
    }, 200);
})();
