<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function digits(string $v): string { return preg_replace('/\D+/', '', $v) ?? ''; }
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$values = ['nome'=>'','email'=>'','telefone'=>'','cpf'=>'','renda'=>'','endereco'=>''];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $_) $values[$key] = trim((string)($_POST[$key] ?? ''));
    $senha = (string)($_POST['senha'] ?? '');
    $confirmar = (string)($_POST['confirmar_senha'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) $errors[] = 'Sessão expirada. Atualize a página.';
    if (mb_strlen($values['nome']) < 3 || mb_strlen($values['nome']) > 100) $errors[] = 'Informe um nome entre 3 e 100 caracteres.';
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || strlen($values['email']) > 100) $errors[] = 'Informe um e-mail válido com até 100 caracteres.';
    if (strlen(digits($values['telefone'])) < 10 || strlen(digits($values['telefone'])) > 11) $errors[] = 'Informe um telefone com DDD.';
    if (strlen(digits($values['cpf'])) !== 11) $errors[] = 'O CPF deve conter 11 dígitos.';
    $renda = str_replace(',', '.', $values['renda']);
    if ($values['renda'] === '' || !is_numeric($renda) || (float)$renda < 0) $errors[] = 'Informe uma renda válida.';
    if ($values['endereco'] === '' || mb_strlen($values['endereco']) > 200) $errors[] = 'Informe o endereço (até 200 caracteres).';
    if (strlen($senha) < 8) $errors[] = 'A senha deve ter pelo menos 8 caracteres.';
    if ($senha !== $confirmar) $errors[] = 'A confirmação da senha não corresponde.';

    if (!$errors) {
        try {
            $pdo = db();
            $check = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
            $check->execute([mb_strtolower($values['email'])]);
            if ($check->fetch()) {
                $errors[] = 'Este e-mail já está cadastrado.';
            } else {
                $pdo->beginTransaction();
                $user = $pdo->prepare('INSERT INTO usuarios (nome, email, senha, telefone) VALUES (?, ?, ?, ?)');
                $user->execute([$values['nome'], mb_strtolower($values['email']), password_hash($senha, PASSWORD_DEFAULT), digits($values['telefone'])]);
                $idUsuario = (int)$pdo->lastInsertId();

                $profile = $pdo->prepare('INSERT INTO padrinho (cpf, renda, endereco, id_usuario) VALUES (?, ?, ?, ?)');
                $profile->execute([digits($values['cpf']), number_format((float)$renda, 2, '.', ''), $values['endereco'], $idUsuario]);
                $pdo->commit();

                session_regenerate_id(true);
                $_SESSION['perfil'] = 'padrinho';
                $_SESSION['padrinho_id'] = (int)$pdo->lastInsertId();
                $_SESSION['padrinho_nome'] = $values['nome'];
                $_SESSION['padrinho_email'] = mb_strtolower($values['email']);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: boas-vindas.php');
                exit;
            }
        } catch (Throwable $ex) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Não foi possível salvar. Confira a conexão e a estrutura do banco bd_padrinhosolidario.';
        }
    }
}
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Cadastro de padrinho — Padrinho Solidário</title><link rel="stylesheet" href="assets/css/style.css"><style>
.register-page{min-height:100vh;padding:42px 20px 70px;background:var(--bg)}.register-wrap{width:min(850px,100%);margin:0 auto}.register-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;gap:16px}.back-link{color:var(--primary-dark);font-weight:700;font-size:14px}.register-card{background:var(--surface);border:1px solid var(--border);border-radius:22px;padding:clamp(22px,5vw,46px);box-shadow:var(--shadow)}.register-heading{text-align:center;margin-bottom:30px}.register-heading h1{font:800 clamp(27px,4vw,38px) Manrope,sans-serif;margin:0 0 10px}.register-heading p{color:var(--muted)}.form-section{margin-top:28px}.form-section h2{font:700 17px Manrope,sans-serif;margin:0 0 16px;padding-bottom:10px;border-bottom:1px solid var(--border)}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.field{display:flex;flex-direction:column;gap:6px}.field.full{grid-column:1/-1}.field label{font-size:13px;font-weight:700}.field input{width:100%;min-height:46px;padding:11px 13px;border:1px solid var(--border);border-radius:10px;background:var(--bg);color:var(--text);font:inherit}.form-actions{margin-top:28px;display:flex;flex-direction:column;gap:13px}.form-actions .button{width:100%;cursor:pointer}.privacy-note{font-size:12px;color:var(--muted)}.alert-error{padding:14px 16px;border-radius:12px;margin-bottom:20px;background:#fff0ed;color:#963e2d;border:1px solid #f1c8be}@media(max-width:600px){.form-grid{grid-template-columns:1fr}.field.full{grid-column:auto}}
</style></head><body><main class="register-page"><div class="register-wrap"><div class="register-top"><a class="brand" href="index.php"><span class="brand-mark">♥</span><span>Padrinho Solidário</span></a><a class="back-link" href="index.php">← Voltar ao início</a></div><section class="register-card">
<header class="register-heading"><span class="eyebrow">Faça parte dessa mudança</span><h1>Cadastro de padrinho</h1><p>Preencha somente as informações previstas nas tabelas usuarios e padrinho.</p></header>
<?php if ($errors): ?><div class="alert-error" role="alert"><strong>Não foi possível concluir o cadastro:</strong><ul><?php foreach ($errors as $error): ?><li><?=e($error)?></li><?php endforeach ?></ul></div><?php endif ?>
<form method="post" autocomplete="on"><input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>">
<div class="form-section"><h2>Usuário</h2><div class="form-grid">
<div class="field full"><label for="nome">Nome *</label><input id="nome" name="nome" maxlength="100" value="<?=e($values['nome'])?>" required></div>
<div class="field"><label for="email">E-mail *</label><input id="email" type="email" name="email" maxlength="100" value="<?=e($values['email'])?>" required></div>
<div class="field"><label for="telefone">Telefone *</label><input id="telefone" name="telefone" maxlength="20" value="<?=e($values['telefone'])?>" required></div>
<div class="field"><label for="senha">Senha *</label><input id="senha" type="password" name="senha" minlength="8" autocomplete="new-password" required></div>
<div class="field"><label for="confirmar_senha">Confirmar senha *</label><input id="confirmar_senha" type="password" name="confirmar_senha" minlength="8" autocomplete="new-password" required></div>
</div></div>
<div class="form-section"><h2>Padrinho</h2><div class="form-grid">
<div class="field"><label for="cpf">CPF *</label><input id="cpf" name="cpf" maxlength="14" value="<?=e($values['cpf'])?>" required></div>
<div class="field"><label for="renda">Renda (R$) *</label><input id="renda" name="renda" type="number" min="0" step="0.01" value="<?=e($values['renda'])?>" required></div>
<div class="field full"><label for="endereco">Endereço *</label><input id="endereco" name="endereco" maxlength="200" value="<?=e($values['endereco'])?>" required></div>
</div></div>
<div class="form-actions"><p class="privacy-note">A senha é armazenada com hash seguro. Os dados são gravados nas tabelas usuarios e padrinho, conforme a estrutura do banco.</p><button class="button" type="submit">Criar cadastro</button><a class="back-link" href="login.php">Já possui cadastro? Entrar</a></div>
</form></section></div></main></body></html>
