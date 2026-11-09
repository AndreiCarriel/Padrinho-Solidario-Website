<?php
declare(strict_types=1);
session_start();
require_once dirname(__DIR__) . '/db.php';
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function admin_only(): void { if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; } }
function csrf_admin(): string { if (empty($_SESSION['csrf_admin'])) $_SESSION['csrf_admin']=bin2hex(random_bytes(32)); return $_SESSION['csrf_admin']; }
function check_csrf_admin(): void { if (!hash_equals(csrf_admin(), (string)($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Sessão expirada. Atualize a página e tente novamente.'); } }
