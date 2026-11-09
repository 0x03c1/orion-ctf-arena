<?php
// ───────────────────────────────────────────────────────────────
//  Órion Corp — alvo de treino (vulnerabilidades MODERNAS).
//  Nesta versão (arena/CTFd) as flags são ÚNICAS POR EQUIPE:
//  cada flag = FLAG{orion-<desafio>-HMAC12(SEGREDO, "equipe|desafio")}
//  O mesmo cálculo roda no plugin do CTFd -> flag de outra equipe
//  é recusada e identifica a origem do vazamento (anti-cola).
// ───────────────────────────────────────────────────────────────
const MARCA     = 'Órion Corp';
const SUBTITULO = 'Plataforma Interna · API v1';
const RODAPE    = 'Órion Corp · Ambiente de treinamento · Uso autorizado';
const DB_ARQ    = '/var/www/data/orion.db';

// JWT propositalmente FRACO (segredo curto + verificação aceita alg:none)
const JWT_SEGREDO = 'orion';

// Supply chain: "backdoor" plantado por dependência comprometida
const DEP_BACKDOOR_HEADER = 'X-Orion-Telemetry';
const DEP_BACKDOOR_TOKEN  = 'r1d3-b4ckd00r';

// Segredo da arena (vem do .env via compose). NUNCA exponha aos alunos.
function SEGREDO_EQUIPE(): string {
  $s = getenv('ORION_TEAM_SECRET');
  return ($s !== false && $s !== '') ? $s : 'troque-isto-no-env';
}

// Flag única por equipe (idêntico ao plugin Python do CTFd)
function flag_equipe(string $equipe, string $desafio): string {
  $equipe = strtolower(trim($equipe));
  $h = substr(hash_hmac('sha256', "$equipe|$desafio", SEGREDO_EQUIPE()), 0, 12);
  return "FLAG{orion-$desafio-$h}";
}
// Senha da equipe derivada do segredo (mesma no CTFd e no alvo)
function senha_equipe(string $equipe): string {
  $equipe = strtolower(trim($equipe));
  return substr(hash_hmac('sha256', "senha|$equipe", SEGREDO_EQUIPE()), 0, 10);
}
// Identidade de equipe: equipe01, equipe02, ... (2+ dígitos)
function eh_equipe(string $u): bool { return (bool)preg_match('/^equipe\d{2,}$/', strtolower(trim($u))); }

// Catálogo (metadados p/ UI e relatório; a flag é calculada por equipe)
function CATALOGO(): array {
  return [
    'bola'   => ['nome'=>'BOLA / IDOR na API',             'pts'=>150, 'owasp'=>'API1:2023'],
    'mass'   => ['nome'=>'Mass Assignment (escalada)',     'pts'=>200, 'owasp'=>'API3:2023'],
    'jwt'    => ['nome'=>'Falsificação de token JWT',      'pts'=>250, 'owasp'=>'API2:2023'],
    'ssrf'   => ['nome'=>'SSRF para serviço interno',      'pts'=>250, 'owasp'=>'A10:2021'],
    'supply' => ['nome'=>'Dependência comprometida',       'pts'=>300, 'owasp'=>'A08:2021'],
    'prompt' => ['nome'=>'Prompt Injection no assistente', 'pts'=>200, 'owasp'=>'LLM01:2025'],
  ];
}
