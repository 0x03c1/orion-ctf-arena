#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Gabarito/credenciais por equipe — SÓ PARA O PROFESSOR.
Imprime, para cada equipe, a senha (CTFd e alvo) e as 6 flags.

  python3 gabarito.py                 # todas as equipes (NUM_EQUIPES do .env)
  python3 gabarito.py equipe07        # só uma
  python3 gabarito.py --credenciais   # só a tabela equipe/senha (handout)
"""
import hashlib, hmac, os, sys

def carrega_env():
    caminho = os.path.join(os.path.dirname(os.path.abspath(__file__)), ".env")
    if os.path.exists(caminho):
        for linha in open(caminho, encoding="utf-8"):
            linha = linha.strip()
            if linha and not linha.startswith("#") and "=" in linha:
                k, v = linha.split("=", 1)
                os.environ.setdefault(k.strip(), v.strip())

def segredo():
    return (os.environ.get("ORION_TEAM_SECRET") or "troque-isto-no-env").encode()

def senha_equipe(eq):
    return hmac.new(segredo(), f"senha|{eq.lower()}".encode(), hashlib.sha256).hexdigest()[:10]

def flag_equipe(eq, d):
    h = hmac.new(segredo(), f"{eq.lower()}|{d}".encode(), hashlib.sha256).hexdigest()[:12]
    return f"FLAG{{orion-{d}-{h}}}"

DESAFIOS = ["bola", "mass", "jwt", "ssrf", "supply", "prompt"]

def main():
    carrega_env()
    args = [a for a in sys.argv[1:]]
    so_cred = "--credenciais" in args
    args = [a for a in args if not a.startswith("--")]
    n = int(os.environ.get("NUM_EQUIPES", "20"))
    equipes = args if args else [f"equipe{i:02d}" for i in range(1, n + 1)]

    if so_cred:
        print(f"{'EQUIPE':<10} {'SENHA (CTFd e alvo)':<20}")
        print("-" * 32)
        for eq in equipes:
            print(f"{eq:<10} {senha_equipe(eq):<20}")
        return

    for eq in equipes:
        print(f"\n### {eq}   (senha CTFd/alvo: {senha_equipe(eq)})")
        for d in DESAFIOS:
            print(f"  {d:<7} {flag_equipe(eq, d)}")

if __name__ == "__main__":
    main()
