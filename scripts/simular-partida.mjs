/*
| Teste de carga: simula uma partida inteira com N participantes.
| Cada participante virtual faz o mesmo que o celular (entra, lê o
| estado a cada 2 s, responde, consulta o resultado) e o script
| conduz a partida como o administrador.
|
| Uso (Node 20+):
|   node scripts/simular-partida.mjs --url http://localhost:8080 \
|       --email admin@exemplo.com --senha "..." --quiz 1 --participantes 130 [--limpar]
|
| --limpar apaga a partida de teste no fim.
*/

const argumentos = Object.fromEntries(
    process.argv.slice(2).reduce((pares, valor, indice, lista) => {
        if (valor.startsWith('--')) pares.push([valor.slice(2), lista[indice + 1] && !lista[indice + 1].startsWith('--') ? lista[indice + 1] : '']);
        return pares;
    }, []),
);

const URL_BASE = (argumentos.url || 'http://localhost:8080').replace(/\/$/, '');
const TOTAL = Number(argumentos.participantes || 130);
const QUIZ_ID = Number(argumentos.quiz || 1);
const SEM_RESPOSTA = 0.1;

const esperar = (ms) => new Promise((resolver) => setTimeout(resolver, ms));

// Igual ao celular: os horários da partida são do servidor, não deste computador.
let diferencaRelogio = 0;
const agoraServidor = () => Date.now() + diferencaRelogio;

async function sincronizarRelogio() {
    let melhor = null;

    for (let medicao = 0; medicao < 5; medicao += 1) {
        const inicio = Date.now();
        const dados = await (await fetch(`${URL_BASE}/api/hora.php`, { cache: 'no-store' })).json();
        const fim = Date.now();

        if (!melhor || fim - inicio < melhor.ida) {
            melhor = { ida: fim - inicio, diferenca: dados.agora_ms - (inicio + fim) / 2 };
        }
    }

    diferencaRelogio = melhor.diferenca;
    console.log(`Relógio do servidor: ${(diferencaRelogio / 1000).toFixed(2)} s em relação a este computador.`);
}
const sortear = (min, max) => min + Math.random() * (max - min);

const metricas = {};
const erros = [];

function medir(nome, ms, ok) {
    const item = metricas[nome] || (metricas[nome] = { tempos: [], falhas: 0 });
    item.tempos.push(ms);
    if (!ok) item.falhas += 1;
}

class Navegador {
    constructor() {
        this.cookies = {};
    }

    async pedir(nome, caminho, opcoes = {}) {
        const inicio = performance.now();
        let resposta;

        try {
            resposta = await fetch(URL_BASE + caminho, {
                redirect: 'manual',
                ...opcoes,
                headers: {
                    ...(opcoes.headers || {}),
                    Cookie: Object.entries(this.cookies).map(([chave, valor]) => `${chave}=${valor}`).join('; '),
                },
            });
        } catch (erro) {
            medir(nome, performance.now() - inicio, false);
            erros.push(`${nome}: ${erro.message}`);
            throw erro;
        }

        for (const linha of resposta.headers.getSetCookie()) {
            const [par] = linha.split(';');
            const [chave, ...resto] = par.split('=');
            this.cookies[chave.trim()] = resto.join('=');
        }

        const ok = resposta.status < 500;
        medir(nome, performance.now() - inicio, ok);

        if (!ok) {
            erros.push(`${nome}: HTTP ${resposta.status}`);
        }

        return resposta;
    }

    formulario(nome, caminho, campos) {
        return this.pedir(nome, caminho, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(campos).toString(),
        });
    }

    json(nome, caminho, corpo) {
        return this.pedir(nome, caminho, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(corpo),
        });
    }
}

