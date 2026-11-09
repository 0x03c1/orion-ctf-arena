# Atividade CTF: Arena Orion (aula 13h as 15h)

Plano de conducao de um CTF de 2 horas com equipes, pontuacao por captura e debrief.
Alvo: portal e API "Orion Corp" com 6 vulnerabilidades modernas. Placar e pontuacao no CTFd.

## 1. Formato

- Equipes (duplas ou trios): cada uma recebe equipeNN e senha (serve no CTFd e no alvo).
- Onde pontuar: CTFd (link publico, 1a linha de `./start.sh urls`).
- Onde atacar: o alvo Orion (link dentro de cada desafio do CTFd).
- Anti-cola: cada equipe tem flags proprias. A flag de outra equipe e recusada e registrada, entao passar flag adiante nao funciona e deixa rastro.
- Duracao util de disputa: cerca de 75 min (o resto e abertura, aquecimento e debrief).

## 2. Cronograma (13h00 as 15h00)

| Horario | Bloco | O que acontece |
|---|---|---|
| 13:00 as 13:10 | Abertura | Regras, entrega das credenciais, mostrar o CTFd e o placar ao vivo. |
| 13:10 as 13:25 | Aquecimento guiado (todos juntos) | Login no CTFd e no Orion, recon rapido (nmap -sV, ffuf, robots.txt), decodificar o JWT do painel. Objetivo: todos comecam enxergando o alvo. |
| 13:25 as 14:40 | Competicao (75 min) | Equipes capturam e submetem no CTFd. Placar projetado. Dicas liberadas conforme a secao 5. |
| 14:40 as 14:55 | Debrief | Placar final, demonstracao das 6 falhas (passo a passo) e correcoes (use o seu gabarito e o passo a passo do professor, mantidos localmente). |
| 14:55 as 15:00 | Encerramento | Premiacao simbolica (1o, 2o, first bloods) e combinar o mini-relatorio. |

Marcos de ritmo: as 14:00 libere a 1a dica dos desafios dificeis, as 14:25 libere a 2a rodada de dicas, as 14:40 congele o placar (Admin, Settings, Freeze).

## 3. Pontuacao por captura

Pontuacao dinamica (CTFd): o valor comeca no maximo e cai conforme mais equipes resolvem (piso de 40%). Resolver cedo vale mais, o que recompensa o first blood naturalmente.

| # | Desafio | OWASP | Dificuldade | Pontos (max ate piso) | Dica (custo) |
|---|---------|-------|:---:|:---:|:---:|
| 1 | BOLA / IDOR | API1:2023 | facil | 150 ate 60 | -20 |
| 2 | Mass Assignment | API3:2023 | facil | 200 ate 80 | -25 |
| 3 | Prompt Injection (IA) | LLM01:2025 | facil | 200 ate 80 | -25 |
| 4 | SSRF | A10:2021 | media | 250 ate 100 | -30 |
| 5 | Falsificacao de JWT | API2:2023 | dificil | 250 ate 100 | -30 |
| 6 | Cadeia de suprimentos | A08:2021 | dificil | 300 ate 120 | -40 |
| | TOTAL | | | 1350 | |

- Submissao errada: sem perda de pontos (ha limite de taxa anti-forca-bruta no CTFd).
- Dica: desconta do valor do desafio, use como alavanca de ritmo nos dificeis.

## 4. O que e realista capturar em cerca de 75 min

Divisao pensada para caber no tempo (ninguem sai de maos vazias, e os fortes tem teto):

- Todas as equipes devem fechar as 3 faceis (BOLA, Mass, Prompt), cerca de 550 pts. Sao diretas: trocar um id, mandar um campo a mais, digitar uma injecao.
- A maioria resolve o SSRF (media), mais 250. Exige entender o fluxo "o servidor busca a URL".
- Equipes fortes fecham JWT e/ou Cadeia de suprimentos (dificeis), mais 250 e 300. Exigem jwt_tool, hashcat e analise de SBOM.

Expectativa: equipes medianas cerca de 800 a 1050 pts (4 a 5 flags), equipes fortes 1350 (6 flags). Mesmo quem so pega as faceis sai com conteudo e pontos.

Se a turma for iniciante e 75 min ficar curto: deixe as 3 faceis mais o SSRF como obrigatorias e JWT e Cadeia de suprimentos como bonus. Da para esconder os 2 dificeis no Admin (estado hidden) e revelar so nos ultimos 20 min como desafio-relampago.

## 5. Dicas (liberacao sugerida)

Cada desafio ja tem 1 dica cadastrada no CTFd (custo na tabela da secao 3). Sugestao de liberacao por tempo:
- 14:00: habilite as dicas dos dificeis (JWT, Cadeia de suprimentos) para destravar.
- 14:25: habilite a da media (SSRF) se o placar estiver travado.
- As faceis nao precisam de dica. Se alguem travar, oriente verbalmente.

## 6. Regras (ler na abertura)

1. Ataque apenas o alvo Orion indicado. Nada fora do escopo.
2. Use as credenciais da sua equipe. Flags sao por equipe: passar flag nao pontua e identifica o vazamento.
3. Vale qualquer ferramenta (Burp, curl, jwt_tool, hashcat, navegador).
4. Nao ataquem o CTFd nem a infraestrutura, so o alvo do jogo.
5. Pontuacao e objetiva (flag no CTFd). Em empate, vence quem chegou primeiro ao topo (horario).

## 7. Nota da atividade (opcional, amarrar a avaliacao)

| Componente | Peso | Base |
|---|---|---|
| Pontuacao no CTF | 60% | pontos / 1350 (ou / obrigatorias, se usar bonus) |
| Mini-relatorio por falha capturada | 30% | classe/OWASP, evidencia, impacto, correcao (rubrica: o gabarito do professor) |
| Participacao e postura etica | 10% | engajamento, respeito ao escopo |

O CTFd exporta o placar (Admin, Scoreboard, CSV) com horarios, base objetiva para a nota.

## 8. Checklist do professor (antes das 13h)

- [ ] `./start.sh`, anota as 2 URLs, confirma o teste de contencao no fim
- [ ] `./start.sh gabarito --credenciais`, gera a folha equipe/senha para distribuir
- [ ] Envia aos alunos a URL publica do CTFd mais a credencial da equipe
- [ ] Projeta o placar do CTFd (/scoreboard) e deixa o gabarito aberto so para voce
- [ ] (opcional) Zera tudo com `docker compose down -v && ./start.sh` se ja houve testes
- [ ] Acompanha as entregas ao vivo com `python3 ver-entregas.py`
