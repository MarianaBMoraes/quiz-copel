"""
Publica o projeto numa hospedagem compartilhada por FTP (cPanel, Hostinger).

Sobe os arquivos do projeto para a pasta do domínio, pulando o que só serve
no computador (docker, .git) e os arquivos de estado das partidas. O .env de
produção vai a partir do arquivo local .env.producao, que não entra no Git.

Configuração em .env.deploy (também fora do Git):
    FTP_HOST=...
    FTP_USER=...
    FTP_PASS=...
    FTP_PASTA=.          (pasta do domínio; "." = onde o FTP já entra)
    FTP_TLS=0            (1 para FTPS)

Uso:
    python scripts/publicar-ftp.py            publica
    python scripts/publicar-ftp.py --listar   só mostra o que subiria
"""

import ftplib
import subprocess
import sys
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent

IGNORAR_PREFIXOS = ("docker/", ".git", "public/estado/")
IGNORAR_ARQUIVOS = {"docker-compose.yml", ".env", ".env.deploy", ".env.producao"}


def ler_config(caminho: Path) -> dict:
    if not caminho.is_file():
        sys.exit(f"Arquivo {caminho.name} não encontrado. Veja o cabeçalho deste script.")

    config = {}

    for linha in caminho.read_text(encoding="utf-8").splitlines():
        linha = linha.strip()

        if linha and not linha.startswith("#") and "=" in linha:
            chave, valor = linha.split("=", 1)
            config[chave.strip()] = valor.strip()

    return config


def arquivos_do_projeto() -> list[str]:
    saida = subprocess.run(
        ["git", "ls-files", "--cached", "--others", "--exclude-standard"],
        cwd=RAIZ, capture_output=True, text=True, check=True,
    ).stdout.splitlines()

    arquivos = [
        caminho for caminho in saida
        if not caminho.startswith(IGNORAR_PREFIXOS)
        and caminho not in IGNORAR_ARQUIVOS
        and (RAIZ / caminho).is_file()
    ]

    # A pasta de estado precisa existir no servidor, com as regras de cache dela.
    arquivos += ["public/estado/.htaccess"]

    return sorted(set(arquivos))


def garantir_pasta(ftp: ftplib.FTP, pasta: str, criadas: set) -> None:
    atual = ""

    for parte in pasta.split("/"):
        atual = f"{atual}/{parte}" if atual else parte

        if atual in criadas:
            continue

        try:
            ftp.mkd(atual)
        except ftplib.error_perm:
            pass

        criadas.add(atual)


def main() -> None:
    arquivos = arquivos_do_projeto()

    if "--listar" in sys.argv:
        print("\n".join(arquivos))
        print(f"\n{len(arquivos)} arquivos (+ .env a partir do .env.producao)")
        return

    config = ler_config(RAIZ / ".env.deploy")
    producao = RAIZ / ".env.producao"

    if not producao.is_file():
        sys.exit("Crie o .env.producao com os dados do banco de produção antes de publicar.")

    classe = ftplib.FTP_TLS if config.get("FTP_TLS") == "1" else ftplib.FTP
    ftp = classe(config["FTP_HOST"], timeout=60)
    ftp.login(config["FTP_USER"], config["FTP_PASS"])

    if isinstance(ftp, ftplib.FTP_TLS):
        ftp.prot_p()

    ftp.cwd(config.get("FTP_PASTA", "."))
    print(f"Publicando em {ftp.pwd()} ({len(arquivos)} arquivos)")

    criadas: set = set()

    for indice, caminho in enumerate(arquivos, start=1):
        pasta = str(Path(caminho).parent).replace("\\", "/")

        if pasta != ".":
            garantir_pasta(ftp, pasta, criadas)

        with open(RAIZ / caminho, "rb") as arquivo:
            ftp.storbinary(f"STOR {caminho}", arquivo)

        print(f"  [{indice}/{len(arquivos)}] {caminho}")

    with open(producao, "rb") as arquivo:
        ftp.storbinary("STOR .env", arquivo)

    print("  .env de produção enviado")

    # Placeholder que algumas hospedagens criam e que ganharia do index.php.
    for sobra in ("default.php", "index.html"):
        try:
            ftp.delete(sobra)
            print(f"  removido {sobra} da hospedagem")
        except ftplib.error_perm:
            pass

    ftp.quit()
    print("Publicado.")


if __name__ == "__main__":
    main()
