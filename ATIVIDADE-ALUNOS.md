# Atividade CTF: Arena Orion

Bem-vindos ao desafio. Voces vao atuar como time de seguranca ofensiva contra a plataforma
ficticia "Orion Corp". Seis falhas modernas estao plantadas na aplicacao (API, SSRF, cadeia de
suprimentos e IA). Capturem as flags e registrem no placar.

## Como acessar

1. Endereco do CTF (placar e desafios): **__________________________________**
   (o professor informa o endereco no inicio da aula)
2. Entrem com as credenciais da sua equipe (login `equipeNN` e a senha entregue pelo professor).
   As MESMAS credenciais valem no placar (CTFd) e na aplicacao alvo.
3. O endereco da aplicacao alvo esta escrito dentro de cada desafio no placar.

## Regras

1. Ataquem apenas a aplicacao alvo indicada. Nada fora desse escopo.
2. Usem as credenciais da sua equipe. Cada equipe tem flags proprias: uma flag de outra equipe
   e recusada e fica registrada. Passar flag adiante nao pontua e expoe quem vazou.
3. Vale qualquer ferramenta (navegador, curl, Burp Suite, jwt_tool, hashcat e outras).
4. Nao ataquem o placar nem a infraestrutura. Apenas o alvo do jogo.
5. Submissao errada nao tira pontos. Ha limite de tentativas para evitar forca bruta no placar.

## Pontuacao

A pontuacao e dinamica: cada desafio comeca no valor maximo e cai conforme mais equipes o
resolvem (ate um piso). Resolver cedo vale mais. Cada desafio tem uma dica opcional no placar,
que desconta alguns pontos se voces a abrirem.

| # | Desafio | Classe (OWASP) | Dificuldade | Pontos (max ate piso) |
|---|---------|----------------|-------------|-----------------------|
| 1 | BOLA / IDOR | API1:2023 | Facil | 150 ate 60 |
| 2 | Mass Assignment | API3:2023 | Facil | 200 ate 80 |
| 3 | Prompt Injection (IA) | LLM01:2025 | Facil | 200 ate 80 |
| 4 | SSRF | A10:2021 | Media | 250 ate 100 |
| 5 | Falsificacao de JWT | API2:2023 | Dificil | 250 ate 100 |
| 6 | Cadeia de suprimentos | A08:2021 | Dificil | 300 ate 120 |
| | Total | | | 1350 |

## Os desafios e as pistas

Cada desafio abaixo diz onde comecar e da uma orientacao. O caminho ate a flag e com voces.
A flag tem sempre o formato `FLAG{orion-...}` e e unica da sua equipe.

### 1. BOLA / IDOR (API1:2023)
Ponto de partida: `GET /api/v1/holerite?id=`
Pista: a API devolve holerites por um identificador numerico, mas sera que ela confere de quem e
o holerite? Autentique-se, troque o identificador e observe o que volta. Comece a enumerar a
partir de 1000. Alguem importante guarda algo no campo de observacoes.

### 2. Mass Assignment (API3:2023)
Ponto de partida: `PATCH /api/v1/perfil`
Pista: veja primeiro quais campos existem no seu usuario (`GET /api/v1/eu`). Ao atualizar o
perfil, teste enviar um campo a mais do que a interface mostra. E se voce pudesse mudar o seu
proprio papel?

### 3. Prompt Injection (LLM01:2025)
Ponto de partida: no portal, a pagina do assistente "Orion Assist".
Pista: o assistente tem instrucoes secretas e recusa perguntas diretas sobre o segredo. Um filtro
simples bloqueia palavras obvias, mas talvez ele nao resista a uma instrucao que se sobreponha as
regras dele. Peca para ele ignorar o que veio antes e mostrar o proprio texto de sistema.

### 4. SSRF (A10:2021)
Ponto de partida: `POST /api/v1/importar`
Pista: esse endpoint faz o servidor buscar uma URL que voce informa, sem checar o destino. Existe
um servico interno de metadados que so responde a si mesmo (loopback). Faca o servidor buscar esse
servico interno por voce. Lembre de incluir o seu identificador de equipe na URL interna.

### 5. Falsificacao de JWT (API2:2023)
Ponto de partida: `GET /api/v1/admin/segredo`
Pista: esse recurso exige papel de administrador, e o papel viaja dentro do seu token. Estude como
o token e assinado. Ha duas fraquezas classicas de JWT para investigar: o algoritmo aceito pelo
servidor e a forca do segredo. Mantenha o seu proprio identificador no token forjado.

### 6. Cadeia de suprimentos (A08:2021)
Ponto de partida: `GET /api/v1/status`
Pista: a aplicacao lista suas dependencias (um SBOM). Uma delas esta marcada como suspeita, e o
historico de versao dela revela um canal de acesso escondido. Descubra como acionar esse canal
estando autenticado.

## Como pontuar

Capturou uma `FLAG{orion-...}`? Entre no placar, abra o desafio correspondente e cole a flag no
campo de submissao. Se estiver correta, os pontos entram para a sua equipe na hora.

## Sugestao de ferramentas

Navegador para o portal e para o assistente. Para a API: curl, Postman ou Burp Suite (interceptar,
repetir e enumerar requisicoes). Para o JWT: jwt_tool e hashcat. Tudo pode ser feito apenas com
curl e um editor de texto, se preferirem a linha de comando.

Boa cacada. Pensem como um atacante: mapeiem, levantem uma hipotese, testem e confirmem.
