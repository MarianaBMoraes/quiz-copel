/*
| Tela da apresentação (Teams). Consulta o painel a cada segundo e
| desenha a cena da fase atual. A virada da leitura pra resposta é
| feita pelo relógio, no mesmo instante em que os celulares liberam.
*/
(() => {
    const palco = document.querySelector('[data-apresentacao]');

    if (!palco) {
        return;
    }

    const codigo = palco.dataset.code;
    const POSTES = 11;

    let diferencaRelogio = 0;
    let dados = null;
    let cenaChave = null;
    let ultimoContador = null;
    let falhas = 0;

    const campo = (nome) => palco.querySelector(`[data-campo="${nome}"]`);
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

    function desenharQr() {
        const endereco = `${window.location.origin}/participante.php?code=${codigo}`;
        const qr = qrcode(0, 'M');

        qr.addData(endereco);
        qr.make();

        campo('qr').innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });

        // Endereço comprido demais pra digitar: o link vai pelo chat da reunião.
        if (window.location.host.length > 30) {
            campo('passo-endereco').textContent = 'ou toque no link enviado no chat da reunião';
        } else {
            campo('endereco').textContent = window.location.host;
        }
    }

    /*
    | Texto longo: procura o maior tamanho de letra que cabe na tela, sem
    | estreitar as colunas e sem cortar nada. Só a letra muda (--texto).
    */
    function caber() {
        const corpo = palco.querySelector('.palco-corpo');
        const titulo = palco.querySelector('.espera-titulo');
        const cena = palco.querySelector('[data-cena="pergunta"]');

        let ajuste = 1;
        titulo.style.setProperty('--ajuste', '1');

        while (titulo.offsetParent && titulo.scrollWidth > titulo.clientWidth + 1 && ajuste > 0.4) {
            ajuste -= 0.05;
            titulo.style.setProperty('--ajuste', ajuste.toFixed(2));
        }

        cena.style.setProperty('--texto', '1');

        if (cena.hidden) {
            return;
        }

        // Limite = fim da área útil. Mede pela posição de layout (offsetTop
        // somado até a área útil), que não muda com as animações de entrada.
        const fundo = (elemento) => {
            let altura = elemento.offsetHeight;
            let atual = elemento;

            while (atual && atual !== corpo) {
                altura += atual.offsetTop;
                atual = atual.offsetParent;
            }

            return atual === corpo ? altura : 0;
        };

        const transborda = () => {
            const limite = corpo.clientHeight - parseFloat(getComputedStyle(corpo).paddingBottom) + 1;

            return [...cena.querySelectorAll('.pergunta-enunciado, .pergunta-aviso, .alternativa, .aprendizado, .resultado-certa')]
                .some((elemento) => elemento.offsetParent && (
                    elemento.scrollHeight > elemento.clientHeight + 1
                    || elemento.scrollWidth > elemento.clientWidth + 1
                    || fundo(elemento) > limite
                ));
        };

        if (!transborda()) {
            return;
        }

        let cabe = 0.2;
        let naoCabe = 1;

        for (let volta = 0; volta < 9; volta += 1) {
            const meio = (cabe + naoCabe) / 2;
            cena.style.setProperty('--texto', meio.toFixed(3));

            if (transborda()) {
                naoCabe = meio;
            } else {
                cabe = meio;
            }
        }

        cena.style.setProperty('--texto', cabe.toFixed(3));
    }

    function aviso(texto) {
        campo('aviso').textContent = texto;
        campo('aviso').hidden = !texto;
    }

    async function buscar() {
        const inicio = Date.now();

        try {
            const resposta = await pedir(`/admin/api/tick.php?code=${codigo}`);
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
            falhas = 0;
            aviso('');
            desenhar();
        } catch (erro) {
            falhas += 1;

            if (erro.message === 'sessao') {
                aviso('Sessão expirada. Entre de novo no painel e recarregue esta tela.');
            } else if (falhas >= 3) {
                aviso('Sem conexão com o servidor. Tentando de novo...');
            }
        } finally {
            setTimeout(buscar, 1000);
        }
    }

    // Pausa: a cena continua a mesma, com o relógio parado no instante da pausa.
    const pausado = () => dados && dados.status === 'pausado' && dados.status_pausado;
    const statusBase = () => (pausado() ? dados.status_pausado : dados.status);
    const instanteJogo = () => (pausado() ? dados.pausada_ms : agora());

    function janelaResposta() {
        if (!dados || !dados.pergunta) {
            return null;
        }

        if (statusBase() === 'leitura' && dados.fim_ms) {
            return {
                inicio: dados.fim_ms,
                fim: dados.fim_ms + dados.pergunta.tempo_resposta * 1000,
            };
        }

        if (statusBase() === 'respondendo') {
            return { inicio: dados.inicio_ms, fim: dados.fim_ms };
        }

        return null;
    }

    function faseLocal() {
        if (['leitura', 'respondendo', 'resultado'].includes(statusBase()) && !dados.pergunta) {
            return 'aguardando';
        }

        if (statusBase() === 'leitura' && instanteJogo() >= dados.fim_ms) {
            return 'respondendo';
        }

        return statusBase();
    }

    function mostrarCena(nome) {
        palco.querySelectorAll('[data-cena]').forEach((cena) => {
            cena.hidden = cena.dataset.cena !== nome;
        });
    }

    function desenhar() {
        if (!dados) {
            return;
        }

        const fase = faseLocal();
        const chave = `${fase}:${dados.pergunta ? dados.pergunta.id : ''}`;
        const mudou = chave !== cenaChave;

        cenaChave = chave;
        campo('pausa').hidden = !pausado();


        campo('codigo-topo').hidden = ['aguardando', 'finalizado'].includes(fase);

        if (fase === 'aguardando' || fase === 'pausado') {
            cenaEspera(mudou);
        } else if (['leitura', 'respondendo', 'resultado'].includes(fase)) {
            cenaPergunta(fase, mudou);
        } else {
            cenaRanking(fase, mudou);
        }

        if (mudou) {
            caber();
        }

        atualizarRelogio();
    }

    function rodape({ contador = null, linha = null }) {
        campo('contador').hidden = contador === null;
        campo('linha').hidden = linha === null;

        if (linha) {
            campo('linha').dataset.modo = linha;
        }

        if (contador) {
            const elemento = campo('contador-numero');

            if (contador.numero !== ultimoContador) {
                elemento.classList.remove('subiu');
                void elemento.offsetWidth;
                elemento.classList.add('subiu');
                ultimoContador = contador.numero;
            }

            elemento.textContent = numero(contador.numero);
            campo('contador-texto').textContent = contador.texto;
        }

        if (!linha) {
            campo('relogio').textContent = '';
        }
    }

    function cenaEspera(mudou) {
        if (mudou) {
            mostrarCena('aguardando');
        }

        rodape({
            contador: {
                numero: dados.participantes,
                texto: dados.participantes === 1 ? 'participante na sala' : 'participantes na sala',
            },
        });
    }

    function cenaPergunta(fase, mudou) {
        const pergunta = dados.pergunta;
        const cena = palco.querySelector('[data-cena="pergunta"]');

        if (mudou) {
            mostrarCena('pergunta');
            cena.dataset.fase = fase;

            campo('numero').textContent = fase === 'resultado'
                ? `Pergunta ${pergunta.indice} de ${pergunta.total} · Resultado`
                : `Pergunta ${pergunta.indice} de ${pergunta.total}`;

            campo('enunciado').textContent = pergunta.enunciado;
            campo('aviso-leitura').hidden = fase !== 'leitura';
            campo('respondidos-bloco').hidden = fase !== 'respondendo';
            campo('alternativas').hidden = fase === 'leitura';

            const explicacao = (pergunta.explicacao || '').trim();
            const certa = (pergunta.alternativas || []).find((alternativa) => alternativa.correta);

            cena.dataset.aprendizado = explicacao ? '1' : '0';
            campo('aprendizado').hidden = !(fase === 'resultado' && explicacao);
            campo('explicacao').textContent = explicacao;
            campo('certa').hidden = !(fase === 'resultado' && certa);
            campo('certa-letra').textContent = certa ? `${certa.letra}.` : '';
            campo('certa-texto').textContent = certa ? certa.texto : '';

            desenharAlternativas(fase);
        }

        campo('respondidos').textContent = numero(dados.respondidos);
        campo('participantes').textContent = numero(dados.participantes);

        if (fase === 'resultado') {
            const certa = pergunta.alternativas.find((alternativa) => alternativa.correta);

            rodape({
                contador: {
                    numero: certa ? certa.total : 0,
                    texto: `de ${numero(dados.participantes)} acertaram`,
                },
            });
        } else {
            rodape({ linha: fase === 'leitura' ? 'carregando' : 'descarregando' });
        }
    }

    function desenharAlternativas(fase) {
        const lista = campo('alternativas');
        const alternativas = dados.pergunta.alternativas || [];
        const maior = Math.max(1, ...alternativas.map((alternativa) => alternativa.total || 0));

        lista.replaceChildren();

        if (fase === 'leitura') {
            return;
        }

        alternativas.forEach((alternativa) => {
            const item = document.createElement('li');
            item.className = `alternativa letra-${alternativa.letra.toLowerCase()}`;

            if (fase === 'resultado') {
                const barra = document.createElement('div');
                barra.className = 'alternativa-barra';
                item.append(barra);

                requestAnimationFrame(() => requestAnimationFrame(() => {
                    barra.style.setProperty('--proporcao', ((alternativa.total || 0) / maior).toFixed(3));
                }));

                if (alternativa.correta) {
                    item.classList.add('correta');
                }
            }

            const letra = document.createElement('span');
            letra.className = 'alternativa-letra';
            letra.textContent = alternativa.letra;

            const texto = document.createElement('span');
            texto.className = 'alternativa-texto';
            texto.textContent = alternativa.texto;

            item.append(letra, texto);

            if (fase === 'resultado') {
                const selo = document.createElement('span');
                selo.className = 'alternativa-selo';
                selo.textContent = 'Correta';

                const total = document.createElement('span');
                total.className = 'alternativa-total';
                total.textContent = numero(alternativa.total);

                item.append(selo, total);
            }

            lista.append(item);
        });
    }

    function cenaRanking(fase, mudou) {
        if (mudou) {
            mostrarCena('ranking');

            const titulo = campo('ranking-titulo');
            const detalhe = document.createElement('small');

            if (fase === 'finalizado') {
                titulo.textContent = 'Ranking final';
                detalhe.textContent = dados.titulo;
            } else {
                titulo.textContent = 'Ranking parcial';
                detalhe.textContent = dados.pergunta
                    ? `Depois da pergunta ${dados.pergunta.indice} de ${dados.pergunta.total}`
                    : '';
            }

            titulo.append(detalhe);
            desenharRanking(fase);
        }

        rodape({
            contador: {
                numero: dados.participantes,
                texto: dados.participantes === 1 ? 'participante' : 'participantes',
            },
        });
    }

    function desenharRanking(fase) {
        const lista = campo('ranking-lista');
        const ranking = dados.ranking || [];

        lista.replaceChildren();

        ranking.forEach((linha, indice) => {
            const item = document.createElement('li');
            item.className = 'ranking-item';
            item.style.setProperty('--ordem', String(indice));

            if (fase === 'finalizado' && linha.posicao <= 3) {
                item.classList.add('podio');
            }

            const posicao = document.createElement('span');
            posicao.className = 'ranking-posicao';
            posicao.textContent = `${linha.posicao}º`;

            const nome = document.createElement('span');
            nome.className = 'ranking-nome';
            nome.textContent = linha.nome;

            const numeros = document.createElement('span');
            numeros.className = 'ranking-numeros';

            const pontos = document.createElement('strong');
            pontos.textContent = `${numero(linha.pontos)} pts`;

            numeros.append(pontos, `${linha.acertos} ${linha.acertos === 1 ? 'acerto' : 'acertos'}`);
            item.append(posicao, nome, numeros);
            lista.append(item);
        });
    }

    function atualizarRelogio() {
        if (!dados) {
            return;
        }

        const janela = janelaResposta();
        const fase = faseLocal();

        if (!janela || !['leitura', 'respondendo'].includes(fase)) {
            return;
        }

        const instante = instanteJogo();
        let falta;
        let carga;

        if (fase === 'leitura') {
            falta = Math.max(0, janela.inicio - instante);
            carga = 1 - falta / Math.max(1, janela.inicio - dados.inicio_ms);

            campo('aviso-leitura').textContent = `As alternativas aparecem em ${Math.ceil(falta / 1000)} s`;
        } else {
            falta = Math.max(0, janela.fim - instante);
            carga = falta / Math.max(1, janela.fim - janela.inicio);
        }

        carga = Math.max(0, Math.min(1, carga));

        const relogio = campo('relogio');
        relogio.textContent = String(Math.ceil(falta / 1000));
        relogio.classList.toggle('acabando', fase === 'respondendo' && falta <= 5000 && !pausado());
        relogio.classList.toggle('pausado', Boolean(pausado()));

        const linha = campo('linha');
        linha.style.setProperty('--carga', carga.toFixed(4));

        linha.querySelectorAll('.linha-energia-postes span').forEach((poste, indice) => {
            poste.classList.toggle('energizado', indice / (POSTES - 1) <= carga);
        });
    }

    desenharQr();
    document.fonts.ready.then(() => cenaChave && caber());
    window.addEventListener('resize', () => cenaChave && caber());
    buscar();
    setInterval(desenhar, 100);
})();
