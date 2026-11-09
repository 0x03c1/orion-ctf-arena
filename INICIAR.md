# ▶️ Como iniciar a Arena (você mesmo, em 1 comando)

> Respondendo direto à dúvida: **você inicia tudo com um único comando, no seu terminal.**
> Não depende do Claude. O passo que o Claude não executa (abrir o túnel público) é justamente
> o que este script faz por você.

## Pré-requisitos
- **OrbStack** (ou Docker Desktop) aberto.
- **Python 3** (já vem no macOS) — usado só para provisionar o CTFd.
- Nada mais: o script gera os segredos e sobe os containers.

## Subir (dia da aula)
```bash
cd ~/Downloads/seguranca/cyber/arena
./start.sh
```
O que ele faz, em ordem:
1. Cria o `.env` na 1ª vez (segredos aleatórios; mostra a senha do admin do CTFd).
2. Sobe **CTFd + banco + redis + alvo Órion** (build na 1ª vez, alguns minutos).
3. Abre **2 túneis Cloudflare** e imprime as 2 URLs públicas.
4. **Provisiona o CTFd**: cria o admin, as `equipeNN` e os 6 desafios com flag por equipe.
5. Roda o **teste de contenção** do alvo.

No fim ele imprime:
```
🏁 CTFd (placar, alunos entram aqui): https://xxxx.trycloudflare.com
🎯 ALVO Órion (onde atacam):          https://yyyy.trycloudflare.com
```

## Entregar à turma
```bash
./start.sh gabarito --credenciais     # tabela equipe/senha para distribuir
```
Cada dupla recebe `equipeNN` + senha. Essa **mesma** credencial serve para:
- **entrar no CTFd** (1ª URL) — onde submete flags e vê o placar;
- **atacar o alvo** (2ª URL) — onde captura as flags.

## Comandos do dia a dia
```bash
./start.sh urls         # reimprime as 2 URLs
./start.sh gabarito     # flags + senhas de todas as equipes (confidencial)
./start.sh testar       # reexecuta o teste de contenção
./start.sh parar        # derruba tudo (as URLs públicas somem)
```

## Se o provisionamento automático falhar
Raro, mas se o `/setup` do CTFd não concluir sozinho:
1. Abra a 1ª URL (ou `http://127.0.0.1:8000`), faça o assistente inicial, escolha modo **Users**.
2. Rode:
   ```bash
   python3 provisionar.py --apenas-desafios --alvo-url "$(sed -n 2p .urls)"
   ```
   (cria as equipes e os desafios via API).

## Memória (máquina de 8 GB)
Este Mac tem **8 GB de RAM** — é o recurso apertado (disco sobra). Por isso o `docker-compose.yml`
já põe **teto de RAM em cada serviço** (CTFd 1g · MariaDB 512m · Redis 128m · alvo 512m · túneis 128m),
somando ~2,4 GB. Isso evita o swap-storm que travava o OrbStack. Para folgar ainda mais na aula:
feche o navegador/apps pesados antes de `./start.sh` e, se o OrbStack permitir, dê a ele ≥4 GB em
Settings → Resources.

## Se o Docker/OrbStack travar
O sintoma (daemon lento, `docker ps` congela) é **pressão de memória**, não falta de disco. Resolve assim:
```bash
osascript -e 'quit app "OrbStack"'; sleep 4; open -a OrbStack
```
Espere ~20 s e rode `./start.sh` de novo (o build fica em cache; é rápido).

## Diferença para o `lab.sh` antigo
- **Placar**: agora é o **CTFd** (times, pontuação dinâmica, first blood, dicas) — não o `placar.sh` artesanal.
- **Flags**: **únicas por equipe** (anti-cola) — a flag de uma equipe é recusada para outra.
- **Início**: **um comando seu** (`./start.sh`), que abre os túneis — sem depender do Claude.
