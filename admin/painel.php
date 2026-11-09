<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
admin_only();
$db = db();

$tables = ['usuarios','padrinho','instituicao','apadrinhado','apadrinhamento'];
$errors = [];
$success = '';

function validDate(string $v): bool { $d=DateTime::createFromFormat('Y-m-d',$v); return $d && $d->format('Y-m-d')===$v; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf_admin();
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'usuario') {
            $nome=trim((string)($_POST['nome']??'')); $email=trim((string)($_POST['email']??'')); $senha=(string)($_POST['senha']??''); $telefone=trim((string)($_POST['telefone']??''));
            if ($nome==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || $senha==='' || $telefone==='') throw new RuntimeException('Preencha nome, e-mail, senha e telefone.');
            $q=$db->prepare('INSERT INTO usuarios (nome,email,senha,telefone) VALUES (?,?,?,?)');
            $q->execute([$nome,$email,password_hash($senha,PASSWORD_DEFAULT),$telefone]);
            $success='Usuário cadastrado.';
        } elseif ($action === 'padrinho') {
            $db->beginTransaction();
            $q=$db->prepare('INSERT INTO usuarios(nome,email,senha,telefone) VALUES(?,?,?,?)');
            $q->execute([trim($_POST['nome']??''),trim($_POST['email']??''),password_hash((string)($_POST['senha']??''),PASSWORD_DEFAULT),trim($_POST['telefone']??'')]);
            $uid=(int)$db->lastInsertId();
            $q=$db->prepare('INSERT INTO padrinho(cpf,renda,endereco,id_usuario) VALUES(?,?,?,?)');
            $q->execute([trim($_POST['cpf']??''),(float)str_replace(',','.',(string)($_POST['renda']??'0')),trim($_POST['endereco']??''),$uid]);
            $db->commit(); $success='Padrinho cadastrado.';
        } elseif ($action === 'instituicao') {
            $db->beginTransaction();
            $q=$db->prepare('INSERT INTO usuarios(nome,email,senha,telefone) VALUES(?,?,?,?)');
            $q->execute([trim($_POST['nome']??''),trim($_POST['email']??''),password_hash((string)($_POST['senha']??''),PASSWORD_DEFAULT),trim($_POST['telefone']??'')]);
            $uid=(int)$db->lastInsertId();
            $q=$db->prepare('INSERT INTO instituicao(cep,responsavel,necessidades,id_usuario) VALUES(?,?,?,?)');
            $q->execute([trim($_POST['cep']??''),trim($_POST['responsavel']??''),trim($_POST['necessidades']??''),$uid]);
            $db->commit(); $success='Instituição cadastrada.';
        } elseif ($action === 'apadrinhado') {
            $q=$db->prepare('INSERT INTO apadrinhado(idade,sexo,observacoes_necessidades,status,id_instituicao) VALUES(?,?,?,?,?)');
            $q->execute([(int)($_POST['idade']??0),trim($_POST['sexo']??''),trim($_POST['observacoes_necessidades']??''),(int)($_POST['status']??1),(int)($_POST['id_instituicao']??0)]);
            $success='Apadrinhado cadastrado.';
        } elseif ($action === 'apadrinhamento') {
            $inicio=trim((string)($_POST['data_inicio']??'')); $fim=trim((string)($_POST['data_fim']??''));
            if (!validDate($inicio) || !validDate($fim)) throw new RuntimeException('Informe datas válidas.');
            $q=$db->prepare('INSERT INTO apadrinhamento(data_inicio,data_fim,tipo_apadrinhamento,status,id_padrinho,id_apadrinhado) VALUES(?,?,?,?,?,?)');
            $q->execute([$inicio,$fim===''?null:$fim,trim($_POST['tipo_apadrinhamento']??''),trim($_POST['status']??''),(int)($_POST['id_padrinho']??0),(int)($_POST['id_apadrinhado']??0)]);
            $success='Apadrinhamento cadastrado.';
        } elseif ($action === 'excluir') {
            $table=(string)($_POST['table']??''); $id=(int)($_POST['id']??0);
            if (!in_array($table,$tables,true) || $id<1) throw new RuntimeException('Registro inválido.');
            $q=$db->prepare("DELETE FROM `$table` WHERE id=?"); $q->execute([$id]);
            $success=$q->rowCount()?'Registro excluído.':'Nenhum registro foi encontrado.';
        }
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        $errors[] = $e instanceof PDOException && $e->getCode()==='23000'
            ? 'Não foi possível salvar: verifique e-mail duplicado ou os relacionamentos (IDs informados).'
            : ($e instanceof RuntimeException ? $e->getMessage() : 'Erro ao salvar. Confira se a estrutura do banco corresponde ao arquivo SQL exportado.');
    }
}