async function prepararAdmin() {
    const admin = new Navegador();

    await admin.pedir('admin_login_pagina', '/admin/login.php');
    const login = await admin.formulario('admin_login', '/admin/login.php', {
        email: argumentos.email,
        password: argumentos.senha,
    });

    if (login.status !== 302 || (login.headers.get('location') || '').includes('login')) {
        throw new Error('Login do admin falhou. Confira --email e --senha.');
    }

    const pagina = await (await admin.pedir('admin_quizzes', '/admin/quizzes.php')).text();
    const csrf = pagina.match(/name="csrf_token"\s+value="([a-f0-9]+)"/);

    if (!csrf) {
        throw new Error('Não achei o csrf_token na tela de quizzes.');
    }

    const criada = await admin.formulario('admin_criar_partida', '/admin/criar-partida.php', {
        csrf_token: csrf[1],
        quiz_id: String(QUIZ_ID),
    });

    const codigo = (criada.headers.get('location') || '').match(/code=(\d{6})/);

    if (!codigo) {
        throw new Error('A partida não foi criada. Confira --quiz.');
    }

    return { admin, csrf: csrf[1], codigo: codigo[1] };
}

async function participante(numero, codigo, estadoGeral) {
    const navegador = new Navegador();
    const respondidas = new Set();
    const resultados = new Set();

    await esperar(sortear(0, 8000));

    const entrada = await navegador.formulario('entrar', '/entrar-partida.php', {
        code: codigo,
        name: `Participante Teste ${String(numero).padStart(3, '0')}`,
        corporate_user: `teste${String(numero).padStart(3, '0')}`,
    });

    if (!navegador.cookies.quiz_participant) {
        erros.push(`entrar: participante ${numero} ficou sem cookie (HTTP ${entrada.status})`);
        return;
    }

    estadoGeral.entraram += 1;

    while (!estadoGeral.acabou) {
        let estado = null;

        try {
            const resposta = await navegador.pedir('estado_json', `/estado/${codigo}.json?t=${Date.now()}`);
            estado = resposta.ok ? await resposta.json() : null;
        } catch (erro) {
            estado = null;
        }

        if (estado && estado.pergunta) {
            const id = estado.pergunta.id;
            const aberta = ['leitura', 'respondendo'].includes(estado.status);

            if (aberta && !respondidas.has(id)) {
                respondidas.add(id);

                if (Math.random() > SEM_RESPOSTA) {
                    const abre = estado.status === 'leitura' ? estado.fim_ms : estado.inicio_ms;
                    // 5% tocam meio segundo antes de abrir, como um celular com relógio adiantado.
                    const adiantado = Math.random() < 0.05 && estado.status === 'leitura';
                    const atraso = adiantado
                        ? Math.max(0, abre - agoraServidor() - 500)
                        : Math.max(0, abre - agoraServidor()) + sortear(300, 12000);
                    const letra = 'ABCD'[Math.floor(Math.random() * 4)];

                    const enviar = async () => {
                        try {
                            const resposta = await navegador.json('responder', '/api/responder.php', {
                                code: codigo,
                                pergunta_id: id,
                                letra,
                            });
                            const dados = await resposta.json();

                            if (dados.motivo === 'cedo') {
                                estadoGeral.cedo += 1;
                                setTimeout(enviar, dados.espera_ms);
                                return;
                            }

                            if (dados.ok) estadoGeral.aceitas += 1;
                            else estadoGeral.recusadas[dados.motivo] = (estadoGeral.recusadas[dados.motivo] || 0) + 1;
                        } catch (erro) {
                            // já registrado em erros
                        }
                    };

                    setTimeout(enviar, atraso);
                }
            }

            const chave = `${estado.status}:${id}`;

            if (['resultado', 'ranking', 'finalizado'].includes(estado.status) && !resultados.has(chave)) {
                resultados.add(chave);
                setTimeout(() => navegador.pedir('eu', `/api/eu.php?code=${codigo}`).catch(() => {}), sortear(0, 1500));
            }
        }

        await esperar(sortear(2000, 2500));
    }
}

