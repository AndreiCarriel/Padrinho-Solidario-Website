<?php
declare(strict_types=1);
session_start();

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['senha'] ?? '');
    $token = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Sua sessão expirou. Atualize a página e tente novamente.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Informe um e-mail válido e sua senha.';
    } else {
        try {
            // Mesmo caminho utilizado no cadastro.php: mantém os cadastros existentes.
            $dataDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'padrinho-solidario-data';
            $dbFile = $dataDir . DIRECTORY_SEPARATOR . 'cadastros.sqlite';

            if (!is_file($dbFile)) {
                $error = 'Ainda não encontramos uma conta cadastrada. Crie seu cadastro primeiro.';
            } else {
                $db = new PDO('sqlite:' . $dbFile);
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $stmt = $db->prepare('SELECT id, nome, email, senha_hash FROM padrinhos WHERE email = :email LIMIT 1');
                $stmt->execute([':email' => mb_strtolower($email)]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user || !password_verify($password, (string)$user['senha_hash'])) {
                    $error = 'E-mail ou senha incorretos.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['padrinho_id'] = (int)$user['id'];
                    $_SESSION['padrinho_nome'] = (string)$user['nome'];
                    $_SESSION['padrinho_email'] = (string)$user['email'];
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header('Location: boas-vindas.php');
                    exit;
                }
            }
        } catch (PDOException $ex) {
            $error = 'Não foi possível acessar os cadastros. Verifique se PDO_SQLITE está habilitado no PHP.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#b95f3e">
<title>Entrar — Padrinho Solidário</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
.login-page{min-height:100vh;padding:42px 20px;display:grid;place-items:center;background:var(--bg)}
.login-wrap{width:min(480px,100%)}
.login-top{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:24px}
.login-card{background:var(--surface);border:1px solid var(--border);border-radius:22px;padding:clamp(24px,5vw,42px);box-shadow:var(--shadow)}
.login-heading{text-align:center;margin-bottom:28px}
.login-heading h1{font:800 30px Manrope,sans-serif;margin:12px 0 7px}
.login-heading p{color:var(--muted);font-size:14px;margin:0}
.login-field{display:flex;flex-direction:column;gap:7px;margin:17px 0}
.login-field label{font-size:13px;font-weight:700}
.login-field input{width:100%;min-height:48px;padding:12px 14px;border:1px solid var(--border);border-radius:10px;background:var(--bg);color:var(--text);font:inherit;outline:none}
.login-field input:focus{border-color:var(--primary);box-shadow:0 0 0 3px #bd674620}
.login-submit{width:100%;margin-top:8px;cursor:pointer}
.login-links{display:flex;flex-direction:column;align-items:center;gap:12px;margin-top:20px;font-size:13px}
.login-links a{color:var(--primary-dark);font-weight:700}
.login-error{padding:13px 15px;border-radius:10px;background:#fff0ed;color:#963e2d;border:1px solid #f1c8be;font-size:13px;margin-bottom:18px}
</style>
</head>
<body>
<main class="login-page">
<div class="login-wrap">
<header class="login-top"><a class="brand" href="index.php"><span class="brand-mark">♥</span><span>Padrinho Solidário</span></a><a class="back-link" href="index.php">← Início</a></header>
<section class="login-card">
<div class="login-heading"><span class="eyebrow">Que bom ter você de volta</span><h1>Entrar na plataforma</h1><p>Acesse sua conta usando seu e-mail e senha.</p></div>
<?php if ($error !== ''): ?><div class="login-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="login.php">
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
<div class="login-field"><label for="email">E-mail</label><input type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="email" required autofocus></div>
<div class="login-field"><label for="senha">Senha</label><input type="password" id="senha" name="senha" autocomplete="current-password" required></div>
<button class="button login-submit" type="submit">Entrar</button>
</form>
<div class="login-links"><a href="cadastro.php">Ainda não tem cadastro? Cadastre-se</a></div>
</section>
</div>
</main>
</body>
</html>
