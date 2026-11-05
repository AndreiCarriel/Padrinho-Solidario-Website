<?php
declare(strict_types=1);
session_start();

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = false;
$values = [
    'nome' => '', 'email' => '', 'telefone' => '', 'cpf' => '',
    'renda' => '', 'cep' => '', 'logradouro' => '', 'numero' => '',
    'complemento' => '', 'bairro' => '', 'cidade' => '', 'estado' => ''
];

function onlyDigits(string $value): string {
    return preg_replace('/\D+/', '', $value) ?? '';
}
function validCpf(string $cpf): bool {
    $cpf = onlyDigits($cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        $sum = 0;
        for ($i = 0; $i < $t; $i++) $sum += (int)$cpf[$i] * (($t + 1) - $i);
        $digit = ((10 * $sum) % 11) % 10;
        if ((int)$cpf[$t] !== $digit) return false;
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $_) $values[$key] = trim((string)($_POST[$key] ?? ''));
    $password = (string)($_POST['senha'] ?? '');
    $confirm = (string)($_POST['confirmar_senha'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) $errors[] = 'Sua sessão expirou. Atualize a página e tente novamente.';
    if (mb_strlen($values['nome']) < 3) $errors[] = 'Informe seu nome completo.';
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Informe um e-mail válido.';
    if (strlen($password) < 8) $errors[] = 'A senha deve ter pelo menos 8 caracteres.';
    if ($password !== $confirm) $errors[] = 'A confirmação da senha não corresponde.';
    if (strlen(onlyDigits($values['telefone'])) < 10 || strlen(onlyDigits($values['telefone'])) > 11) $errors[] = 'Informe um telefone com DDD.';
    if (!validCpf($values['cpf'])) $errors[] = 'O CPF informado é inválido.';
    if ($values['renda'] === '' || !is_numeric(str_replace(',', '.', $values['renda'])) || (float)str_replace(',', '.', $values['renda']) < 0) $errors[] = 'Informe uma renda mensal válida.';
    if (strlen(onlyDigits($values['cep'])) !== 8) $errors[] = 'Informe um CEP válido.';
    foreach (['logradouro','numero','bairro','cidade','estado'] as $field) {
        if ($values[$field] === '') $errors[] = 'Preencha todos os campos obrigatórios do endereço.';
    }
    if (strlen($values['estado']) !== 2) $errors[] = 'Informe a UF com duas letras.';

    if (!$errors) {
        try {
            // O banco fica fora da pasta pública do projeto.
            $dataDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'padrinho-solidario-data';
            if (!is_dir($dataDir) && !mkdir($dataDir, 0700, true) && !is_dir($dataDir)) {
                throw new RuntimeException('Não foi possível preparar o armazenamento.');
            }
            $db = new PDO('sqlite:' . $dataDir . DIRECTORY_SEPARATOR . 'cadastros.sqlite');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->exec("CREATE TABLE IF NOT EXISTS padrinhos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL, email TEXT NOT NULL UNIQUE,
                senha_hash TEXT NOT NULL, telefone TEXT NOT NULL,
                cpf TEXT NOT NULL UNIQUE, renda_mensal REAL NOT NULL,
                cep TEXT NOT NULL, logradouro TEXT NOT NULL, numero TEXT NOT NULL,
                complemento TEXT, bairro TEXT NOT NULL, cidade TEXT NOT NULL,
                estado TEXT NOT NULL, criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )");
            $stmt = $db->prepare("INSERT INTO padrinhos
                (nome,email,senha_hash,telefone,cpf,renda_mensal,cep,logradouro,numero,complemento,bairro,cidade,estado)
                VALUES (:nome,:email,:senha,:telefone,:cpf,:renda,:cep,:logradouro,:numero,:complemento,:bairro,:cidade,:estado)");
            $stmt->execute([
                ':nome'=>$values['nome'], ':email'=>mb_strtolower($values['email']),
                ':senha'=>password_hash($password, PASSWORD_DEFAULT),
                ':telefone'=>onlyDigits($values['telefone']), ':cpf'=>onlyDigits($values['cpf']),
                ':renda'=>(float)str_replace(',', '.', $values['renda']),
                ':cep'=>onlyDigits($values['cep']), ':logradouro'=>$values['logradouro'],
                ':numero'=>$values['numero'], ':complemento'=>$values['complemento'],
                ':bairro'=>$values['bairro'], ':cidade'=>$values['cidade'], ':estado'=>strtoupper($values['estado'])
            ]);
            $_SESSION['padrinho_id'] = (int)$db->lastInsertId();
            $_SESSION['padrinho_nome'] = $values['nome'];
            $_SESSION['padrinho_email'] = mb_strtolower($values['email']);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: boas-vindas.php');
            exit;
        } catch (PDOException $ex) {
            if (str_contains($ex->getMessage(), 'UNIQUE')) {
                $errors[] = 'Já existe um cadastro com este e-mail ou CPF.';
            } else {
                $errors[] = 'Não foi possível concluir o cadastro. Verifique se o PHP possui a extensão PDO_SQLITE habilitada.';
            }
        } catch (Throwable $ex) {
            $errors[] = 'Não foi possível concluir o cadastro. Tente novamente mais tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#b95f3e">
<title>Cadastro de padrinho — Padrinho Solidário</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
.register-page{min-height:100vh;padding:42px 20px 70px;background:var(--bg)}
.register-wrap{width:min(850px,100%);margin:0 auto}
.register-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;gap:16px}
.back-link{color:var(--primary-dark);font-weight:700;font-size:14px}
.register-card{background:var(--surface);border:1px solid var(--border);border-radius:22px;padding:clamp(22px,5vw,46px);box-shadow:var(--shadow)}
.register-heading{text-align:center;margin-bottom:30px}
.register-heading .eyebrow{display:inline-block;margin-bottom:10px}
.register-heading h1{font:800 clamp(27px,4vw,38px) Manrope,sans-serif;margin:0 0 10px}
.register-heading p{color:var(--muted);margin:0}
.form-section{margin-top:28px}
.form-section h2{font:700 17px Manrope,sans-serif;margin:0 0 16px;padding-bottom:10px;border-bottom:1px solid var(--border)}
.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.field{display:flex;flex-direction:column;gap:6px}
.field.full{grid-column:1/-1}
.field label{font-size:13px;font-weight:700}
.field input{width:100%;min-height:46px;padding:11px 13px;border:1px solid var(--border);border-radius:10px;background:var(--bg);color:var(--text);font:inherit;outline:none}
.field input:focus{border-color:var(--primary);box-shadow:0 0 0 3px #bd674620}
.field small,.privacy-note{font-size:12px;color:var(--muted)}
.form-actions{margin-top:28px;display:flex;flex-direction:column;gap:13px}
.form-actions .button{width:100%;cursor:pointer}
.alert{padding:14px 16px;border-radius:12px;margin:0 0 20px;font-size:14px}
.alert-error{background:#fff0ed;color:#963e2d;border:1px solid #f1c8be}
.alert-success{background:#eaf6ef;color:#216747;border:1px solid #c6e5d2}
.error-list{margin:7px 0 0;padding-left:20px}
@media(max-width:600px){.form-grid{grid-template-columns:1fr}.field.full{grid-column:auto}.register-top{align-items:flex-start}.register-card{padding:22px}}
</style>
</head>
<body>
<main class="register-page">
<div class="register-wrap">
<div class="register-top"><a class="brand" href="index.php"><span class="brand-mark">♥</span><span>Padrinho Solidário</span></a><a class="back-link" href="index.php">← Voltar ao início</a></div>
<section class="register-card">
<header class="register-heading"><span class="eyebrow">Faça parte dessa mudança</span><h1>Cadastro de padrinho</h1><p>Preencha seus dados para iniciar seu cadastro na plataforma.</p></header>
<?php if ($success): ?><div class="alert alert-success" role="status"><strong>Cadastro realizado!</strong><br>Seus dados foram registrados. A próxima etapa será a análise e aprovação pela instituição parceira.</div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-error" role="alert"><strong>Não foi possível concluir o cadastro:</strong><ul class="error-list"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="cadastro.php" autocomplete="on">
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
<div class="form-section"><h2>Dados pessoais</h2><div class="form-grid">
<div class="field full"><label for="nome">Nome completo *</label><input id="nome" name="nome" value="<?= e($values['nome']) ?>" autocomplete="name" required minlength="3"></div>
<div class="field"><label for="email">E-mail *</label><input id="email" type="email" name="email" value="<?= e($values['email']) ?>" autocomplete="email" required></div>
<div class="field"><label for="telefone">Telefone com DDD *</label><input id="telefone" name="telefone" value="<?= e($values['telefone']) ?>" autocomplete="tel" inputmode="tel" placeholder="(00) 00000-0000" required></div>
<div class="field"><label for="cpf">CPF *</label><input id="cpf" name="cpf" value="<?= e($values['cpf']) ?>" inputmode="numeric" placeholder="000.000.000-00" maxlength="14" required></div>
<div class="field"><label for="renda">Renda mensal (R$) *</label><input id="renda" name="renda" value="<?= e($values['renda']) ?>" inputmode="decimal" placeholder="Ex.: 3500,00" required></div>
<div class="field"><label for="senha">Senha *</label><input id="senha" type="password" name="senha" autocomplete="new-password" minlength="8" required><small>Mínimo de 8 caracteres.</small></div>
<div class="field"><label for="confirmar_senha">Confirmar senha *</label><input id="confirmar_senha" type="password" name="confirmar_senha" autocomplete="new-password" minlength="8" required></div>
</div></div>
<div class="form-section"><h2>Endereço</h2><div class="form-grid">
<div class="field"><label for="cep">CEP *</label><input id="cep" name="cep" value="<?= e($values['cep']) ?>" autocomplete="postal-code" inputmode="numeric" placeholder="00000-000" maxlength="9" required></div>
<div class="field"><label for="estado">Estado (UF) *</label><input id="estado" name="estado" value="<?= e($values['estado']) ?>" autocomplete="address-level1" maxlength="2" placeholder="SP" required></div>
<div class="field full"><label for="logradouro">Rua / Avenida *</label><input id="logradouro" name="logradouro" value="<?= e($values['logradouro']) ?>" autocomplete="address-line1" required></div>
<div class="field"><label for="numero">Número *</label><input id="numero" name="numero" value="<?= e($values['numero']) ?>" required></div>
<div class="field"><label for="complemento">Complemento</label><input id="complemento" name="complemento" value="<?= e($values['complemento']) ?>" autocomplete="address-line2"></div>
<div class="field"><label for="bairro">Bairro *</label><input id="bairro" name="bairro" value="<?= e($values['bairro']) ?>" required></div>
<div class="field"><label for="cidade">Cidade *</label><input id="cidade" name="cidade" value="<?= e($values['cidade']) ?>" autocomplete="address-level2" required></div>
</div></div>
<div class="form-actions"><p class="privacy-note">Seus dados serão utilizados para análise do cadastro de apadrinhamento. Não informe dados de terceiros. Esta versão é demonstrativa; antes de publicar o sistema, implemente política de privacidade, controles de acesso, HTTPS e procedimentos de proteção e retenção de dados conforme a LGPD.</p><button class="button" type="submit">Criar meu cadastro</button><a class="back-link" href="login.php">Já possui cadastro? Entre aqui</a><a class="back-link" href="index.php">Cancelar e voltar</a></div>
</form>
</section>
</div>
</main>
<script>
document.getElementById('cpf').addEventListener('input', function () {
  let v = this.value.replace(/\\D/g, '').slice(0, 11);
  v = v.replace(/(\\d{3})(\\d)/, '$1.$2').replace(/(\\d{3})(\\d)/, '$1.$2').replace(/(\\d{3})(\\d{1,2})$/, '$1-$2');
  this.value = v;
});
document.getElementById('telefone').addEventListener('input', function () {
  let v = this.value.replace(/\\D/g, '').slice(0, 11);
  if (v.length > 10) v = v.replace(/(\\d{2})(\\d{5})(\\d{4})/, '($1) $2-$3');
  else if (v.length > 6) v = v.replace(/(\\d{2})(\\d{4})(\\d{0,4})/, '($1) $2-$3');
  else if (v.length > 2) v = v.replace(/(\\d{2})(\\d+)/, '($1) $2');
  this.value = v;
});
document.getElementById('cep').addEventListener('input', function () {
  let v = this.value.replace(/\\D/g, '').slice(0, 8);
  if (v.length > 5) v = v.replace(/(\\d{5})(\\d+)/, '$1-$2');
  this.value = v;
});
</script>
</body></html>
