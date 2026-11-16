<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatarTelefone(string $telefone): string
{
    $numero = preg_replace('/\\D+/', '', $telefone) ?? '';
    if (strlen($numero) === 11) {
        return sprintf('(%s) %s %s-%s', substr($numero, 0, 2), substr($numero, 2, 1), substr($numero, 3, 4), substr($numero, 7, 4));
    }
    if (strlen($numero) === 10) {
        return sprintf('(%s) %s-%s', substr($numero, 0, 2), substr($numero, 2, 4), substr($numero, 6, 4));
    }
    return $telefone;
}

$instituicoes = [];
$erroConsulta = false;

try {
    $pdo = db();
    $consulta = $pdo->query(
        'SELECT i.id, i.responsavel, u.nome, u.email, u.telefone
         FROM instituicao AS i
         INNER JOIN usuarios AS u ON u.id = i.id_usuario
         ORDER BY u.nome ASC'
    );
    $instituicoes = $consulta->fetchAll();
} catch (Throwable $erro) {
    $erroConsulta = true;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instituições parceiras — Padrinho Solidário</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .simple-page {
            min-height: 100vh;
            padding: 38px 0 70px
        }

        .simple-wrap {
            width: min(1000px, calc(100% - 40px));
            margin: auto
        }

        .simple-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: clamp(20px, 4vw, 32px);
            box-shadow: var(--shadow)
        }

        .institution-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-top: 24px
        }

        .institution-item {
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px;
            background: var(--surface);
            display: flex;
            flex-direction: column;
            gap: 10px
        }

        .institution-item h2 {
            font: 700 19px Manrope, sans-serif;
            margin: 0
        }

        .institution-item p {
            font-size: 14px;
            color: var(--muted);
            margin: 0;
            overflow-wrap: anywhere
        }

        .institution-item .contact-line {
            display: flex;
            flex-direction: column;
            gap: 3px
        }

        .institution-item .contact-label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--muted)
        }

        .institution-item a {
            color: var(--primary-dark);
            font-weight: 700;
            overflow-wrap: anywhere;
            text-decoration: none
        }

        .institution-item a:hover {
            text-decoration: underline
        }

        .empty-state {
            margin-top: 22px;
            padding: 22px;
            border: 1px dashed var(--border);
            border-radius: 14px;
            color: var(--muted);
            text-align: center
        }

        @media(max-width:650px) {
            .institution-list {
                grid-template-columns: 1fr
            }
        }
    </style>
</head>

<body>
    <main class="simple-page">
        <div class="simple-wrap">
            <a class="brand" href="index.php"><span class="brand-mark">♥</span><span>Padrinho Solidário</span></a>
            <section class="simple-card" style="margin-top:28px">
                <span class="eyebrow">Doações</span>
                <h1>Entre em contato com uma instituição</h1>
                <p>Você informou que ainda não possui cadastro. Selecione uma instituição cadastrada na plataforma e
                    entre em contato diretamente para combinar sua doação.</p>

                <?php if ($erroConsulta): ?>
                    <div class="empty-state" role="alert">Não foi possível carregar as instituições neste momento. Tente
                        novamente mais tarde.</div>
                <?php elseif (!$instituicoes): ?>
                    <div class="empty-state">Ainda não há instituições cadastradas na plataforma.</div>
                <?php else: ?>
                    <div class="institution-list">
                        <?php foreach ($instituicoes as $item): ?>
                            <?php
                            $nome = (string) ($item['nome'] ?? '');
                            $email = (string) ($item['email'] ?? '');
                            $telefone = (string) ($item['telefone'] ?? '');
                            $responsavel = (string) ($item['responsavel'] ?? '');
                            ?>
                            <article class="institution-item">
                                <h2><?= e($nome) ?></h2>
                                <div class="contact-line">
                                    <span class="contact-label">Responsável</span>
                                    <p><?= e($responsavel !== '' ? $responsavel : 'Não informado') ?></p>
                                </div>
                                <div class="contact-line">
                                    <span class="contact-label">E-mail</span>
                                    <?php if ($email !== ''): ?>
                                        <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
                                    <?php else: ?>
                                        <p>Não informado</p>
                                    <?php endif; ?>
                                </div>
                                <div class="contact-line">
                                    <span class="contact-label">Telefone</span>
                                    <?php if ($telefone !== ''): ?>
                                        <a
                                            href="tel:<?= e(preg_replace('/\\D+/', '', $telefone) ?? '') ?>"><?= e(formatarTelefone($telefone)) ?></a>
                                    <?php else: ?>
                                        <p>Não informado</p>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <a class="button button-outline" href="index.php" style="margin-top:24px">Voltar ao início</a>
            </section>
        </div>
    </main>
</body>

</html>