async function conduzir(admin, csrf, codigo, estadoGeral) {
    const acao = (nome) => admin.json(`acao_${nome}`, '/admin/api/acao.php', { code: codigo, acao: nome, csrf_token: csrf });
    const tick = async () => {
        try {
            const resposta = await admin.pedir('admin_tick', `/admin/api/tick.php?code=${codigo}&completo=1`);
            const dados = await resposta.json();
            return dados.ok ? dados : null;
        } catch (erro) {
            erros.push(`admin_tick: ${erro.message.slice(0, 80)}`);
            return null;
        }
    };

    while (estadoGeral.entraram < TOTAL) {
        await esperar(500);
    }

    console.log(`${estadoGeral.entraram} participantes na sala. Iniciando.`);
    await acao('iniciar');

    let ultimo = '';
    let dados = null;

    while (true) {
        const lido = await tick();

        if (!lido) {
            await esperar(1000);
            continue;
        }

        dados = lido;

        const chave = `${dados.status}:${dados.pergunta ? dados.pergunta.id : ''}`;

        if (chave !== ultimo) {
            ultimo = chave;
            const p = dados.pergunta;
            console.log(`  ${p ? `pergunta ${p.indice}/${p.total}` : ''} ${dados.status} (responderam ${dados.respondidos}/${dados.participantes})`);

            if (dados.status === 'resultado') {
                await esperar(3000);
                const ultima = p.indice === p.total;

                if (!ultima && p.indice % 3 === 0) {
                    await acao('mostrar_ranking');
                    await esperar(3000);
                }

                await acao('proxima');
            }

            if (dados.status === 'finalizado') {
                break;
            }
        }

        await esperar(1000);
    }

    return dados;
}

function percentil(lista, p) {
    const ordenada = [...lista].sort((a, b) => a - b);
    return ordenada[Math.min(ordenada.length - 1, Math.floor(ordenada.length * p))] || 0;
}

await sincronizarRelogio();
const { admin, csrf, codigo } = await prepararAdmin();
console.log(`Partida ${codigo} criada em ${URL_BASE}. Entrando ${TOTAL} participantes...`);

const estadoGeral = { entraram: 0, aceitas: 0, cedo: 0, recusadas: {}, acabou: false };
const inicio = Date.now();
const bots = Array.from({ length: TOTAL }, (_, indice) => participante(indice + 1, codigo, estadoGeral));

const final = await conduzir(admin, csrf, codigo, estadoGeral);
await esperar(4000);
estadoGeral.acabou = true;
await Promise.allSettled(bots);

console.log(`\nPartida encerrada em ${Math.round((Date.now() - inicio) / 1000)} s.`);
console.log(`Respostas aceitas: ${estadoGeral.aceitas}. Recusadas: ${JSON.stringify(estadoGeral.recusadas)}. Avisos de "cedo" reenviados: ${estadoGeral.cedo}`);

const somaRespondidas = (final.ranking || []).reduce((soma, linha) => soma + linha.respondidas, 0);
console.log(`Respostas gravadas no banco (ranking): ${somaRespondidas}`);
console.log(`Top 3: ${(final.ranking || []).slice(0, 3).map((l) => `${l.nome} ${l.acertos} acertos ${l.pontos} pts`).join(' | ')}`);

console.log('\nEndpoint              pedidos  falhas   p50 ms   p95 ms   máx ms');

for (const [nome, item] of Object.entries(metricas)) {
    console.log(
        nome.padEnd(20),
        String(item.tempos.length).padStart(8),
        String(item.falhas).padStart(7),
        percentil(item.tempos, 0.5).toFixed(0).padStart(8),
        percentil(item.tempos, 0.95).toFixed(0).padStart(8),
        Math.max(...item.tempos).toFixed(0).padStart(8),
    );
}

if (argumentos.limpar !== undefined) {
    // Apaga a partida de teste (participantes e respostas vão junto).
    const apagada = await admin.formulario('admin_excluir_partida', '/admin/excluir-partida.php', {
        csrf_token: csrf,
        game_code: codigo,
    });
    console.log(`
Partida de teste ${codigo} ${apagada.status === 302 ? 'apagada' : `NÃO apagada (HTTP ${apagada.status})`}.`);
}

if (erros.length) {
    console.log(`\n${erros.length} erros. Primeiros:`);
    [...new Set(erros)].slice(0, 15).forEach((erro) => console.log(`  ${erro}`));
}

process.exit(erros.length || somaRespondidas !== estadoGeral.aceitas ? 1 : 0);
