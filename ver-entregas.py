#!/usr/bin/env python3
# Visibilidade das entregas do CTF (le direto do CTFd, admin).
# Uso:  python3 ver-entregas.py           (placar + capturas por equipe)
#       python3 ver-entregas.py --erradas (inclui tentativas erradas e flags de outra equipe)
# Le as credenciais de admin do .env (CTFD_ADMIN_USER / CTFD_ADMIN_PASS).

import urllib.request, urllib.parse, http.cookiejar, re, json, sys, os
from collections import defaultdict

BASE = os.environ.get("CTFD_BASE", "http://127.0.0.1:8000")

def ler_env(chave, padrao=""):
    try:
        for ln in open(os.path.join(os.path.dirname(__file__), ".env")):
            if ln.strip().startswith(chave + "="):
                return ln.strip().split("=", 1)[1]
    except FileNotFoundError:
        pass
    return padrao

USER = ler_env("CTFD_ADMIN_USER", "professor")
PW   = ler_env("CTFD_ADMIN_PASS")

cj = http.cookiejar.CookieJar()
op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))
op.addheaders = [("User-Agent", "ver-entregas")]

def get(p):
    return op.open(BASE + p, timeout=30).read().decode()

h = get("/login")
n = re.search(r'name="nonce"[^>]*value="([0-9a-f]+)"', h).group(1)
op.open(BASE + "/login", data=urllib.parse.urlencode(
    {"name": USER, "password": PW, "nonce": n, "_submit": "Submit"}).encode()).read()
csrf = re.search(r"'csrfNonce':\s*\"([0-9a-f]+)\"", get("/")).group(1)

def api(p):
    r = urllib.request.Request(BASE + "/api/v1" + p,
        headers={"CSRF-Token": csrf, "Accept": "application/json"})
    return json.load(op.open(r, timeout=30))

ver_erradas = "--erradas" in sys.argv

sb = api("/scoreboard")["data"]
subs = api("/submissions?per_page=500")["data"]

print("=" * 60)
print("PLACAR (equipes com pontos)")
print("=" * 60)
if not sb:
    print("Nenhuma equipe pontuou ainda.")
else:
    for i, row in enumerate(sb, 1):
        print(f"{i:>2}. {row.get('name'):<12} {row.get('score')} pts")

cert = [s for s in subs if s.get("type") == "correct"]
err  = [s for s in subs if s.get("type") != "correct"]

porequipe = defaultdict(list)
for s in cert:
    porequipe[(s.get('user') or {}).get('name', '?')].append(
        ((s.get('challenge') or {}).get('name', '?'), s.get('date', '')[:19]))

print()
print("=" * 60)
print(f"CAPTURAS POR EQUIPE  (equipes que ja entregaram: {len(porequipe)})")
print("=" * 60)
if not porequipe:
    print("Ninguem capturou flag ainda.")
for name in sorted(porequipe):
    caps = sorted(porequipe[name], key=lambda x: x[1])
    print(f"\n{name}: {len(caps)} flag(s)")
    for cname, date in caps:
        print(f"   [{date[11:]}] {cname}")

if ver_erradas:
    print()
    print("=" * 60)
    print(f"TENTATIVAS ERRADAS ({len(err)})  -- inclui flag de outra equipe (anti-cola)")
    print("=" * 60)
    if not err:
        print("Nenhuma tentativa errada.")
    for s in sorted(err, key=lambda x: x.get('date', '')):
        quem = (s.get('user') or {}).get('name', '?')
        desafio = (s.get('challenge') or {}).get('name', '?')
        tent = (s.get('provided') or '').strip()
        print(f"   [{s.get('date','')[11:19]}] {quem:<12} {desafio:<30} tentou: {tent}")

print()
print(f"(fonte: {BASE} ao vivo | corretas: {len(cert)} | erradas: {len(err)})")
