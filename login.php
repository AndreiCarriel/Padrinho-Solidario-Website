<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';

require_once __DIR__ . '/db.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$perfil = (string) ($_GET['perfil'] ?? $_POST['perfil'] ?? 'padrinho');

if (!in_array($perfil, ['padrinho', 'instituicao'], true)) {
    $perfil = 'padrinho';
}

$email = '';
$erro = '';
$redirect = (string) ($_GET['redirect'] ?? $_POST['redirect'] ?? '');

if ($redirect !== 'doacao.php') {
    $redirect = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');
    $token = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $erro = 'Sua sessão expirou. Atualize a página e tente novamente.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
        $erro = 'Informe um e-mail válido e sua senha.';
    } else {
        try {
            $db = db();

            if ($perfil === 'instituicao') {
                $sql = '
                    SELECT
                        i.id AS perfil_id,
                        u.nome,
                        u.email,
                        u.senha
                    FROM instituicao AS i
                    INNER JOIN usuarios AS u
                        ON u.id = i.id_usuario
                    WHERE u.email = ?
                    LIMIT 1
                ';
            } else {
                $sql = '
                    SELECT
                        p.id AS perfil_id,
                        u.nome,
                        u.email,
                        u.senha
                    FROM padrinho AS p
                    INNER JOIN usuarios AS u
                        ON u.id = p.id_usuario
                    WHERE u.email = ?
                    LIMIT 1
                ';
            }

            $consulta = $db->prepare($sql);
            $consulta->execute([$email]);
            $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

            if (!$usuario || !password_verify($senha, (string) $usuario['senha'])) {
                $erro = 'E-mail ou senha incorretos para o perfil selecionado.';
            } else {
                session_regenerate_id(true);

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['perfil'] = $perfil;

                if ($perfil === 'instituicao') {
                    $_SESSION['instituicao_id'] = (int) $usuario['perfil_id'];
                    $_SESSION['instituicao_nome'] = (string) $usuario['nome'];
                    $_SESSION['instituicao_email'] = (string) $usuario['email'];

                    header('Location: painel-instituicao.php');
                } else {
                    $_SESSION['padrinho_id'] = (int) $usuario['perfil_id'];
                    $_SESSION['padrinho_nome'] = (string) $usuario['nome'];
                    $_SESSION['padrinho_email'] = (string) $usuario['email'];

                    $destino = $redirect === 'doacao.php'
                        ? 'doacao.php'
                        : 'boas-vindas.php';

                    header('Location: ' . $destino);
                }

                exit;
            }
        } catch (PDOException $ex) {
            error_log('Falha no login: ' . $ex->getMessage());

            $erro = 'Não foi possível consultar o banco de dados. Verifique se as tabelas e colunas estão iguais à exportação SQL.';
        } catch (Throwable $ex) {
            error_log('Erro inesperado no login: ' . $ex->getMessage());

            $erro = 'Ocorreu um erro inesperado ao realizar o login.';
        }
    }
}

$titulo = $perfil === 'instituicao'
    ? 'Acesso da instituição'
    : 'Acesso do apadrinhado';

$cadastro = $perfil === 'instituicao'
    ? 'cadastro-instituicao.php'
    : 'cadastro.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#b95f3e">
    <title>Entrar — Padrinho Solidário</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-page {
            min-height: 100vh;
            padding: 42px 20px;
            display: grid;
            place-items: center;
            background: var(--bg);
        }

        .login-wrap {
            width: min(480px, 100%);
        }

        .login-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .login-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: clamp(24px, 5vw, 42px);
            box-shadow: var(--shadow);
        }

        .login-heading {
            text-align: center;
            margin-bottom: 28px;
        }

        .login-heading h1 {
            font: 800 30px Manrope, sans-serif;
            margin: 12px 0 7px;
        }

        .login-heading p {
            color: var(--muted);
            font-size: 14px;
        }

        .login-field {
            display: flex;
            flex-direction: column;
            gap: 7px;
            margin: 17px 0;
        }

        .login-field label {
            font-size: 13px;
            font-weight: 700;
        }

        .login-field input {
            width: 100%;
            min-height: 48px;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--bg);
            color: var(--text);
            font: inherit;
        }

        .login-submit {
            width: 100%;
            margin-top: 8px;
            cursor: pointer;
        }

        .login-links {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            margin-top: 20px;
            font-size: 13px;
        }

        .login-links a {
            color: var(--primary-dark);
            font-weight: 700;
        }

        .login-error {
            padding: 13px 15px;
            border-radius: 10px;
            background: #fff0ed;
            color: #963e2d;
            border: 1px solid #f1c8be;
            font-size: 13px;
            margin-bottom: 18px;
        }
    </style>
</head>

<body>
    <main class="login-page">
        <div class="login-wrap">
            <header class="login-top">
                <a class="brand" href="index.php">
                    <span class="brand-mark">♥</span>
                    <span>Padrinho Solidário</span>
                </a>
                <a href="index.php">← Início</a>
            </header>

            <section class="login-card">
                <div class="login-heading">
                    <span class="eyebrow"><?= e($titulo) ?></span>
                    <h1>Entrar na plataforma</h1>
                    <p>Acesse sua conta usando seu e-mail e senha.</p>
                </div>

                <?php if ($erro !== ''): ?>
                    <div class="login-error" role="alert"><?= e($erro) ?></div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="perfil" value="<?= e($perfil) ?>">
                    <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

                    <div class="login-field">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="email"
                            required autofocus>
                    </div>

                    <div class="login-field">
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" autocomplete="current-password" required>
                    </div>

                    <button class="button login-submit" type="submit">Entrar</button>
                </form>

                <div class="login-links">
                    <a href="<?= e($cadastro) ?>">Ainda não tem cadastro? Cadastre-se</a>
                    <a href="login.php?perfil=<?= $perfil === 'instituicao' ? 'padrinho' : 'instituicao' ?>">
                        Acessar como <?= $perfil === 'instituicao' ? 'apadrinhado' : 'instituição' ?>
                    </a>
                </div>
            </section>
        </div>
    </main>
</body>

</html>