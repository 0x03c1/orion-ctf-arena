#!/usr/bin/env bash
# =====================================================================
#  Arena Órion — UM comando para subir tudo. VOCÊ roda este script.
#  (Não depende de nenhuma ação do Claude; abre os túneis você mesmo.)
#
#    ./start.sh            sobe CTFd + alvo, provisiona e abre os 2 túneis
#    ./start.sh urls       mostra as URLs públicas atuais
#    ./start.sh gabarito   credenciais + flags por equipe (professor)
#    ./start.sh testar     teste de contenção do alvo
#    ./start.sh parar      derruba tudo (URLs públicas somem)
# =====================================================================
set -uo pipefail
BASE="$(cd "$(dirname "$0")" && pwd)"; cd "$BASE"
DOH="https://1.1.1.1/dns-query"
ok(){ printf '\033[32m✔\033[0m %s\n' "$*"; }
info(){ printf '\033[36m→\033[0m %s\n' "$*"; }
falha(){ printf '\033[31m✘ %s\033[0m\n' "$*" >&2; exit 1; }
dc(){ docker compose "$@"; }
code(){ curl -s -m 20 --doh-url "$DOH" -o /dev/null -w '%{http_code}' "$@" 2>/dev/null || true; }
rand(){ LC_ALL=C tr -dc 'a-f0-9' </dev/urandom | head -c "${1:-48}"; }

gerar_env(){
  [ -f .env ] && return
  info "Primeiro uso: gerando .env com segredos aleatórios"
  cp .env.example .env
  # substitui os placeholders por valores reais
  local seg; seg=$(rand 48)
  local key; key=$(rand 48)
  local dbp; dbp=$(rand 24)
  local dbr; dbr=$(rand 24)
  local adm; adm=$(rand 10)
  sed -i '' \
    -e "s|^ORION_TEAM_SECRET=.*|ORION_TEAM_SECRET=$seg|" \
    -e "s|^CTFD_SECRET_KEY=.*|CTFD_SECRET_KEY=$key|" \
    -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$dbp|" \
    -e "s|^DB_ROOT_PASSWORD=.*|DB_ROOT_PASSWORD=$dbr|" \
    -e "s|^CTFD_ADMIN_PASS=.*|CTFD_ADMIN_PASS=$adm|" .env
  ok ".env criado. Admin do CTFd: usuário 'professor' / senha '$adm' (veja/edite no .env)"
}

esperar_saudavel(){ # nome, tentativas
  local c; for _ in $(seq 1 "${2:-60}"); do
    c=$(docker inspect -f '{{.State.Health.Status}}' "$1" 2>/dev/null | tr -d '[:space:]')
    [ "$c" = healthy ] && return 0; sleep 3
  done; return 1
}
url_tunel(){ docker logs "$1" 2>&1 | grep -oE 'https://[a-z0-9-]+\.trycloudflare\.com' | head -1; }

up(){
  command -v docker >/dev/null 2>&1 || falha "Docker não encontrado (instale/abra o OrbStack)."
  docker info >/dev/null 2>&1 || { info "Abrindo OrbStack..."; open -a OrbStack 2>/dev/null; for _ in $(seq 1 30); do docker info >/dev/null 2>&1 && break; sleep 2; done; }
  docker info >/dev/null 2>&1 || falha "Docker não respondeu. Abra o OrbStack e tente de novo."
  gerar_env
  # o docker compose lê o .env automaticamente (não dar `source`: valores com espaço quebram o shell)

  info "Subindo banco, redis, CTFd e alvo (build na 1ª vez; pode levar alguns minutos)"
  dc up -d --build || falha "compose up falhou (veja: docker compose logs)"

  info "Aguardando o CTFd ficar saudável"
  esperar_saudavel arena-ctfd 80 || falha "CTFd não ficou saudável (docker compose logs ctfd)"
  ok "CTFd no ar em http://127.0.0.1:8000 (admin do professor)"
  esperar_saudavel arena-orion 40 || falha "alvo não ficou saudável (docker logs arena-orion)"
  ok "Alvo Órion no ar (isolado, sem porta publicada)"

  info "Abrindo túneis Cloudflare (CTFd e alvo)"
  local ctfd_url="" orion_url=""
  for _ in $(seq 1 40); do ctfd_url=$(url_tunel arena-tunel-ctfd); [ -n "$ctfd_url" ] && break; sleep 2; done
  for _ in $(seq 1 40); do orion_url=$(url_tunel arena-tunel-orion); [ -n "$orion_url" ] && break; sleep 2; done
  [ -n "$ctfd_url" ]  || falha "sem URL do túnel do CTFd (docker logs arena-tunel-ctfd)"
  [ -n "$orion_url" ] || falha "sem URL do túnel do alvo (docker logs arena-tunel-orion)"
  printf '%s\n%s\n' "$ctfd_url" "$orion_url" > "$BASE/.urls"

  info "Esperando as URLs responderem de fora"
  for _ in $(seq 1 45); do [[ "$(code "$orion_url/")" =~ ^(200|302)$ ]] && break; sleep 2; done

  info "Provisionando o CTFd (admin, equipes e desafios)"
  if python3 "$BASE/provisionar.py" --alvo-url "$orion_url"; then
    ok "CTFd provisionado"
  else
    printf '\033[33m! Provisionamento automático não concluiu.\033[0m\n'
    echo "  Faça o assistente inicial em $ctfd_url (ou http://127.0.0.1:8000) — modo 'Users' — e rode:"
    echo "    python3 provisionar.py --apenas-desafios --alvo-url $orion_url"
  fi

  nohup caffeinate -dimsu >/dev/null 2>&1 & echo $! > "$BASE/.caffeinate"
  testar
  echo
  printf '\033[1m🏁 CTFd (placar, alunos entram aqui): %s\033[0m\n' "$ctfd_url"
  printf '\033[1m🎯 ALVO Órion (onde atacam):          %s\033[0m\n' "$orion_url"
  echo   "   Admin do professor: $ctfd_url/login  (usuário/sua senha no .env)"
  echo   "   Credenciais+flags por equipe: ./start.sh gabarito"
  echo   "   Encerrar: ./start.sh parar"
}

testar(){
  echo "── Teste de contenção (a partir do container do alvo) ──"
  docker exec arena-orion bash -c '
    t(){ timeout 4 bash -c "</dev/tcp/$1/$2" 2>/dev/null && echo "  [!] ALCANCOU $1:$2 <<< FALHA" || echo "  [ok] bloqueado $1:$2"; }
    t host.docker.internal 32222; t 1.1.1.1 443; t 8.8.8.8 53
    getent hosts google.com >/dev/null 2>&1 && echo "  [!] resolve DNS externo" || echo "  [ok] sem DNS externo"
    ip route 2>/dev/null | grep -q default && echo "  [!] tem rota default" || echo "  [ok] sem rota de saida"' 2>/dev/null
}

case "${1:-up}" in
  up|"") up ;;
  urls) cat "$BASE/.urls" 2>/dev/null || echo "(sem túnel ativo)";;
  gabarito) shift; python3 "$BASE/gabarito.py" "$@";;
  testar) testar ;;
  parar|down)
    [ -f "$BASE/.caffeinate" ] && kill "$(cat "$BASE/.caffeinate")" 2>/dev/null; rm -f "$BASE/.caffeinate" "$BASE/.urls"
    dc down; ok "Arena derrubada. URLs públicas desativadas.";;
  *) sed -n '2,13p' "$0" | sed 's/^# \{0,1\}//'; exit 1 ;;
esac
