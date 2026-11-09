<?php
// Helpers: banco, identidade de equipe, JWT (vulnerável), eventos e layout.
require_once __DIR__ . '/config.php';

function ip(): string { return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '-'; }
function usuario(): ?string { return $_SESSION['usuario'] ?? null; }

// EVT|<ip>|<equipe>|<tipo>|<detalhe>|<pts>  -> só para forense/depuração do professor.
// A PONTUAÇÃO oficial é do CTFd; isto aqui não pontua nada.
function evento(string $tipo, string $detalhe = '', int $pts = 0, ?string $quem = null): void {
  $u = $quem ?? (usuario() ?? ($GLOBALS['api_user'] ?? '-'));
  error_log(sprintf('EVT|%s|%s|%s|%s|%d', ip(), $u, $tipo, $detalhe, $pts));
}

function db(): PDO {
  $novo = !file_exists(DB_ARQ);
  if ($novo) @mkdir(dirname(DB_ARQ), 0777, true);
  $db = new PDO('sqlite:' . DB_ARQ);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  if ($novo) semear($db);
  return $db;
}
function semear(PDO $db): void {
  // Personas = alvos de enumeração (BOLA). As equipes entram sob demanda (garante_equipe).
  $db->exec("CREATE TABLE usuarios (id INTEGER PRIMARY KEY, usuario TEXT UNIQUE, senha TEXT, papel TEXT,
             nome TEXT, cargo TEXT, salario INTEGER, obs TEXT)");
  $st = $db->prepare("INSERT INTO usuarios (id,usuario,senha,papel,nome,cargo,salario,obs) VALUES (?,?,?,?,?,?,?,?)");
  // id 1000 = CEO: o holerite dele guarda a flag de BOLA (injetada por equipe no acesso)
  $st->execute([1000,'ricardo.ceo','-','admin','Ricardo Alves','CEO',98000,'Bônus confidencial da diretoria.']);
  $st->execute([1001,'ana.prado','-','colaborador','Ana Prado','Estagiária de Marketing',1800,'—']);
  $st->execute([1002,'mcosta','-','colaborador','Marcos Costa','Analista Financeiro',7200,'—']);
}
// Cria a linha da equipe na 1ª autenticação (para /eu e Mass Assignment funcionarem).
function garante_equipe(PDO $db, string $equipe): void {
  $equipe = strtolower(trim($equipe));
  $db->prepare("INSERT OR IGNORE INTO usuarios (usuario,senha,papel,nome,cargo,salario,obs)
                VALUES (?,?,?,?,?,?,?)")
     ->execute([$equipe, senha_equipe($equipe), 'colaborador', $equipe, 'Equipe (treino)', 2000, '—']);
}

// ---------- JWT (HS256) com verificação PROPOSITALMENTE insegura ----------
function b64u(string $d): string { return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); }
function b64u_dec(string $d): string { return (string)base64_decode(strtr($d, '-_', '+/')); }
function jwt_emitir(string $sub, string $papel): string {
  $h = b64u(json_encode(['alg'=>'HS256','typ'=>'JWT']));
  $p = b64u(json_encode(['sub'=>$sub,'papel'=>$papel,'exp'=>time()+3600]));
  $s = b64u(hash_hmac('sha256', "$h.$p", JWT_SEGREDO, true));
  return "$h.$p.$s";
}
function jwt_ler(?string $tok): ?array {
  if (!$tok) return null;
  $parts = explode('.', $tok);
  if (count($parts) < 2) return null;
  [$h, $p] = $parts; $s = $parts[2] ?? '';
  $head = json_decode(b64u_dec($h), true); $pay = json_decode(b64u_dec($p), true);
  if (!is_array($pay)) return null;
  if (strtolower($head['alg'] ?? '') === 'none') return $pay;         // BUG 1: aceita alg none
  $calc = b64u(hash_hmac('sha256', "$h.$p", JWT_SEGREDO, true));       // BUG 2: segredo fraco "orion"
  return hash_equals($calc, $s) ? $pay : null;
}
function token_do_header(): ?array {
  $h = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
  if (stripos($h, 'Bearer ') === 0) return jwt_ler(trim(substr($h, 7)));
  return null;
}

// ---------- layout (identidade própria, sem traço de DVWA) ----------
function topo(string $titulo): void {
  $u = usuario();
  echo '<!doctype html><html lang="pt-br"><head><meta charset="utf-8">';
  echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
  echo '<title>' . htmlspecialchars($titulo) . ' · ' . MARCA . '</title>';
  echo '<link rel="stylesheet" href="/assets/estilo.css"></head><body>';
  echo '<header class="topo"><div class="marca"><span class="logo">◆</span><div><strong>' . MARCA .
       '</strong><small>' . SUBTITULO . '</small></div></div>';
  if ($u) echo '<nav><a href="?pg=painel">Painel</a><a href="?pg=assistente">Órion Assist</a>' .
               '<a class="sair" href="?pg=sair">Sair (' . htmlspecialchars($u) . ')</a></nav>';
  echo '</header><main>';
}
function rodape(): void { echo '</main><footer>' . RODAPE . '</footer></body></html>'; }
function exige_login(): void { if (!usuario()) { header('Location: ?pg=home'); exit; } }
