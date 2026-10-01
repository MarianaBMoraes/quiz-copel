/*
| Painel ao vivo da partida: mostra a fase, os números e só os botões
| que fazem sentido na fase atual. Enquanto esta tela (ou a
| apresentação) está aberta, o servidor vira as fases pelo tempo.
*/
(() => {
    const painel = document.querySelector('[data-ao-vivo]');

    if (!painel) {
        return;
    }

    const codigo = painel.dataset.code;
    const csrf = painel.dataset.csrf;

    const NOMES_FASE = {
        aguardando: 'aguardando participantes',
        leitura: 'leitura da pergunta',
        respondendo: 'participantes respondendo',
        resultado: 'mostrando o resultado',
        ranking: 'mostrando o ranking parcial',
        finalizado: 'jogo encerrado',
        pausado: 'tempo pausado',
    };

    const CONFIRMAR = {
        iniciar: 'Iniciar o quiz agora? A primeira pergunta aparece na apresentação.',
        encerrar_questao: 'Encerrar esta questão agora? Quem ainda não respondeu fica sem pontos nela.',
        finalizar: 'Encerrar o jogo agora? As perguntas que faltam não serão feitas.',
    };

    let dados = null;
    let diferencaRelogio = 0;
    let ocupado = false;
    let rankingDesenhado = null;
    let temporizador = null;

    const campo = (nome) => painel.querySelector(`[data-campo="${nome}"]`);
    const botao = (acao) => painel.querySelector(`[data-acao="${acao}"]`);
    const agora = () => Date.now() + diferencaRelogio;
    const numero = (valor) => Number(valor || 0).toLocaleString('pt-BR');

    async function pedir(endereco, opcoes = {}, limiteMs = 5000) {
        const controle = new AbortController();
        const temporizadorLimite = setTimeout(() => controle.abort(), limiteMs);

        try {
            return await fetch(endereco, { cache: 'no-store', ...opcoes, signal: controle.signal });
        } finally {
            clearTimeout(temporizadorLimite);
        }
    }

    function erro(texto) {
        campo('erro').textContent = texto;
        campo('erro').hidden = !texto;
    }

    async function buscar() {
        clearTimeout(temporizador);

        const inicio = Date.now();

        try {
            const resposta = await pedir(`/admin/api/tick.php?code=${codigo}&completo=1`);
            const tipo = resposta.headers.get('content-type') || '';

            if (!tipo.includes('application/json')) {
                throw new Error('sessao');
            }

            const novo = await resposta.json();

            if (!novo.ok) {
                throw new Error(novo.motivo || 'erro');
            }

            diferencaRelogio = novo.agora_ms - (inicio + Date.now()) / 2;
            dados = novo;

            if (campo('erro').dataset.origem === 'conexao') {
                erro('');
            }

            desenhar();
        } catch (falha) {
            campo('erro').dataset.origem = 'conexao';
            erro(falha.message === 'sessao'
                ? 'Sua sessão expirou. Recarregue a página e entre de novo.'
                : 'Sem conexão com o servidor. Tentando de novo...');
        } finally {
            temporizador = setTimeout(buscar, 1500);
        }
    }

    function faseLocal() {
        if (dados.status === 'leitura' && dados.pergunta && agora() >= dados.fim_ms) {
            return 'respondendo';
        }

        return dados.status;
    }

    function mostrarBotao(acao, visivel, principal) {
        const elemento = botao(acao);

        elemento.hidden = !visivel;
        elemento.disabled = ocupado;
        elemento.classList.toggle('admin-button-primary', principal);
        elemento.classList.toggle('admin-button-secondary', !principal);
    }

    function desenhar() {
        if (!dados) {
            return;
        }

        const fase = faseLocal();
        const pergunta = dados.pergunta;
        const ultima = pergunta && pergunta.indice === pergunta.total;
        const pedeRanking = pergunta && pergunta.indice % 3 === 0 && !ultima;

        campo('fase').textContent = NOMES_FASE[fase] || fase;
        campo('pergunta').textContent = pergunta
            ? `${pergunta.indice} de ${pergunta.total}`
            : `0 de ${dados.total_perguntas}`;
        campo('participantes').textContent = numero(dados.participantes);
        const pausadoRespondendo = fase === 'pausado' && dados.status_pausado === 'respondendo';

        campo('respondidos').textContent = ['respondendo', 'resultado'].includes(fase) || pausadoRespondendo
            ? `${numero(dados.respondidos)} / ${numero(dados.participantes)}`
            : '-';

        mostrarBotao('iniciar', fase === 'aguardando', true);
        botao('iniciar').disabled = ocupado || dados.total_perguntas === 0;

        mostrarBotao('pausar', ['leitura', 'respondendo'].includes(fase), false);
        mostrarBotao('continuar', fase === 'pausado', true);
        mostrarBotao('encerrar_questao', ['leitura', 'respondendo', 'pausado'].includes(fase), false);
        mostrarBotao('mostrar_ranking', fase === 'resultado' && !ultima, pedeRanking);
        mostrarBotao('proxima', ['resultado', 'ranking'].includes(fase), !pedeRanking || fase === 'ranking');
        botao('proxima').textContent = ultima ? 'Ver ranking final' : 'Próxima pergunta';
        mostrarBotao('finalizar', fase !== 'finalizado', false);
        botao('finalizar').classList.add('admin-button-danger');

        atualizarTempo();
        desenharRanking();
    }

    function atualizarTempo() {
        if (!dados) {
            return;
        }

        const fase = faseLocal();
        let fim = null;

        if (fase === 'pausado' && dados.status_pausado) {
            // Tempo parado: mostra o que faltava no instante da pausa.
            const segundos = Math.max(0, Math.ceil((dados.fim_ms - dados.pausada_ms) / 1000));
            const leitura = dados.status_pausado === 'leitura' ? 'leitura ' : '';

            campo('tempo').textContent = `${leitura}${segundos} s (pausado)`;
            return;
        }

        if (dados.status === 'leitura' && fase === 'leitura') {
            fim = dados.fim_ms;
        } else if (dados.status === 'leitura' && fase === 'respondendo' && dados.pergunta) {
            fim = dados.fim_ms + dados.pergunta.tempo_resposta * 1000;
        } else if (dados.status === 'respondendo') {
            fim = dados.fim_ms;
        }

        if (fim === null) {
            campo('tempo').textContent = '-';
            return;
        }

        const segundos = Math.max(0, Math.ceil((fim - agora()) / 1000));

        campo('tempo').textContent = fase === 'leitura' ? `leitura ${segundos} s` : `${segundos} s`;
    }

    function desenharRanking() {
        const ranking = dados.ranking || [];
        const assinatura = JSON.stringify(ranking);

        campo('ranking-bloco').hidden = dados.status === 'aguardando';

        if (assinatura === rankingDesenhado) {
            return;
        }

        rankingDesenhado = assinatura;

        const corpo = campo('ranking');
        corpo.replaceChildren();

        ranking.forEach((linha) => {
            const tr = document.createElement('tr');

            [
                `${linha.posicao}º`,
                linha.nome,
                linha.usuario,
                String(linha.acertos),
                String(linha.respondidas),
                numero(linha.pontos),
            ].forEach((valor) => {
                const td = document.createElement('td');
                td.textContent = valor;
                tr.append(td);
            });

            corpo.append(tr);
        });
    }

    async function executar(acao) {
        if (ocupado) {
            return;
        }

        if (CONFIRMAR[acao] && !window.confirm(CONFIRMAR[acao])) {
            return;
        }

        ocupado = true;
        desenhar();
        campo('erro').dataset.origem = 'acao';
        erro('');

        try {
            const resposta = await pedir('/admin/api/acao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: codigo, acao, csrf_token: csrf }),
            });

            const resultado = await resposta.json();

            if (!resultado.ok) {
                erro(resultado.motivo === 'acao_invalida'
                    ? 'Essa ação não vale mais nesta fase. A tela foi atualizada.'
                    : 'Não foi possível executar. Recarregue a página e tente de novo.');
            }
        } catch (falha) {
            erro('Sem conexão com o servidor. Confira a internet e tente de novo.');
        } finally {
            ocupado = false;
            buscar();
        }
    }

    // Link pra colar no chat da reunião: quem toca já cai na tela de nome e usuário.
    const botaoCopiar = painel.querySelector('[data-copiar-link]');

    botaoCopiar.addEventListener('click', async () => {
        const link = `${window.location.origin}/participante.php?code=${codigo}`;

        try {
            await navigator.clipboard.writeText(link);
            botaoCopiar.textContent = 'Link copiado';
            setTimeout(() => { botaoCopiar.textContent = 'Copiar link de entrada'; }, 2500);
        } catch (falha) {
            window.prompt('Copie o link de entrada:', link);
        }
    });

    painel.querySelectorAll('[data-acao]').forEach((elemento) => {
        elemento.addEventListener('click', () => executar(elemento.dataset.acao));
    });

    buscar();
    setInterval(() => {
        if (dados) {
            desenhar();
        }
    }, 250);
})();