function rows(PDO $db,string $sql): array { return $db->query($sql)->fetchAll(); }
try {
    $usuarios=rows($db,'SELECT id,nome,email,senha,telefone FROM usuarios ORDER BY id DESC');
    $padrinhos=rows($db,'SELECT p.id,p.cpf,p.renda,p.endereco,p.id_usuario,u.nome,u.email,u.telefone FROM padrinho p JOIN usuarios u ON u.id=p.id_usuario ORDER BY p.id DESC');
    $instituicoes=rows($db,'SELECT i.id,i.cep,i.responsavel,i.necessidades,i.id_usuario,u.nome,u.email,u.telefone FROM instituicao i JOIN usuarios u ON u.id=i.id_usuario ORDER BY i.id DESC');
    $apadrinhados=rows($db,'SELECT a.id,a.idade,a.sexo,a.observacoes_necessidades,a.status,a.id_instituicao,i.responsavel,u.nome AS nome_instituicao FROM apadrinhado a JOIN instituicao i ON i.id=a.id_instituicao JOIN usuarios u ON u.id=i.id_usuario ORDER BY a.id DESC');
    $apadrinhamentos=rows($db,'SELECT ap.id,ap.data_inicio,ap.data_fim,ap.tipo_apadrinhamento,ap.status,ap.id_padrinho,ap.id_apadrinhado,up.nome AS padrinho,ac.idade,ac.sexo FROM apadrinhamento ap JOIN padrinho p ON p.id=ap.id_padrinho JOIN usuarios up ON up.id=p.id_usuario JOIN apadrinhado ac ON ac.id=ap.id_apadrinhado ORDER BY ap.id DESC');
} catch(Throwable $e) {
    http_response_code(500);
    exit('A estrutura do banco não corresponde à exportação bd_padrinhosolidario.sql. Confira nomes de tabelas e colunas.');
}
$instOpts=$db->query('SELECT i.id,u.nome FROM instituicao i JOIN usuarios u ON u.id=i.id_usuario ORDER BY u.nome')->fetchAll();
$padOpts=$db->query('SELECT p.id,u.nome FROM padrinho p JOIN usuarios u ON u.id=p.id_usuario ORDER BY u.nome')->fetchAll();
$childOpts=$db->query('SELECT id,idade,sexo FROM apadrinhado ORDER BY id DESC')->fetchAll();
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Painel administrativo</title><link rel="stylesheet" href="../assets/css/style.css">
<style>
body{background:var(--bg);padding:28px 16px}.wrap{max-width:1200px;margin:auto}.top{display:flex;justify-content:space-between;align-items:center;gap:16px}.panel{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:22px;margin:22px 0;box-shadow:var(--shadow)}.forms{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:12px}.forms input,.forms select,.forms textarea{width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:var(--bg);color:var(--text)}.forms button{align-self:end}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:13px}th,td{text-align:left;padding:10px;border-bottom:1px solid var(--border);vertical-align:top}th{white-space:nowrap}.muted{color:var(--muted);font-size:13px}.section-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.section-grid .panel{margin:0}@media(max-width:750px){.section-grid{grid-template-columns:1fr}}
</style></head><body><main class="wrap">
<header class="top"><div><a class="brand" href="../index.php">♥ Padrinho Solidário</a><h1>Painel administrativo</h1><p class="muted">Estrutura alinhada às tabelas e colunas do SQL exportado.</p></div><a class="button button-outline" href="logout.php">Sair</a></header>
<?php if($success):?><div class="panel"><?=h($success)?></div><?php endif?><?php foreach($errors as $err):?><div class="panel" style="color:#a33"><?=h($err)?></div><?php endforeach?>
<section class="section-grid">
<div class="panel"><h2>Cadastrar usuário</h2><p class="muted">Tabela: usuarios</p><form method="post" class="forms"><input type="hidden" name="csrf" value="<?=h(csrf_admin())?>"><input type="hidden" name="action" value="usuario"><input name="nome" placeholder="Nome" required><input type="email" name="email" placeholder="E-mail" required><input type="password" name="senha" placeholder="Senha" required><input name="telefone" placeholder="Telefone" required><button class="button">Salvar usuário</button></form></div>
<div class="panel"><h2>Cadastrar padrinho</h2><p class="muted">Cria o registro em usuarios e padrinho, ligados por id_usuario.</p><form method="post" class="forms"><input type="hidden" name="csrf" value="<?=h(csrf_admin())?>"><input type="hidden" name="action" value="padrinho"><input name="nome" placeholder="Nome" required><input type="email" name="email" placeholder="E-mail" required><input type="password" name="senha" placeholder="Senha" required><input name="telefone" placeholder="Telefone" required><input name="cpf" placeholder="CPF" required><input name="renda" placeholder="Renda" required><input name="endereco" placeholder="Endereço" required><button class="button">Salvar padrinho</button></form></div>
<div class="panel"><h2>Cadastrar instituição</h2><p class="muted">Cria o registro em usuarios e instituicao.</p><form method="post" class="forms"><input type="hidden" name="csrf" value="<?=h(csrf_admin())?>"><input type="hidden" name="action" value="instituicao"><input name="nome" placeholder="Nome da instituição" required><input type="email" name="email" placeholder="E-mail" required><input type="password" name="senha" placeholder="Senha" required><input name="telefone" placeholder="Telefone" required><input name="cep" placeholder="CEP" required><input name="responsavel" placeholder="Responsável" required><textarea name="necessidades" placeholder="Necessidades" required></textarea><button class="button">Salvar instituição</button></form></div>
<div class="panel"><h2>Cadastrar apadrinhado</h2><p class="muted">Tabela: apadrinhado</p><form method="post" class="forms"><input type="hidden" name="csrf" value="<?=h(csrf_admin())?>"><input type="hidden" name="action" value="apadrinhado"><input type="number" name="idade" min="0" required placeholder="Idade"><select name="sexo" required><option value="">Sexo</option><option>Masculino</option><option>Feminino</option><option>Outro</option></select><textarea name="observacoes_necessidades" required placeholder="Observações / necessidades"></textarea><select name="status"><option value="1">Ativo (1)</option><option value="0">Inativo (0)</option></select><select name="id_instituicao" required><option value="">Instituição</option><?php foreach($instOpts as $i):?><option value="<?=$i['id']?>"><?=h($i['nome'])?> (#<?=$i['id']?>)</option><?php endforeach?></select><button class="button">Salvar apadrinhado</button></form></div>
<div class="panel"><h2>Cadastrar apadrinhamento</h2><p class="muted">Tabela: apadrinhamento</p><form method="post" class="forms"><input type="hidden" name="csrf" value="<?=h(csrf_admin())?>"><input type="hidden" name="action" value="apadrinhamento"><label>Início<input type="date" name="data_inicio" required></label><label>Fim<input type="date" name="data_fim" required></label><input name="tipo_apadrinhamento" placeholder="Tipo de apadrinhamento" required><input name="status" placeholder="Status" required><select name="id_padrinho" required><option value="">Padrinho</option><?php foreach($padOpts as $p):?><option value="<?=$p['id']?>"><?=h($p['nome'])?> (#<?=$p['id']?>)</option><?php endforeach?></select><select name="id_apadrinhado" required><option value="">Apadrinhado</option><?php foreach($childOpts as $c):?><option value="<?=$c['id']?>">#<?=$c['id']?> — <?=$c['idade']?> anos / <?=h($c['sexo'])?></option><?php endforeach?></select><button class="button">Salvar vínculo</button></form></div>
</section>

<?php
$lists=[
 ['Usuários','usuarios',$usuarios,['id','nome','email','telefone']],
 ['Padrinhos','padrinho',$padrinhos,['id','nome','email','telefone','cpf','renda','endereco','id_usuario']],
 ['Instituições','instituicao',$instituicoes,['id','nome','email','telefone','cep','responsavel','necessidades','id_usuario']],
 ['Apadrinhados','apadrinhado',$apadrinhados,['id','idade','sexo','observacoes_necessidades','status','nome_instituicao','id_instituicao']],
 ['Apadrinhamentos','apadrinhamento',$apadrinhamentos,['id','data_inicio','data_fim','tipo_apadrinhamento','status','padrinho','idade','sexo','id_padrinho','id_apadrinhado']]
];
foreach($lists as [$title,$table,$data,$cols]):?>
<section class="panel"><h2><?=h($title)?> <small class="muted">(<?=count($data)?>)</small></h2><div class="table-wrap"><table><thead><tr><?php foreach($cols as $col):?><th><?=h($col)?></th><?php endforeach?><th>Ação</th></tr></thead><tbody><?php foreach($data as $row):?><tr><?php foreach($cols as $col):?><td><?=h($row[$col]??'')?></td><?php endforeach?><td><form method="post" onsubmit="return confirm('Excluir este registro?')" ><input type="hidden" name="csrf" value="<?=h(csrf_admin())?>"><input type="hidden" name="action" value="excluir"><input type="hidden" name="table" value="<?=h($table)?>"><input type="hidden" name="id" value="<?=h($row['id'])?>"><button class="button button-outline" type="submit">Excluir</button></form></td></tr><?php endforeach?><?php if(!$data):?><tr><td colspan="<?=count($cols)+1?>">Nenhum registro.</td></tr><?php endif?></tbody></table></div></section>
<?php endforeach?>
<section class="panel"><h2>Doações</h2><p class="muted">A documentação prevê a tabela <code>doacao</code>, mas o arquivo exportado bd_padrinhosolidario.sql não contém essa tabela. Por isso, não criei colunas nem um formulário de doação nesta versão, para respeitar exatamente a exportação enviada.</p></section>
</main></body></html>
