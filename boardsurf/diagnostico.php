<?php
require_once __DIR__ . '/config.php';
$checks = [];
$checks['PHP'] = ['ok' => version_compare(PHP_VERSION, '8.0.0', '>='), 'text' => PHP_VERSION];
try {
    $db = get_db();
    $db->query('SELECT 1');
    $checks['MySQL'] = ['ok'=>true,'text'=>'Conexão realizada'];
    foreach (['usuarios','logs_autenticacao','produtos'] as $table) {
        $q = $db->query("SHOW TABLES LIKE '$table'");
        $exists = (bool)$q->fetchColumn();
        $checks["Tabela $table"] = ['ok'=>$exists,'text'=>$exists?'Encontrada':'Não encontrada'];
    }
} catch (Throwable $e) {
    $checks['MySQL'] = ['ok'=>false,'text'=>$e->getMessage()];
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Diagnóstico BoardSurf</title>
<style>body{font-family:Arial,sans-serif;background:#f4f7f8;margin:0;padding:30px;color:#222}.card{max-width:760px;margin:auto;background:#fff;padding:28px;border-radius:16px;box-shadow:0 8px 30px #0001}h1{margin-top:0}.item{padding:14px;margin:10px 0;border-radius:10px;background:#f5f5f5}.ok{border-left:6px solid #159447}.bad{border-left:6px solid #c62828}.btn{display:inline-block;margin-top:18px;padding:12px 18px;background:#0187a7;color:#fff;text-decoration:none;border-radius:8px}.small{font-size:13px;color:#666}</style></head><body><div class="card"><h1>Diagnóstico do BoardSurf</h1><p>Use esta página quando o cadastro ou login não conseguirem acessar o banco.</p>
<?php foreach($checks as $name=>$c): ?><div class="item <?= $c['ok']?'ok':'bad' ?>"><strong><?= htmlspecialchars($name) ?>:</strong> <?= htmlspecialchars($c['text']) ?></div><?php endforeach; ?>
<p class="small">Se MySQL estiver com erro, abra o XAMPP e inicie MySQL. Se a tabela não existir, importe <strong>banco.sql</strong> no phpMyAdmin.</p><a class="btn" href="cadastro.html">Ir para cadastro</a> <a class="btn" href="login.html">Ir para login</a></div></body></html>
