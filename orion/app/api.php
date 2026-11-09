<?php
// ===================================================================
//  Órion Corp — API v1 (ALVO de treino). Vulnerabilidades modernas.
//  Flags ÚNICAS POR EQUIPE: flag_equipe($equipe, '<desafio>').
// ===================================================================
require_once __DIR__ . '/app.php';
header('Content-Type: application/json; charset=utf-8');

function j($x, int $code = 200){ http_response_code($code); echo json_encode($x, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); exit; }
function corpo(): array { $d = json_decode(file_get_contents('php://input'), true); return is_array($d) ? $d : []; }

$metodo = $_SERVER['REQUEST_METHOD'];
$rota = preg_replace('#^/api/v1/?#', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$rota = trim($rota, '/');

// ----- SUPPLY CHAIN: backdoor da dependência comprometida (orion-telemetry 1.4.2) -----
// Responde em QUALQUER rota quando o header mágico chega. Personaliza por equipe (exige token).
$hdr_env = 'HTTP_' . strtoupper(str_replace('-', '_', DEP_BACKDOOR_HEADER));
if (isset($_SERVER[$hdr_env]) && hash_equals(DEP_BACKDOOR_TOKEN, $_SERVER[$hdr_env])) {
  $tk = token_do_header();
  if (!$tk || !eh_equipe($tk['sub'] ?? '')) j(['telemetry'=>'debug','erro'=>'inclua seu Authorization: Bearer <jwt> para personalizar a telemetria'], 401);
  $eq = strtolower($tk['sub']);
  evento('supply', 'backdoor-acionado', CATALOGO()['supply']['pts'], $eq);
  j(['telemetry'=>'debug', 'nota'=>'canal de depuração da dependência', 'segredo'=>flag_equipe($eq, 'supply')]);
}

// ----- login: valida equipeNN pela senha derivada e devolve um JWT -----
if ($rota === 'login' && $metodo === 'POST') {
  $b = corpo(); $u = (string)($b['usuario'] ?? ''); $s = (string)($b['senha'] ?? '');
  if (eh_equipe($u) && hash_equals(senha_equipe($u), $s)) {
    garante_equipe(db(), $u);
    evento('ACESSO', 'api-login:' . $u, 0, $u);
    j(['token' => jwt_emitir(strtolower($u), 'colaborador'), 'papel' => 'colaborador']);
  }
  evento('ACESSO', 'api-falha:' . $u, 0, $u ?: '-');
  j(['erro' => 'credenciais inválidas'], 401);
}

// ----- status / SBOM (pista do supply chain) -----
if ($rota === 'status') {
  j([
    'app' => 'orion-portal', 'versao' => '1.0',
    'dependencias' => [
      ['pacote'=>'orion-ui',        'versao'=>'2.1.0', 'origem'=>'registro-interno'],
      ['pacote'=>'orion-telemetry', 'versao'=>'1.4.2', 'origem'=>'registro-publico',
       'aviso'=>'versao 1.4.2 marcada como SUSPEITA pelo time de seguranca; ver changelog'],
      ['pacote'=>'sqlite-pdo',      'versao'=>'3.40',  'origem'=>'sistema'],
    ],
    'changelog_orion_telemetry' => 'https://exemplo.invalido/orion-telemetry/CHANGELOG — '
      . '"1.4.2: adicionado canal de telemetria via header ' . DEP_BACKDOOR_HEADER . ' (uso interno)"',
  ]);
}

// ----- serviço "interno" de metadados: só confia em loopback (alvo do SSRF) -----
// ANTES do portão de token. Personaliza pela equipe informada na URL (?equipe=equipeNN).
if ($rota === 'metadados') {
  $ra = $_SERVER['REMOTE_ADDR'] ?? '';
  if (!in_array($ra, ['127.0.0.1', '::1'], true))
    j(['erro'=>'acesso restrito à rede interna (apenas loopback)'], 403);
  $eq = strtolower((string)($_GET['equipe'] ?? ''));
  if (!eh_equipe($eq)) j(['erro'=>'informe sua equipe: ?equipe=equipeNN'], 400);
  evento('ssrf', 'metadados:' . $eq, CATALOGO()['ssrf']['pts'], $eq);
  j(['servico'=>'metadados-internos', 'ambiente'=>'producao', 'segredo'=>flag_equipe($eq, 'ssrf')]);
}

// ----- daqui em diante exige token (autenticação) -----
$tok = token_do_header();
if (!$tok) j(['erro' => 'faltou Authorization: Bearer <jwt>'], 401);
$sub = strtolower($tok['sub'] ?? '?'); $papel = $tok['papel'] ?? 'colaborador';
$GLOBALS['api_user'] = $sub;

// ----- quem sou eu -----
if ($rota === 'eu') {
  $r = db()->prepare("SELECT id,usuario,papel,nome,cargo FROM usuarios WHERE usuario=?");
  $r->execute([$sub]); j($r->fetch(PDO::FETCH_ASSOC) ?: ['usuario'=>$sub,'papel'=>$papel,'nota'=>'sessão de equipe']);
}

// ----- BOLA / IDOR: holerite por id, SEM checar dono -----
if (preg_match('#^holerite#', $rota)) {
  $id = (int)($_GET['id'] ?? 1001);
  evento('bola', 'holerite-id:' . $id, 0, $sub);
  $r = db()->prepare("SELECT id,nome,cargo,salario,obs FROM usuarios WHERE id=?");   // <- sem WHERE dono=sub
  $r->execute([$id]); $h = $r->fetch(PDO::FETCH_ASSOC);
  if ($h && (int)$h['id'] === 1000) $h['obs'] = 'Bônus confidencial. ' . flag_equipe($sub, 'bola'); // CEO
  j($h ?: ['erro'=>'holerite inexistente'], $h ? 200 : 404);
}

// ----- MASS ASSIGNMENT: PATCH /perfil aceita QUALQUER campo, inclusive papel -----
if ($rota === 'perfil' && $metodo === 'PATCH') {
  $b = corpo(); $campos = []; $valores = [];
  foreach ($b as $k => $v) {                       // <- sem allowlist: aceita tudo, até "papel"
    if (!preg_match('/^[a-z_]+$/', $k)) continue;
    $campos[] = "$k=?"; $valores[] = is_scalar($v) ? $v : json_encode($v);
  }
  if ($campos) {
    $valores[] = $sub;
    db()->prepare("UPDATE usuarios SET " . implode(',', $campos) . " WHERE usuario=?")->execute($valores);
  }
  $r = db()->prepare("SELECT usuario,papel,nome,cargo FROM usuarios WHERE usuario=?");
  $r->execute([$sub]); $perfil = $r->fetch(PDO::FETCH_ASSOC);
  $resp = ['perfil' => $perfil];
  if (($perfil['papel'] ?? '') === 'admin') {
    evento('mass', 'escalou-para-admin', CATALOGO()['mass']['pts'], $sub);
    $resp['parabens'] = 'Você alterou um campo que não deveria ser editável.';
    $resp['segredo']  = flag_equipe($sub, 'mass');
  }
  j($resp);
}

// ----- JWT: área que exige papel admin NO TOKEN (alcançável forjando o token) -----
if ($rota === 'admin/segredo') {
  if ($papel !== 'admin') j(['erro'=>'requer papel admin no token'], 403);
  evento('jwt', 'admin-via-token', CATALOGO()['jwt']['pts'], $sub);
  j(['area'=>'diretoria', 'segredo'=>flag_equipe($sub, 'jwt')]);
}

// ----- SSRF: importa um recurso por URL; o fetch é feito pelo servidor -----
if ($rota === 'importar' && $metodo === 'POST') {
  $url = (string)(corpo()['url'] ?? '');
  evento('ssrf', 'importar:' . substr($url, 0, 60), 0, $sub);
  if (!preg_match('#^https?://#i', $url)) j(['erro'=>'url inválida'], 400);
  $ctx = stream_context_create(['http'=>['timeout'=>4]]);
  $conteudo = @file_get_contents($url, false, $ctx);          // <- sem validar destino (SSRF)
  if ($conteudo === false) j(['erro'=>'falha ao buscar', 'url'=>$url], 502);
  j(['url'=>$url, 'tamanho'=>strlen($conteudo), 'conteudo'=>substr($conteudo, 0, 500)]);
}

j(['erro'=>'rota não encontrada', 'rota'=>$rota], 404);
