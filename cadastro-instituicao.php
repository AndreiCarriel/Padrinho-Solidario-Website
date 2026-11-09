<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function digits(string $v): string { return preg_replace('/\D+/', '', $v) ?? ''; }
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$fields = ['nome'=>'','email'=>'','telefone'=>'','cep'=>'','responsavel'=>'','necessidades'=>''];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $key => $_) $fields[$key] = trim((string)($_POST[$key] ?? ''));
    $senha = (string)($_POST['senha'] ?? '');
    $confirmar = (string)($_POST['confirmar_senha'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) $errors[] = 'Sessão expirada. Atualize a página.';
    if (mb_strlen($fields['nome']) < 3 || mb_strlen($fields['nome']) > 100) $errors[] = 'Informe o nome da instituição/usuário com até 100 caracteres.';
    if (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL) || strlen($fields['email']) > 100) $errors[] = 'Informe um e-mail válido com até 100 caracteres.';
    if (strlen(digits($fields['telefone'])) < 10 || strlen(digits($fields['telefone'])) > 11) $errors[] = 'Informe um telefone com DDD.';
    if (strlen(digits($fields['cep'])) !== 8) $errors[] = 'Informe um CEP com 8 dígitos.';
    if ($fields['responsavel'] === '' || mb_strlen($fields['responsavel']) > 100) $errors[] = 'Informe o responsável (até 100 caracteres).';
    if ($fields['necessidades'] === '') $errors[] = 'Descreva as necessidades da instituição.';
    if (strlen($senha) < 8) $errors[] = 'A senha deve ter pelo menos 8 caracteres.';
    if ($senha !== $confirmar) $errors[] = 'A confirmação da senha não corresponde.';

    if (!$errors) {
        try {
            $pdo = db();
            $check = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
            $check->execute([mb_strtolower($fields['email'])]);
            if ($check->fetch()) {
                $errors[] = 'Este e-mail já está cadastrado.';
            } else {
                $pdo->beginTransaction();
                $user = $pdo->prepare('INSERT INTO usuarios (nome, email, senha, telefone) VALUES (?, ?, ?, ?)');
                $user->execute([$fields['nome'], mb_strtolower($fields['email']), password_hash($senha, PASSWORD_DEFAULT), digits($fields['telefone'])]);
                $idUsuario = (int)$pdo->lastInsertId();

                $profile = $pdo->prepare('INSERT INTO instituicao (cep, responsavel, necessidades, id_usuario) VALUES (?, ?, ?, ?)');
                $profile->execute([digits($fields['cep']), $fields['responsavel'], $fields['necessidades'], $idUsuario]);
                $pdo->commit();

                session_regenerate_id(true);
                $_SESSION['perfil'] = 'instituicao';
                $_SESSION['instituicao_id'] = (int)$pdo->lastInsertId();
                $_SESSION['instituicao_nome'] = $fields['nome'];
                $_SESSION['instituicao_email'] = mb_strtolower($fields['email']);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: painel-instituicao.php');
                exit;
            }
        } catch (Throwable $ex) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Não foi possível salvar. Confira a conexão e a estrutura do banco bd_padrinhosolidario.';
        }
    }
}
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Cadastro de instituição — Padrinho Solidário</title><link rel="stylesheet" href="assets/css/style.css"><style>
.register-page{min-height:100vh;padding:40px 18px;background:var(--bg)}.register-wrap{max-width:900px;margin:auto}.register-card{background:var(--surface);border:1px solid var(--border);border-radius:22px;padding:clamp(22px,5vw,44px);box-shadow:var(--shadow)}.register-top{display:flex;justify-content:space-between;margin-bottom:24px;gap:12px}.register-heading{text-align:center;margin-bottom:28px}.register-heading h1{font:800 32px Manrope,sans-serif}.form-section{margin-top:26px}.form-section h2{font:700 18px Manrope,sans-serif;border-bottom:1px solid var(--border);padding-bottom:10px}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.field{display:flex;flex-direction:column;gap:6px}.field.full{grid-column:1/-1}.field label{font-size:13px;font-weight:700}.field input,.field textarea{width:100%;padding:12px;border:1px solid var(--border);border-radius:10px;background:var(--bg);color:var(--text);font:inherit}.field textarea{min-height:120px;resize:vertical}.form-actions{display:grid;gap:13px;margin-top:28px}.privacy-note{font-size:12px;color:var(--muted)}.alert-error{padding:14px;background:#fff0ed;color:#963e2d;border-radius:10px;margin-bottom:18px}@media(max-width:620px){.form-grid{grid-template-columns:1fr}.field.full{grid-column:auto}}
</style></head><body><main class="register-page"><div class="register-wrap"><div class="register-top"><a class="brand" href="index.php"><span class="brand-mark">♥</span><span>Padrinho Solidário</span></a><a href="index.php">← Início</a></div><section class="register-card">
<header class="register-heading"><span class="eyebrow">Cadastro de parceiro</span><h1>Cadastre sua instituição</h1><p>Preencha somente as informações existentes nas tabelas usuarios e instituicao.</p></header>
<?php if ($errors): ?><div class="alert-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?=e($error)?></li><?php endforeach ?></ul></div><?php endif ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>">
<div class="form-section"><h2>Usuário</h2><div class="form-grid">
<div class="field full"><label for="nome">Nome *</label><input id="nome" name="nome" maxlength="100" value="<?=e($fields['nome'])?>" required></div>
<div class="field"><label for="email">E-mail *</label><input id="email" name="email" type="email" maxlength="100" value="<?=e($fields['email'])?>" required></div>
<div class="field"><label for="telefone">Telefone *</label><input id="telefone" name="telefone" maxlength="20" value="<?=e($fields['telefone'])?>" required></div>
<div class="field"><label for="senha">Senha *</label><input id="senha" name="senha" type="password" minlength="8" autocomplete="new-password" required></div>
<div class="field"><label for="confirmar_senha">Confirmar senha *</label><input id="confirmar_senha" name="confirmar_senha" type="password" minlength="8" autocomplete="new-password" required></div>
</div></div>
<div class="form-section"><h2>Instituição</h2><div class="form-grid">
<div class="field"><label for="cep">CEP *</label><input id="cep" name="cep" maxlength="10" value="<?=e($fields['cep'])?>" required></div>
<div class="field"><label for="responsavel">Responsável *</label><input id="responsavel" name="responsavel" maxlength="100" value="<?=e($fields['responsavel'])?>" required></div>
<div class="field full"><label for="necessidades">Necessidades *</label><textarea id="necessidades" name="necessidades" required><?=e($fields['necessidades'])?></textarea></div>
</div></div>
<div class="form-actions"><p class="privacy-note">Os dados serão gravados nas tabelas usuarios e instituicao. Nenhuma coluna adicional será utilizada.</p><button class="button" type="submit">Cadastrar instituição</button><a href="login.php?perfil=instituicao">Já possui cadastro? Entrar</a></div>
</form></section></div></main></body></html>
