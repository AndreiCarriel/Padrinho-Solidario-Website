<?php
declare(strict_types=1);
function fluxo_csrf(): string
{
    if (empty($_SESSION['csrf_token']))
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return (string) $_SESSION['csrf_token'];
}
function fluxo_validar_csrf(): bool
{
    return isset($_SESSION['csrf_token'], $_POST['csrf_token']) && hash_equals((string) $_SESSION['csrf_token'], (string) $_POST['csrf_token']);
}
function fluxo_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
function fluxo_status_disponivel($status): bool
{
    return in_array(mb_strtolower(trim((string) $status)), ['1', 'ativo', 'ativa', 'disponivel', 'disponível'], true);
}


/**
 * Garante a existência das tabelas auxiliares do fluxo.
 * É idempotente: não apaga nem altera registros existentes.
 * O usuário MySQL precisa ter permissão CREATE na primeira execução.
 */
function fluxo_garantir_tabelas(PDO $db): void
{
    // A migração SQL adiciona as colunas ausentes às tabelas legadas.
    // Aqui apenas validamos que as estruturas esperadas estão disponíveis.
    $checks = [
        'solicitacoes_apadrinhamento' => ['id', 'padrinho_id', 'crianca_id', 'instituicao_id', 'observacoes', 'status', 'criado_em'],
        'doacoes' => ['id', 'padrinho_id', 'id_apadrinhado', 'instituicao_id', 'tipo', 'valor', 'descricao', 'data_doacao', 'status'],
    ];
    foreach ($checks as $table => $columns) {
        $stmt = $db->query("SHOW COLUMNS FROM `{$table}`");
        $found = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
        $missing = array_values(array_diff($columns, $found));
        if ($missing) {
            throw new RuntimeException("A tabela {$table} ainda não recebeu a atualização SQL. Colunas ausentes: " . implode(', ', $missing));
        }
    }
}

/** Retorna mensagem técnica segura para facilitar diagnóstico durante desenvolvimento. */
function fluxo_diagnostico(PDOException $e): string
{
    $sqlState = (string) $e->getCode();
    $driverCode = '';
    if (isset($e->errorInfo[1]))
        $driverCode = ' (MySQL ' . (int) $e->errorInfo[1] . ')';
    return 'Falha no banco [' . fluxo_e($sqlState . $driverCode) . ']. Consulte o log de erros do PHP para ver a consulta e a coluna/tabela ausente.';
}
