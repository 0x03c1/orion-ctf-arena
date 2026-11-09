# Orion CTF Arena

Arena de CTF focada em vulnerabilidades modernas de API, SSRF, cadeia de suprimentos e IA,
com placar oficial no CTFd e um mecanismo anti-cola: cada equipe recebe flags unicas.

![Licenca](https://img.shields.io/badge/licenca-MIT-green)
![CTFd](https://img.shields.io/badge/placar-CTFd%203.7-blue)
![Docker](https://img.shields.io/badge/deploy-Docker%20Compose-2496ED)
![OWASP](https://img.shields.io/badge/base-OWASP%20API%20%2B%20Top%2010%20%2B%20LLM-orange)

## O que e

Um laboratorio pronto para rodar uma competicao de seguranca ofensiva em sala de aula ou evento.
Sobe com um comando, publica dois enderecos (placar e alvo) e provisiona equipes e desafios
automaticamente. O alvo e uma aplicacao deliberadamente vulneravel ("Orion Corp") isolada em rede
sem saida, de modo que pode ser totalmente comprometida sem alcancar a maquina host, a LAN ou a internet.

O diferencial e o anti-cola: a flag de uma equipe nao vale para outra. Passar flag adiante nao
pontua e a tentativa fica registrada, apontando a origem do vazamento.

## Desafios

Seis vulnerabilidades modernas, mapeadas a referencias OWASP:

| # | Desafio | Classe OWASP | Onde comeca |
|---|---------|--------------|-------------|
| 1 | BOLA / IDOR | API1:2023 | `GET /api/v1/holerite?id=` |
| 2 | Mass Assignment | API3:2023 | `PATCH /api/v1/perfil` |
| 3 | Falsificacao de JWT | API2:2023 | `GET /api/v1/admin/segredo` |
| 4 | SSRF para servico interno | A10:2021 | `POST /api/v1/importar` |
| 5 | Cadeia de suprimentos | A08:2021 | header `X-Orion-Telemetry` |
| 6 | Prompt Injection (IA) | LLM01:2025 | portal, assistente Orion Assist |

As solucoes (gabarito e writeups) nao fazem parte deste repositorio, de proposito: assim quem clona
tem o desafio para resolver. A pontuacao e dinamica no CTFd (o valor cai conforme mais equipes resolvem).

## Como o anti-cola funciona

Cada flag e cada senha de equipe saem de um HMAC de um segredo da arena (`ORION_TEAM_SECRET`),
que fica so no seu `.env` e nunca e versionado:

```
FLAG{orion-<desafio>-HMAC12(SEGREDO, "equipe|desafio")}
senha_da_equipe       = HMAC10(SEGREDO, "senha|equipe")
```

O alvo gera a flag conforme a equipe autenticada. O plugin do CTFd (`orion_equipe`) recalcula a flag
esperada para a equipe que esta submetendo e compara. Logo, cada equipe so valida a propria flag.
A mesma senha derivada serve no CTFd e no alvo: uma credencial por equipe.

## Arquitetura

```
                 Internet
          +---------+---------+
   cloudflared-ctfd     cloudflared-orion      (rede borda, com saida)
          |                   |   |
        CTFd (placar)         |  rede "alvo" (internal: SEM saida/host/LAN)
     rede "ctfd_int"          +-- alvo Orion (PHP/Apache)
   (internal: CTFd+DB+Redis)
```

- Unica porta publicada no host: o CTFd em `127.0.0.1:8000` (admin e provisionamento).
- O alvo nao publica porta nenhuma. Jogadores chegam so pelo tunel, e a rede do alvo nao tem rota de saida.

## Stack

- CTFd 3.7 (placar, equipes, pontuacao dinamica, dicas, exportacao) com MariaDB e Redis.
- Plugin de flag por equipe (anti-cola), em Python puro.
- Alvo em PHP/Apache, isolado em rede Docker sem saida.
- Provisionamento via API do CTFd, usando apenas a biblioteca padrao do Python.
- Tuneis publicos opcionais via Cloudflare.

## Rodar

Pre-requisitos: Docker e Docker Compose.

```bash
cp .env.example .env     # ajuste ORION_TEAM_SECRET e as senhas
./start.sh               # sobe tudo, provisiona o CTFd e abre os tuneis
./start.sh urls          # mostra as 2 URLs publicas (placar e alvo)
./start.sh parar         # derruba tudo
```

Operacao em sala e passo a passo de preparo estao em [INICIAR.md](INICIAR.md).

## Estrutura

| Caminho | Papel |
|---------|-------|
| `start.sh` | comando unico: sobe, provisiona e abre os tuneis |
| `docker-compose.yml` | CTFd, MariaDB, Redis, alvo e tuneis |
| `.env.example` | modelo de variaveis (o `.env` real nao e versionado) |
| `provisionar.py` | cria admin, equipes e desafios no CTFd via API |
| `ctfd-plugins/orion_equipe/` | plugin de flag por equipe (anti-cola) |
| `orion/` | alvo: Dockerfile e aplicacao PHP |
| `ver-entregas.py` | leitura ao vivo do placar e das submissoes (professor) |
| `ATIVIDADE-ALUNOS.md` | material do aluno: desafios e pistas |
| `ATIVIDADE-CTF.md` | plano de conducao da atividade com pontuacao |

## Trocar as flags entre turmas

Mude `ORION_TEAM_SECRET` no `.env` e suba de novo. Todas as senhas e flags mudam de uma vez.

## Aviso de uso

Projeto educacional. A aplicacao alvo e vulneravel de proposito e deve rodar apenas em ambiente
controlado e isolado, como entregue. Use somente para ensino, treino e pesquisa autorizados. Nao
exponha o alvo em producao nem o utilize contra sistemas de terceiros sem autorizacao explicita.

## Licenca

MIT. Veja [LICENSE](LICENSE).
